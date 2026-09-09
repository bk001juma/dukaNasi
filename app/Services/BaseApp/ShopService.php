<?php

namespace App\Services\BaseApp;

use App\Models\Category;
use App\Models\Shop;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use RuntimeException;

class ShopService
{
    public function __construct(private LegacyAccessService $access) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getShops(array $payload, ?string $token): array
    {
        $user = $this->access->currentUserOrFail($token);
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $userId = LegacyPayload::integer($payload, 'userId', 'user_id');

        if ($user->user_type === 'is_employee' && $adminId !== null && $userId !== null) {
            if ((int) $user->user_id !== $userId || (int) $user->admin_id !== $adminId) {
                return $this->access->noAccessResponse();
            }

            $shops = $this->access->findAssignedShops($user->shops_ids);

            if ($shops->isNotEmpty()) {
                return LegacyApiResponse::array(1, 'Success', $shops);
            }

            return LegacyApiResponse::array(0, 'No Shops have been assigned', $shops);
        }

        if ($user->user_type === 'is_owner' && $adminId !== null) {
            $shops = Shop::query()
                ->where('admin_id', $user->id)
                ->get();

            if ($shops->isNotEmpty()) {
                return LegacyApiResponse::array(1, 'Success', $shops);
            }

            return LegacyApiResponse::array(0, 'No Shops have been assigned', $shops);
        }

        return LegacyApiResponse::array(0, 'Fail invalid request');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function createShop(array $payload, ?string $token): array
    {
        $user = $this->access->currentUserFromToken($token);
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');

        if ($user === null || $adminId === null) {
            return LegacyApiResponse::array(0, 'Fail invalid request');
        }

        if ($user->user_type !== 'is_owner' || (int) $user->id !== $adminId) {
            return $this->access->noAccessResponse();
        }

        $shop = Shop::query()->create([
            'name' => LegacyPayload::string($payload, 'name'),
            'details' => LegacyPayload::string($payload, 'details'),
            'capital' => $this->defaultString(LegacyPayload::string($payload, 'capital'), '0'),
            'equity' => $this->defaultString(LegacyPayload::string($payload, 'equity'), '0'),
            'rent' => $this->defaultString(LegacyPayload::string($payload, 'rent'), '0'),
            'img' => LegacyPayload::string($payload, 'img'),
            'equipment' => LegacyPayload::string($payload, 'equipment'),
            'admin_id' => $adminId,
            'address' => LegacyPayload::string($payload, 'address'),
            'pobox' => LegacyPayload::string($payload, 'pobox'),
            'phone' => LegacyPayload::string($payload, 'phone'),
            'email' => LegacyPayload::string($payload, 'email'),
            'location' => LegacyPayload::string($payload, 'location'),
            'category' => $this->resolveCategoryName($payload),
            'type' => LegacyPayload::integer($payload, 'type') ?? 1,
            'status' => LegacyPayload::integer($payload, 'status') ?? 1,
            'license_assigned' => LegacyPayload::integer($payload, 'licenseAssigned', 'license_assigned') ?? 1,
        ]);

        return LegacyApiResponse::array(1, 'Success', $shop);
    }

    private function resolveCategoryName(array $payload): string
    {
        $categoryId = LegacyPayload::integer($payload, 'categoryId', 'category_id');

        if ($categoryId === null) {
            return $this->defaultString(LegacyPayload::string($payload, 'category'), 'general');
        }

        $categoryName = Category::query()
            ->whereKey($categoryId)
            ->where('status', 1)
            ->value('name');

        if ($categoryName === null) {
            throw new RuntimeException('Category not found');
        }

        return $categoryName;
    }

    private function defaultString(?string $value, string $fallback): string
    {
        return blank($value) ? $fallback : $value;
    }
}
