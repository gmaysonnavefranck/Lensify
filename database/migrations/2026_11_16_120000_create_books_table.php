<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('language')->nullable();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Author::class)->nullable()->constrained()->nullOnDelete();
            // No foreign key: a deleted chapter just leaves nothing to resume
            $table->unsignedBigInteger('last_read_text_id')->nullable();
            $table->timestamps();
        });

        // A book is an ordered list of chapters, each one a regular text
        Schema::table('texts', function (Blueprint $table) {
            $table->foreignIdFor(Book::class)->nullable()->after('author_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->nullable()->after('book_id');
        });
    }

    public function down(): void
    {
        Schema::table('texts', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Book::class);
            $table->dropColumn('position');
        });
        Schema::dropIfExists('books');
    }
};
