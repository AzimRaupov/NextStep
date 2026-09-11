<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StepTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_step_id',
        'status',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(CourseStep::class, 'course_step_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(StepQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(StepAttempt::class);
    }
}
