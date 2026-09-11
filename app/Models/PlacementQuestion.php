<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlacementQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'placement_test_id',
        'order',
        'question',
        'options',
        'correct_option',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    public function placementTest(): BelongsTo
    {
        return $this->belongsTo(PlacementTest::class);
    }

    public function answer(): HasOne
    {
        return $this->hasOne(PlacementAnswer::class);
    }
}
