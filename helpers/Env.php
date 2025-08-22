<?php

declare(strict_types=1);

/**
 * Environment Variables Helper
 * Loads and manages environment variables from .env file
 */

class Env
{
    private static array $variables = [];

    /**
     * Load environment variables from .env file
     */
    public static function load(string $file): void
    {
        if (!file_exists($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');

                self::$variables[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }

    /**
     * Get environment variable value
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$variables[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Get all environment variables
     */
    public static function all(): array
    {
        return self::$variables;
    }
}
