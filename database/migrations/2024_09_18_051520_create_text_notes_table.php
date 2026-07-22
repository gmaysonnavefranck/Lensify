<?php

use App\Models\Text;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('text_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Text::class);
            $table->integer('start_position');
            $table->integer('end_position');
            $table->boolean('is_ai_generated');
            $table->longText('note');
            $table->longText('text_content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('text_notes');
    }
};
