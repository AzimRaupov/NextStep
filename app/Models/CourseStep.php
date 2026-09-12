<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'parent_id',
        'order',
        'title',
        'description',
        'requires_test',
        'status',
        'estimated_days',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'requires_test' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CourseStep::class, 'parent_id');
    }

    /**
     * A course step is either a parent (a section grouping the roadmap into
     * a topical block) or a child (the actual actionable lesson a student
     * works through). Only children carry resources, tests, and progress.
     */
    public function children(): HasMany
    {
        return $this->hasMany(CourseStep::class, 'parent_id')->orderBy('order');
    }

    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    public function computedStatus(): string
    {
        if (! $this->isParent()) {
            return $this->status;
        }

        $childStatuses = $this->children->pluck('status');

        return match (true) {
            $childStatuses->isNotEmpty() && $childStatuses->every(fn (string $status) => $status === 'completed') => 'completed',
            $childStatuses->contains(fn (string $status) => in_array($status, ['available', 'completed'], true)) => 'available',
            default => 'locked',
        };
    }

    public function resources(): HasMany
    {
        return $this->hasMany(StepResource::class);
    }

    public function test(): HasOne
    {
        return $this->hasOne(StepTest::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(StepChatMessage::class)->orderBy('created_at');
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);

        $flattened = $this->course->flattenedChildSteps();
        $currentIndex = $flattened->search(fn (self $step) => $step->id === $this->id);
        $nextStep = $currentIndex === false ? null : $flattened->get($currentIndex + 1);

        if ($nextStep && $nextStep->status === 'locked') {
            $nextStep->update(['status' => 'available', 'started_at' => now()]);
        }
    }
}
