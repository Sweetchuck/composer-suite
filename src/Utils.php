<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite;

class Utils
{
    public static function isVector(array $items): bool
    {
        if (!$items) {
            return false;
        }

        return array_keys($items) === range(0, count($items) - 1);
    }
}
