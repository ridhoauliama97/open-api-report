<?php

namespace App\Exceptions;

class GotenbergThrottleException extends GotenbergPdfException
{
    public function __construct(string $message, public readonly int $retryAfterSeconds = 5)
    {
        parent::__construct($message);
    }

    public static function busy(string $bucket): self
    {
        $retryAfter = max(1, (int) config('services.gotenberg.throttle_retry_after', 5));

        return new self(
            "Antrian konversi PDF ({$bucket}) penuh. Coba lagi beberapa saat.",
            $retryAfter,
        );
    }
}
