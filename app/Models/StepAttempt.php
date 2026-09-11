<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'step_test_id',
        'user_id',
        'score',
        'passed',
        'answers',
    ];

    protected $casts = [
        'passed' => 'boolean',
        'answers' => 'array',
    ];

    public function stepTest(): BelongsTo
    {
        return $this->belongsTo(StepTest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
