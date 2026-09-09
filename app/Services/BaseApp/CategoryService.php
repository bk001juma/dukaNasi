<?php

namespace App\Services\BaseApp;

use App\Models\Category;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(private LegacyAccessService $access) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function createCategory(array $payload, ?string $token): array
    {
        $user = $this->access->currentUserFromToken($token);

        if ($user === null) {
            return LegacyApiResponse::array(0, 'Fail invalid request');
        }

        if ($user->user_type !== 'is_owner') {
            return $this->access->noAccessResponse();
        }

        $name = trim((string) LegacyPayload::string($payload, 'name'));
        $type = LegacyPayload::integer($payload, 'type') ?? 1;
        $status = LegacyPayload::integer($payload, 'status') ?? 1;
        $existingCategory = Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->where('type', $type)
            ->where('status', $status)
            ->first();

        if ($existingCategory !== null) {
            return LegacyApiResponse::array(0, 'Category already exists', $existingCategory);
        }

        $category = Category::query()->create([
            'name' => $name,
            'details' => LegacyPayload::string($payload, 'details'),
            'type' => $type,
            'status' => $status,
        ]);

        return LegacyApiResponse::array(1, 'Success', $category);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getCategories(array $payload, ?string $token): array
    {
        $user = $this->access->currentUserFromToken($token);

        if ($user === null) {
            return LegacyApiResponse::array(0, 'Fail invalid request');
        }

        $status = LegacyPayload::integer($payload, 'status') ?? 1;
        $query = Category::query()->where('status', $status);
        $type = LegacyPayload::integer($payload, 'type');

        if ($type !== null) {
            $query->where('type', $type);
        }

        $categories = $query->get();

        if ($categories->isNotEmpty()) {
            return LegacyApiResponse::array(1, 'Success', $categories);
        }

        return LegacyApiResponse::array(0, 'No Categories found', $categories);
    }
}
