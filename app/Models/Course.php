<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'topic',
        'declared_level',
        'title',
        'summary',
        'level',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function placementTest(): HasOne
    {
        return $this->hasOne(PlacementTest::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(CourseStep::class)->orderBy('order');
    }
}
