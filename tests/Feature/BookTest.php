<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\Text;
use App\Models\TextNote;
use App\Models\User;
use Illuminate\Http\UploadedFile;

// A minimal EPUB: a cover with no text, one document holding two chapters, one with a two-line heading
function makeEpub(): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'epub');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('mimetype', 'application/epub+zip');
    $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container"><rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles></container>');
    $zip->addFromString('OEBPS/content.opf', '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><metadata xmlns:dc="http://purl.org/dc/elements/1.1/"><dc:title>Don Quixote</dc:title><dc:creator>Miguel de Cervantes</dc:creator><dc:language>en</dc:language></metadata>'
        . '<manifest><item id="cover" href="cover.xhtml" media-type="application/xhtml+xml"/><item id="nav" href="nav.xhtml" media-type="application/xhtml+xml"/><item id="c1" href="part%201.xhtml" media-type="application/xhtml+xml"/><item id="c2" href="part2.xhtml" media-type="application/xhtml+xml"/></manifest>'
        . '<spine><itemref idref="cover"/><itemref idref="nav" linear="no"/><itemref idref="c1"/><itemref idref="c2"/></spine></package>');
    $zip->addFromString('OEBPS/cover.xhtml', '<html><body><img src="cover.jpg"/></body></html>');
    $zip->addFromString('OEBPS/nav.xhtml', '<html><body><p>Table of contents</p></body></html>');
    $zip->addFromString('OEBPS/part 1.xhtml', '<?xml version="1.0" encoding="utf-8"?><html xmlns="http://www.w3.org/1999/xhtml"><body>'
        . '<h2>CHAPTER I.</h2><h3>Which treats of the famous gentleman</h3><p>In a village of La Mancha,</p><p>the name of which I have no desire to call to mind…</p>'
        . '<h2>CHAPTER II.</h2><p>These preliminaries settled,<br/>he did not care to put off.</p></body></html>');
    $zip->addFromString('OEBPS/part2.xhtml', '<html><body><div><h2>CHAPTER III.</h2><p>Señor Quixote</p></div></body></html>');
    $zip->close();

    return new UploadedFile($path, 'quixote.epub', null, null, true);
}

test('an epub becomes a book with one text per chapter', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/books', ['file' => makeEpub()]);

    $book = Book::firstWhere('user_id', $user->id);
    $response->assertRedirect(route('books.show', $book));
    expect($book->title)->toBe('Don Quixote')
        ->and($book->author->name)->toBe('Miguel de Cervantes')
        ->and($book->chapters->pluck('title')->all())->toBe(['CHAPTER I. Which treats of the famous gentleman', 'CHAPTER II.', 'CHAPTER III.'])
        ->and($book->chapters->pluck('position')->all())->toBe([1, 2, 3])
        ->and($book->chapters[0]->content)->toBe("In a village of La Mancha,\n\nthe name of which I have no desire to call to mind…")
        ->and($book->chapters[1]->content)->toBe("These preliminaries settled,\nhe did not care to put off.")
        ->and($book->chapters[2]->content)->toBe('Señor Quixote')
        ->and($book->chapters[2]->author_id)->toBe($book->author_id);
});

test('a plain text file is split on chapter headings and unwrapped', function () {
    $user = User::factory()->create();
    $line = trim(str_repeat('word ', 13)); // 64 chars, like a hard-wrapped ebook line
    $txt = "Title: Don Quixote\r\nAuthor: Miguel de Cervantes\r\n\r\nCHAPTER I.\r\n\r\n{$line}\r\n{$line}\r\n{$line}\r\n\r\n"
        . "Part of the story continues on this line\r\n{$line}\r\n\r\nCHAPTER II.\r\n\r\n{$line}\r\n{$line}\r\n";

    $this->actingAs($user)->post('/books', ['file' => UploadedFile::fake()->createWithContent('quixote.txt', $txt)])->assertRedirect();

    $book = Book::firstWhere('user_id', $user->id);
    expect($book->title)->toBe('Don Quixote')
        ->and($book->author->name)->toBe('Miguel de Cervantes')
        ->and($book->chapters->pluck('title')->all())->toBe(['Beginning', 'CHAPTER I.', 'CHAPTER II.'])
        ->and($book->chapters[1]->content)->toBe("$line $line $line\n\nPart of the story continues on this line $line")
        ->and($book->chapters[2]->content)->toBe("$line $line");
});

test('a file with no text is rejected', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/books', ['file' => UploadedFile::fake()->createWithContent('empty.txt', "  \n ")])
        ->assertSessionHasErrors('file');
    $this->actingAs($user)->post('/books', ['file' => UploadedFile::fake()->createWithContent('broken.epub', 'not a zip')])
        ->assertSessionHasErrors('file');
    expect(Book::count())->toBe(0);
});

test('chapters stay off the texts list and link to their neighbours', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/books', ['file' => makeEpub()]);
    $book = Book::first();
    [$first, $second, $third] = $book->chapters;

    $this->actingAs($user)->get('/texts')->assertOk()->assertDontSee('CHAPTER II.');

    $this->actingAs($user)->get("/texts/{$second->id}")->assertInertia(fn ($page) => $page
        ->where('book.title', 'Don Quixote')
        ->where('book.position', 2)
        ->where('book.total', 3)
        ->where('book.prev.id', $first->id)
        ->where('book.next.id', $third->id));

    expect($book->fresh()->last_read_text_id)->toBe($second->id);
});

test('a book can be renamed and its author changed on every chapter', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/books', ['file' => makeEpub()]);
    $book = Book::first();

    $this->actingAs($user)->patch("/books/{$book->id}", ['title' => 'El Quijote', 'author_name' => 'Cervantes'])->assertRedirect();

    $author = Author::firstWhere('name', 'Cervantes');
    expect($book->fresh()->title)->toBe('El Quijote')
        ->and($book->chapters()->pluck('author_id')->unique()->all())->toBe([$author->id]);
});

test('deleting a book removes its chapters and their footnotes', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post('/books', ['file' => makeEpub()]);
    $book = Book::first();
    $chapter = $book->chapters->first();
    TextNote::create(['text_id' => $chapter->id, 'start_position' => 0, 'end_position' => 2, 'is_ai_generated' => false, 'note' => 'n', 'text_content' => 'In']);
    $standalone = Text::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->delete("/books/{$book->id}")->assertRedirect(route('books.index'));

    expect(Book::count())->toBe(0)
        ->and(Text::pluck('id')->all())->toBe([$standalone->id])
        ->and(TextNote::count())->toBe(0);
});

test('books are private', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner)->post('/books', ['file' => makeEpub()]);
    $book = Book::first();
    $intruder = User::factory()->create();

    $this->actingAs($intruder)->get('/books')->assertOk()->assertDontSee('Don Quixote');
    $this->actingAs($intruder)->get("/books/{$book->id}")->assertNotFound();
    $this->actingAs($intruder)->patch("/books/{$book->id}", ['title' => 'x'])->assertNotFound();
    $this->actingAs($intruder)->delete("/books/{$book->id}")->assertNotFound();
    $this->actingAs($intruder)->get("/texts/{$book->chapters->first()->id}")->assertNotFound();

    expect($book->fresh()->title)->toBe('Don Quixote');
});
