<?php

namespace App\Support;

class LevelResolver
{
    public const DECLARED_NONE = 'none';

    public const DECLARED_BASICS = 'basics';

    public const DECLARED_CONFIDENT = 'confident';

    private const LEVEL_ORDER = ['beginner', 'intermediate', 'advanced'];

    /**
     * @return array<int, string>
     */
    public static function declaredLevels(): array
    {
        return [self::DECLARED_NONE, self::DECLARED_BASICS, self::DECLARED_CONFIDENT];
    }

    public static function skipsPlacementTest(string $declaredLevel): bool
    {
        return $declaredLevel === self::DECLARED_NONE;
    }

    public static function fromScore(int $scorePercent, string $declaredLevel): string
    {
        $scoredLevel = match (true) {
            $scorePercent >= 75 => 'advanced',
            $scorePercent >= 40 => 'intermediate',
            default => 'beginner',
        };

        return self::higherLevel($scoredLevel, self::floorFor($declaredLevel));
    }

    public static function label(string $level): string
    {
        return match ($level) {
            'advanced' => 'Продвинутый',
            'intermediate' => 'Средний',
            default => 'Начинающий',
        };
    }

    public static function declaredLevelLabel(string $declaredLevel): string
    {
        return match ($declaredLevel) {
            self::DECLARED_NONE => 'Ничего не знаю',
            self::DECLARED_BASICS => 'Знаю основы',
            self::DECLARED_CONFIDENT => 'Уверенно разбираюсь',
            default => $declaredLevel,
        };
    }

    /**
     * Self-reported level acts as a floor: the placement test can only raise
     * the resolved level above it, never place the student below it.
     */
    private static function floorFor(string $declaredLevel): string
    {
        return match ($declaredLevel) {
            self::DECLARED_CONFIDENT => 'intermediate',
            default => 'beginner',
        };
    }

    private static function higherLevel(string $a, string $b): string
    {
        return array_search($a, self::LEVEL_ORDER, true) >= array_search($b, self::LEVEL_ORDER, true) ? $a : $b;
    }
}
