<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_steps', function (Blueprint $table) {
            $table->unsignedInteger('estimated_minutes')->nullable()->after('requires_test');
            $table->timestamp('started_at')->nullable()->after('status');
            $table->timestamp('completed_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('course_steps', function (Blueprint $table) {
            $table->dropColumn(['estimated_minutes', 'started_at', 'completed_at']);
        });
    }
};
