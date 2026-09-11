<?php

use App\Jobs\GeneratePlacementTestJob;
use App\Jobs\GenerateRoadmapJob;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

it('skips the placement test and starts generating the roadmap when the student declares knowing nothing', function () {
    Queue::fake([GenerateRoadmapJob::class, GeneratePlacementTestJob::class]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/courses', ['topic' => 'Python', 'declared_level' => 'none']);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'generating');
    $response->assertJsonPath('data.level', 'beginner');
    $response->assertJsonPath('data.declared_level', 'none');
    $response->assertJsonPath('data.placement_test', null);

    $course = Course::firstWhere('topic', 'Python');

    expect($course->placementTest)->toBeNull();

    Queue::assertPushed(GenerateRoadmapJob::class, fn (GenerateRoadmapJob $job) => $job->courseId === $course->id);
    Queue::assertNotPushed(GeneratePlacementTestJob::class);
});

it('generates a placement test instead of skipping straight to the roadmap for a declared level', function (string $declaredLevel) {
    Queue::fake([GenerateRoadmapJob::class, GeneratePlacementTestJob::class]);

    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/courses', ['topic' => 'Python', 'declared_level' => $declaredLevel]);

    $response->assertCreated();
    $response->assertJsonPath('data.status', 'queued');
    $response->assertJsonPath('data.level', null);
    $response->assertJsonPath('data.declared_level', $declaredLevel);

    $course = Course::firstWhere('topic', 'Python');

    expect($course->placementTest)->not->toBeNull();
    expect($course->placementTest->status)->toBe('generating');

    Queue::assertPushed(GeneratePlacementTestJob::class, fn (GeneratePlacementTestJob $job) => $job->courseId === $course->id);
    Queue::assertNotPushed(GenerateRoadmapJob::class);
})->with(['basics', 'confident']);

it('rejects a declared level outside the known set', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson('/api/courses', ['topic' => 'Python', 'declared_level' => 'expert']);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('declared_level');
});
