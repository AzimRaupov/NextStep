<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('course_steps')
            ->whereNull('estimated_minutes')
            ->update(['estimated_minutes' => 60]);

        DB::table('course_steps')
            ->whereNull('started_at')
            ->whereIn('status', ['available', 'completed'])
            ->update(['started_at' => DB::raw('created_at')]);

        DB::table('course_steps')
            ->where('status', 'completed')
            ->whereNull('completed_at')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        // Data backfill, not reversible.
    }
};
