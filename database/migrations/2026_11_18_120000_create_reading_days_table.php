<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per user per day they read: the history behind streaks and charts
        Schema::create('reading_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('pages')->default(0);
            $table->unsignedInteger('seconds')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });

        Schema::table('texts', function (Blueprint $table) {
            $table->timestamp('last_read_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_days');
        Schema::table('texts', function (Blueprint $table) {
            $table->dropColumn('last_read_at');
        });
    }
};
