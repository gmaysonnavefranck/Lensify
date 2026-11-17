<?php

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recurring names in a book (characters, places, other), highlighted wherever they appear
        Schema::create('glossary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Book::class)->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('name');
            $table->json('aliases')->nullable();
            $table->text('description')->nullable();
            $table->string('relation')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['book_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('glossary_entries');
    }
};
