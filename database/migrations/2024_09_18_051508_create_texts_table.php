<?php

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('texts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('type')->nullable();
            $table->string('language')->nullable();
            $table->string('year')->nullable();
            $table->longText('content');
            $table->foreignIdFor(User::class);
            $table->foreignIdFor(Author::class)->nullable()->constrained();
            $table->foreignId('translation_for')->nullable()->constrained('texts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('texts');
    }
};
