<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StepQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'step_test_id',
        'order',
        'question',
        'options',
        'correct_option',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function stepTest(): BelongsTo
    {
        return $this->belongsTo(StepTest::class);
    }
}
