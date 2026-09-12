<?php

use App\Models\Course;
use App\Models\CourseStep;
use App\Models\StepQuestion;
use App\Models\StepTest;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function createStepWithTest(User $user): CourseStep
{
    $course = Course::factory()->for($user)->create();
    $module = CourseStep::factory()->for($course)->create();
    $step = CourseStep::factory()->requiresTest()->for($course)->create(['parent_id' => $module->id]);
    $stepTest = StepTest::create(['course_step_id' => $step->id, 'status' => 'pending']);

    StepQuestion::create([
        'step_test_id' => $stepTest->id,
        'order' => 1,
        'question' => 'What is 2 + 2?',
        'options' => ['3', '4', '5'],
        'correct_option' => '4',
    ]);

    StepQuestion::create([
        'step_test_id' => $stepTest->id,
        'order' => 2,
        'question' => 'What is the capital of France?',
        'options' => ['Berlin', 'Paris', 'Madrid'],
        'correct_option' => 'Paris',
    ]);

    return $step->fresh();
}

it('returns a per-question review with the correct answers after submitting', function () {
    $user = User::factory()->create();
    $step = createStepWithTest($user);
    $questions = $step->test->questions;

    $response = $this->actingAs($user)->postJson(
        "/api/courses/{$step->course_id}/steps/{$step->id}/test/submit",
        [
            'answers' => [
                ['question_id' => $questions[0]->id, 'selected_option' => '3'],
                ['question_id' => $questions[1]->id, 'selected_option' => 'Paris'],
            ],
        ]
    );

    $response->assertOk();
    $response->assertJsonPath('correct_count', 1);
    $response->assertJsonPath('total', 2);

    $review = $response->json('review');

    expect($review)->toHaveCount(2);

    expect($review[0])
        ->question_id->toBe($questions[0]->id)
        ->selected_option->toBe('3')
        ->correct_option->toBe('4')
        ->is_correct->toBeFalse();

    expect($review[1])
        ->question_id->toBe($questions[1]->id)
        ->selected_option->toBe('Paris')
        ->correct_option->toBe('Paris')
        ->is_correct->toBeTrue();
});

it('forbids submitting a test for a step belonging to another student', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $step = createStepWithTest($owner);
    $question = $step->test->questions->first();

    $response = $this->actingAs($intruder)->postJson(
        "/api/courses/{$step->course_id}/steps/{$step->id}/test/submit",
        ['answers' => [['question_id' => $question->id, 'selected_option' => '4']]]
    );

    $response->assertForbidden();
});
