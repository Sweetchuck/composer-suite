<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite;

class Utils
{
    public static int $jsonEncodeFlags = \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;

    public static function isVector(array $items): bool
    {
        if (!$items) {
            return false;
        }

        return array_keys($items) === range(0, count($items) - 1);
    }

    public static function isDefaultComposer(string $fileName): bool
    {
        return preg_match('@(^|/)?composer\.json$@', $fileName) === 1;
    }

    public static function encode(array $data): string
    {
        return json_encode($data, static::$jsonEncodeFlags);
    }

    public static function decode(string $encoded): array
    {
        return json_decode($encoded, true);
    }
}
