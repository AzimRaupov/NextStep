<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

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

    /**
     * Every step row for the course: parent steps (roadmap sections) and
     * their children (the actionable lessons) alike.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(CourseStep::class)->orderBy('order');
    }

    public function parentSteps(): HasMany
    {
        return $this->steps()->whereNull('parent_id');
    }

    /**
     * The actionable child steps across every section, in roadmap order.
     * Used to advance the student to the next lesson when one is completed,
     * even when that means crossing into the next section.
     *
     * @return Collection<int, CourseStep>
     */
    public function flattenedChildSteps(): Collection
    {
        return $this->parentSteps()->with('children')->get()
            ->flatMap(fn (CourseStep $parent) => $parent->children);
    }
}
