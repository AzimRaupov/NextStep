<?php

namespace App\Support;

class Locales
{
    public const DEFAULT = 'tg';

    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return ['ru', 'en', 'tg'];
    }
}
