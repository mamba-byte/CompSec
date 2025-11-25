<?php

declare(strict_types=1);

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read env file at {$path}");
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
            $key = trim($key);
            $value = trim($value);
            if ($value !== '' && $value[0] === '"' && str_ends_with($value, '"')) {
                $value = substr($value, 1, -1);
            }
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return $value;
    }

    public static function getInt(string $key, int $default): int
    {
        $value = self::get($key);
        return $value === null ? $default : (int)$value;
    }
}

