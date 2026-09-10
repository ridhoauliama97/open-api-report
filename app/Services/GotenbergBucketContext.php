<?php

namespace App\Services;

class GotenbergBucketContext
{
    private static ?string $bucket = null;

    public static function current(): string
    {
        return self::$bucket ?? 'interactive';
    }

    /**
     * Run the callback while forcing a specific throttle bucket for every
     * Gotenberg conversion made inside it.
     */
    public static function run(string $bucket, callable $callback): mixed
    {
        $previous = self::$bucket;
        self::$bucket = $bucket;

        try {
            return $callback();
        } finally {
            self::$bucket = $previous;
        }
    }
}
