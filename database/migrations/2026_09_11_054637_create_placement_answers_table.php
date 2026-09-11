<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('placement_question_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('selected_option');
            $table->boolean('is_correct');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement_answers');
    }
};
