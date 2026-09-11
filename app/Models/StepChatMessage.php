<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_step_id',
        'user_id',
        'role',
        'content',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(CourseStep::class, 'course_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
