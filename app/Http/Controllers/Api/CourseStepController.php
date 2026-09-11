<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CourseStepController extends Controller
{
    public function complete(Request $request, Course $course, CourseStep $step)
    {
        Gate::authorize('view', $course);

        abort_if($step->requires_test, 422, 'Этот шаг завершается прохождением теста.');

        $step->markCompleted();

        return response()->json(['status' => 'completed']);
    }
}
