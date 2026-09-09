<?php

namespace App\Http\Middleware;

use App\Models\RateLimitBlock;
use App\Models\RateLimitBlockLog;
use App\Support\LegacyApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class LegacyLoginRateLimit
{
    private const LIMIT = 3;

    private const DURATION_SECONDS = 5;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $keyType = 'username';
        $keyValue = (string) $request->header('username', '').(string) $request->header('deviceId', '');
        $now = now()->getTimestamp();

        $existingBlock = RateLimitBlock::query()
            ->where('key_type', $keyType)
            ->where('key_value', $keyValue)
            ->first();

        if ($existingBlock !== null && $now < $existingBlock->blocked_until) {
            $remainingSeconds = $existingBlock->blocked_until - $now;

            return LegacyApiResponse::json(
                0,
                'Too many requests. You are blocked for '.$this->formatDuration($remainingSeconds),
                null,
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $counterKey = 'legacy_login_rate_limit:'.$keyValue;
        $count = (int) Cache::get($counterKey, 0);

        if ($count >= self::LIMIT) {
            $this->applyBlock($keyType, $keyValue);

            return LegacyApiResponse::json(
                0,
                'Too many requests — you are blocked temporarily.',
                null,
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        if ($count === 0) {
            Cache::put($counterKey, 1, self::DURATION_SECONDS);
        } else {
            Cache::increment($counterKey);
        }

        return $next($request);
    }

    private function applyBlock(string $keyType, string $keyValue): void
    {
        $block = RateLimitBlock::query()->firstOrNew([
            'key_type' => $keyType,
            'key_value' => $keyValue,
        ]);

        $block->current_penalty = ((int) $block->current_penalty) + 1;
        $block->blocked_until = now()->getTimestamp() + $this->penaltySeconds($block->current_penalty);
        $block->save();

        RateLimitBlockLog::query()->create([
            'key_type' => $keyType,
            'key_value' => $keyValue,
            'penalty_level' => $block->current_penalty,
            'blocked_until' => $block->blocked_until,
            'reason' => 'Exceeded rate limit',
            'timestamp' => now(),
        ]);
    }

    private function penaltySeconds(int $penalty): int
    {
        return match ($penalty) {
            1 => 60,
            2 => 600,
            3 => 1800,
            default => 86400,
        };
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return 'a few seconds';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.' hour(s)';
        }

        if ($minutes > 0) {
            $parts[] = $minutes.' minute(s)';
        }

        if ($remainingSeconds > 0) {
            $parts[] = $remainingSeconds.' second(s)';
        }

        return implode(' ', $parts);
    }
}
