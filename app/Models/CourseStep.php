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

        $nextStep = $this->course->steps()->where('order', $this->order + 1)->first();

        if ($nextStep && $nextStep->status === 'locked') {
            $nextStep->update(['status' => 'available', 'started_at' => now()]);
        }
    }
}
