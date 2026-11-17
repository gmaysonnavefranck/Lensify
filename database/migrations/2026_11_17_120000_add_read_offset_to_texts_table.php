<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Character offset of the first word on the page the reader stopped at
        Schema::table('texts', function (Blueprint $table) {
            $table->unsignedInteger('read_offset')->default(0)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('texts', function (Blueprint $table) {
            $table->dropColumn('read_offset');
        });
    }
};
