<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Highlight;
use App\Models\Quote;
use App\Models\ReadingDay;
use App\Models\Text;
use App\Models\TextNote;
use App\Models\VocabularyWord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $textIds = Text::where('user_id', $userId)->select('id');

        // Streak, week and chart are worked out in the browser, in the reader's own timezone
        // ponytail: a year of days is sent, so streaks show at most 366; count server-side if that's ever reached
        $days = ReadingDay::where('user_id', $userId)->where('date', '>=', now()->subDays(367))
            ->orderBy('date')->get(['date', 'pages', 'seconds']);

        $recent = Text::where('user_id', $userId)->whereNotNull('last_read_at')
            ->with(['book:id,title', 'author:id,name'])
            ->latest('last_read_at')->limit(30)
            ->get(['id', 'title', 'book_id', 'author_id', 'position', 'read_offset', 'last_read_at', DB::raw('CHAR_LENGTH(content) as length')])
            // One entry per book (its latest chapter), so a book read across chapters doesn't fill the list
            ->unique(fn (Text $text) => $text->book_id ? "book-{$text->book_id}" : "text-{$text->id}")
            ->take(4)->values()
            ->map(fn (Text $text) => [
                'id' => $text->id,
                'title' => $text->title,
                'author' => $text->author?->name,
                'book' => $text->book ? [
                    'id' => $text->book->id,
                    'title' => $text->book->title,
                    'chapter' => $text->book->chapters()->where('position', '<=', $text->position)->count(),
                    'chapters' => $text->book->chapters()->count(),
                ] : null,
                'percent' => $text->length ? (int) round(100 * $text->read_offset / $text->length) : 0,
                'last_read_at' => $text->last_read_at,
            ]);

        return Inertia::render('Dashboard', [
            'days' => $days,
            'recent' => $recent,
            'quote' => $this->ofTheDay(Quote::where('user_id', $userId)->with('text:id,title')),
            'word' => $this->ofTheDay(VocabularyWord::where('user_id', $userId)),
            'totals' => [
                'books' => Book::where('user_id', $userId)->count(),
                'texts' => Text::where('user_id', $userId)->whereNull('book_id')->count(),
                'footnotes' => TextNote::whereIn('text_id', $textIds)->count(),
                'highlights' => Highlight::whereIn('text_id', $textIds)->count(),
                'quotes' => Quote::where('user_id', $userId)->count(),
                'words' => VocabularyWord::where('user_id', $userId)->count(),
            ],
        ]);
    }

    // Adds pages turned and time read to the reader's day (sent by the reader as they turn pages)
    public function track(Request $request)
    {
        $validated = $request->validate([
            // The reader's local date; allowed a day either side of the server's for timezones
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now()->subDays(2)->toDateString(), 'before_or_equal:'.now()->addDays(2)->toDateString()],
            'pages' => 'required|integer|min:0|max:200',
            'seconds' => 'required|integer|min:0|max:3600',
        ]);

        $day = ReadingDay::firstOrCreate(['user_id' => Auth::id(), 'date' => $validated['date']]);
        ReadingDay::whereKey($day->id)->incrementEach(['pages' => $validated['pages'], 'seconds' => $validated['seconds']]);

        return response()->noContent();
    }

    // The same item all day, a different one tomorrow
    private function ofTheDay($query)
    {
        $count = (clone $query)->count();

        return $count ? $query->orderBy('id')->skip(crc32(now()->toDateString()) % $count)->first() : null;
    }
}
