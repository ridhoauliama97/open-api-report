<?php

namespace App\Services;

use App\Exceptions\GotenbergThrottleException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Semaphore limiting how many Gotenberg conversions may run at once per
 * bucket (interactive user traffic / warm refresh commands / async jobs).
 *
 * Slots are named cache locks ("gotenberg:{bucket}:{n}"), so the effective
 * store depends on the configured cache driver: file/database store keeps
 * this correct for the single-server deployment; redis would also be
 * correct for a multi-server setup without code changes.
 */
class GotenbergThrottle
{
    public const BUCKETS = ['interactive', 'warm', 'async'];

    /**
     * Acquire one slot of the given bucket or throw GotenbergThrottleException.
     *
     * @return array{0: string, 1: mixed} slot key + lock instance for release()
     */
    public function acquire(string $bucket): array
    {
        $maxSlots = max(1, (int) config('services.gotenberg.max_'.$bucket, 1));
        $waitSeconds = max(0, (int) config('services.gotenberg.wait_'.$bucket.'_seconds', 0));
        $ttl = $this->lockTtl();

        $deadline = microtime(true) + $waitSeconds;

        do {
            for ($slot = 1; $slot <= $maxSlots; $slot++) {
                $lock = Cache::lock($this->slotKey($bucket, $slot), $ttl);

                if ($lock->get()) {
                    return [$this->slotKey($bucket, $slot), $lock];
                }
            }

            if ($waitSeconds > 0) {
                usleep(200000);
            }
        } while (microtime(true) < $deadline);

        throw GotenbergThrottleException::busy($bucket);
    }

    public function release(array $slot): void
    {
        [$key, $lock] = $slot;

        try {
            $lock->release();
        } catch (RuntimeException) {
            // lock may already be expired; nothing to release
        }
    }

    private function slotKey(string $bucket, int $slot): string
    {
        return "gotenberg:{$bucket}:{$slot}";
    }

    private function lockTtl(): int
    {
        $timeout = (int) config('services.gotenberg.timeout', 300);
        $retries = max(0, (int) config('services.gotenberg.retry_attempts', 0));

        return $timeout * ($retries + 1) + 60;
    }
}
