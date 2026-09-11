<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('step_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_step_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('url');
            $table->string('type')->default('article');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('step_resources');
    }
};
