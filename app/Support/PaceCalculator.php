<?php

namespace App\Support;

use App\Models\CourseStep;
use Illuminate\Support\Collection;

class PaceCalculator
{
    private const MINUTES_PER_DAY = 1440;

    public static function forStep(CourseStep $step): ?array
    {
        if ($step->status === 'locked' || $step->estimated_days === null || $step->started_at === null) {
            return null;
        }

        $elapsedMinutes = $step->status === 'completed' && $step->completed_at !== null
            ? $step->started_at->diffInMinutes($step->completed_at)
            : $step->started_at->diffInMinutes(now());

        $estimatedMinutes = $step->estimated_days * self::MINUTES_PER_DAY;
        $ratio = $estimatedMinutes > 0 ? $elapsedMinutes / $estimatedMinutes : 1;

        $status = match (true) {
            $ratio > 1.3 => 'behind',
            $step->status === 'completed' && $ratio < 0.7 => 'ahead',
            default => 'on_time',
        };

        return [
            'status' => $status,
            'ratio' => $ratio,
            'elapsed_days' => round($elapsedMinutes / self::MINUTES_PER_DAY, 1),
            'estimated_days' => $step->estimated_days,
            'delta_days' => round(($elapsedMinutes - $estimatedMinutes) / self::MINUTES_PER_DAY, 1),
        ];
    }

    /**
     * @param  Collection<int, CourseStep>  $steps
     * @return array{
     *     completed_status: string|null,
     *     completed_delta_days: float,
     *     completed_ratio: float|null,
     *     current_step_status: string|null,
     *     current_step_delta_days: float|null,
     *     current_step_ratio: float|null,
     *     overall_ratio: float|null,
     *     remaining_days: int,
     *     deadline_date: string|null,
     * }
     */
    public static function forCourse(Collection $steps): array
    {
        $completedEstimatedMinutes = 0;
        $completedElapsedMinutes = 0;
        $hasCompleted = false;

        $currentStepStatus = null;
        $currentStepDelta = null;
        $currentStepRatio = null;

        foreach ($steps as $step) {
            $pace = self::forStep($step);

            if ($pace === null) {
                continue;
            }

            if ($step->status === 'completed') {
                $completedEstimatedMinutes += $step->estimated_days * self::MINUTES_PER_DAY;
                $completedElapsedMinutes += $pace['elapsed_days'] * self::MINUTES_PER_DAY;
                $hasCompleted = true;
            } elseif ($step->status === 'available') {
                $currentStepStatus = $pace['status'];
                $currentStepDelta = $pace['delta_days'];
                $currentStepRatio = $pace['ratio'];
            }
        }

        $completedStatus = null;
        $completedDelta = round(($completedElapsedMinutes - $completedEstimatedMinutes) / self::MINUTES_PER_DAY, 1);
        $completedRatio = null;

        if ($hasCompleted) {
            $completedRatio = $completedEstimatedMinutes > 0 ? $completedElapsedMinutes / $completedEstimatedMinutes : 1;

            $completedStatus = match (true) {
                $completedRatio > 1.3 => 'behind',
                $completedRatio < 0.7 => 'ahead',
                default => 'on_time',
            };
        }

        // Remaining calendar days needed to finish the roadmap: the estimated
        // length of every step that isn't completed yet, from today. This is
        // the deadline the platform commits the student to for planning.
        $remainingDays = (int) $steps->where('status', '!=', 'completed')->sum('estimated_days');
        $deadlineDate = $remainingDays > 0 ? now()->addDays($remainingDays)->toDateString() : null;

        return [
            'completed_status' => $completedStatus,
            'completed_delta_days' => $completedDelta,
            'completed_ratio' => $completedRatio,
            'current_step_status' => $currentStepStatus,
            'current_step_delta_days' => $currentStepDelta,
            'current_step_ratio' => $currentStepRatio,
            'overall_ratio' => $currentStepRatio ?? $completedRatio,
            'remaining_days' => $remainingDays,
            'deadline_date' => $deadlineDate,
        ];
    }
}
