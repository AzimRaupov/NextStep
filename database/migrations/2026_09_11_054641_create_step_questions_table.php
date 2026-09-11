<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('step_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('step_test_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order');
            $table->text('question');
            $table->json('options');
            $table->string('correct_option');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_questions');
    }
};
