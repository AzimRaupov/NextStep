<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pacing used to be estimated in minutes of focused study, which read as
     * "1 hour per step" and made the ahead/behind pace comparison fire within
     * an hour of starting a step. Steps realistically take a student a few
     * days of calendar time, so pacing and deadlines are now planned in days.
     */
    public function up(): void
    {
        Schema::table('course_steps', function (Blueprint $table) {
            $table->unsignedTinyInteger('estimated_days')->nullable()->after('estimated_minutes');
        });

        DB::table('course_steps')->whereNotNull('estimated_minutes')->select('id', 'estimated_minutes')
            ->orderBy('id')->each(function ($step) {
                DB::table('course_steps')->where('id', $step->id)->update([
                    'estimated_days' => max(1, (int) round($step->estimated_minutes / 90)),
                ]);
            });

        Schema::table('course_steps', function (Blueprint $table) {
            $table->dropColumn('estimated_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('course_steps', function (Blueprint $table) {
            $table->unsignedInteger('estimated_minutes')->nullable()->after('requires_test');
        });

        DB::table('course_steps')->whereNotNull('estimated_days')->select('id', 'estimated_days')
            ->orderBy('id')->each(function ($step) {
                DB::table('course_steps')->where('id', $step->id)->update([
                    'estimated_minutes' => $step->estimated_days * 90,
                ]);
            });

        Schema::table('course_steps', function (Blueprint $table) {
            $table->dropColumn('estimated_days');
        });
    }
};
