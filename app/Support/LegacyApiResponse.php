<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LegacyApiResponse
{
    public static function json(
        int $statusCode,
        string $message,
        mixed $data = null,
        int $httpStatus = 200,
    ): JsonResponse {
        return response()->json(self::array($statusCode, $message, $data), $httpStatus);
    }

    /**
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public static function array(int $statusCode, string $message, mixed $data = null): array
    {
        return [
            'statusCode' => $statusCode,
            'message' => $message,
            'data' => self::serialize($data),
        ];
    }

    public static function rawJson(mixed $data, int $httpStatus = 200): JsonResponse
    {
        return response()->json(self::serialize($data), $httpStatus);
    }

    public static function serialize(mixed $value): mixed
    {
        if ($value instanceof CarbonInterface) {
            return $value->format('Y-m-d\TH:i:s');
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d\TH:i:s');
        }

        if ($value instanceof Collection) {
            return $value->map(fn (mixed $item): mixed => self::serialize($item))->values()->all();
        }

        if ($value instanceof Model && method_exists($value, 'toLegacyArray')) {
            return self::serializePreservingKeys($value->toLegacyArray());
        }

        if ($value instanceof Arrayable) {
            return self::camelizeArray($value->toArray());
        }

        if (is_array($value)) {
            return self::camelizeArray($value);
        }

        return $value;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private static function camelizeArray(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $nextValue = self::serialize($value);

            if (is_int($key)) {
                $result[$key] = $nextValue;

                continue;
            }

            $result[Str::camel((string) $key)] = $nextValue;
        }

        return $result;
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private static function serializePreservingKeys(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $result[$key] = self::serialize($value);
        }

        return $result;
    }
}
