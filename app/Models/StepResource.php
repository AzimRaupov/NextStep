<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepResource extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_step_id',
        'title',
        'url',
        'type',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(CourseStep::class, 'course_step_id');
    }
}
