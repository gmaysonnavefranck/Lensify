<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The first tables were created without foreign keys, so deleting a user (or a text) left rows behind.
// Remove what is already orphaned, then let the database cascade from now on.
return new class extends Migration
{
    private const OWNED_BY_USER = ['texts', 'authors', 'genres', 'themes', 'literary_periods'];

    public function up(): void
    {
        $users = DB::table('users')->select('id');
        DB::table('texts')->whereNotIn('user_id', $users)->delete();
        DB::table('text_notes')->whereNotIn('text_id', DB::table('texts')->select('id'))->delete();
        $orphanAuthors = DB::table('authors')->whereNotIn('user_id', $users)->pluck('id');
        DB::table('texts')->whereIn('author_id', $orphanAuthors)->update(['author_id' => null]);
        DB::table('authors')->whereIn('id', $orphanAuthors)->delete();
        foreach (['genres', 'themes', 'literary_periods'] as $table) {
            DB::table($table)->whereNotIn('user_id', $users)->delete();
        }

        foreach (self::OWNED_BY_USER as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        }
        Schema::table('text_notes', fn (Blueprint $t) => $t->foreign('text_id')->references('id')->on('texts')->cascadeOnDelete());

        // Deleting an author keeps their texts, unlinked (it used to block the delete)
        Schema::table('texts', function (Blueprint $t) {
            $t->dropForeign(['author_id']);
            $t->foreign('author_id')->references('id')->on('authors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('texts', function (Blueprint $t) {
            $t->dropForeign(['author_id']);
            $t->foreign('author_id')->references('id')->on('authors');
        });
        Schema::table('text_notes', fn (Blueprint $t) => $t->dropForeign(['text_id']));
        foreach (self::OWNED_BY_USER as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropForeign(['user_id']));
        }
    }
};
