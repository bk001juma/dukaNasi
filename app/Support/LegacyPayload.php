<?php

namespace App\Support;

use Illuminate\Support\Str;

class LegacyPayload
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public static function get(array $payload, string $key, string ...$aliases): mixed
    {
        foreach (self::candidates($key, $aliases) as $candidate) {
            if (array_key_exists($candidate, $payload)) {
                return $payload[$candidate];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function string(array $payload, string $key, string ...$aliases): ?string
    {
        $value = self::get($payload, $key, ...$aliases);

        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function integer(array $payload, string $key, string ...$aliases): ?int
    {
        $value = self::get($payload, $key, ...$aliases);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function float(array $payload, string $key, string ...$aliases): ?float
    {
        $value = self::get($payload, $key, ...$aliases);

        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    public static function list(array $payload, string $key, string ...$aliases): array
    {
        $value = self::get($payload, $key, ...$aliases);

        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values($value);
        }

        return [$value];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, int>
     */
    public static function integerList(array $payload, string $key, string ...$aliases): array
    {
        return array_map(
            fn (mixed $value): int => (int) $value,
            self::list($payload, $key, ...$aliases),
        );
    }

    /**
     * @param  array<int, string>  $aliases
     * @return array<int, string>
     */
    private static function candidates(string $key, array $aliases): array
    {
        $names = [$key, ...$aliases, Str::snake($key), Str::camel($key)];

        return array_values(array_unique($names));
    }
}
