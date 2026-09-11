<?php

use App\Support\LevelResolver;

it('resolves the level from the score alone when the declared level sets no floor', function (int $score, string $expected) {
    expect(LevelResolver::fromScore($score, LevelResolver::DECLARED_BASICS))->toBe($expected);
})->with([
    'low score stays beginner' => [10, 'beginner'],
    'mid score becomes intermediate' => [40, 'intermediate'],
    'high score becomes advanced' => [75, 'advanced'],
]);

it('never resolves below intermediate for a confident declared level, even on a low score', function () {
    expect(LevelResolver::fromScore(10, LevelResolver::DECLARED_CONFIDENT))->toBe('intermediate');
});

it('still lets a confident declared level reach advanced on a high score', function () {
    expect(LevelResolver::fromScore(90, LevelResolver::DECLARED_CONFIDENT))->toBe('advanced');
});

it('only skips the placement test for the "knows nothing" declared level', function () {
    expect(LevelResolver::skipsPlacementTest(LevelResolver::DECLARED_NONE))->toBeTrue();
    expect(LevelResolver::skipsPlacementTest(LevelResolver::DECLARED_BASICS))->toBeFalse();
    expect(LevelResolver::skipsPlacementTest(LevelResolver::DECLARED_CONFIDENT))->toBeFalse();
});
