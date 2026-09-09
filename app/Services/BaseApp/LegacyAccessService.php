<?php

namespace App\Services\BaseApp;

use App\Models\Admin;
use App\Models\Shop;
use App\Support\LegacyApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

class LegacyAccessService
{
    public function currentUserFromToken(?string $token): ?Admin
    {
        if (blank($token)) {
            return null;
        }

        return Admin::query()->where('secret_key', $token)->first();
    }

    public function currentUserOrFail(?string $token): Admin
    {
        $user = $this->currentUserFromToken($token);

        if ($user === null) {
            throw new RuntimeException('User token expired ! login again');
        }

        return $user;
    }

    public function noLicense(string $token, string $username): bool
    {
        $user = null;

        if (filled($token)) {
            $user = Admin::query()->where('secret_key', $token)->first();
        } elseif (filled($username)) {
            $user = Admin::query()->where('username', $username)->first();
        }

        if ($user === null) {
            return true;
        }

        if ($user->user_type === 'is_employee' && $user->admin_id !== null) {
            $owner = Admin::query()->find($user->admin_id);

            if ($owner !== null) {
                $user = $owner;
            }
        }

        if ((int) $user->active !== 1 || $user->expiring_date === null) {
            return true;
        }

        return $user->expiring_date->toDateString() <= today()->toDateString();
    }

    public function canAccessShop(Admin $user, ?int $adminId, ?int $shopId, ?int $userId = null): bool
    {
        if ($adminId === null || $shopId === null) {
            return false;
        }

        if ($user->user_type === 'is_employee') {
            if ((int) $user->admin_id !== $adminId) {
                return false;
            }

            if ($userId !== null && (int) $user->user_id !== $userId) {
                return false;
            }

            return in_array($shopId, $this->assignedShopIds($user->shops_ids), true);
        }

        if ((int) $user->id !== $adminId) {
            return false;
        }

        return Shop::query()
            ->whereKey($shopId)
            ->where('admin_id', $adminId)
            ->exists();
    }

    /**
     * @return Collection<int, Shop>
     */
    public function findAssignedShops(?string $rawShopIds): Collection
    {
        $shopIds = $this->assignedShopIds($rawShopIds);

        if ($shopIds === []) {
            return collect();
        }

        return Shop::query()
            ->whereIn('id', $shopIds)
            ->where('license_assigned', 1)
            ->where('type', 1)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{statusCode: int, message: string, data: null}
     */
    public function noAccessResponse(): array
    {
        return LegacyApiResponse::array(0, 'Fail you dont have access to view this resource');
    }

    public function parseDateTime(?string $date): ?CarbonImmutable
    {
        if (blank($date)) {
            return null;
        }

        $patterns = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d H:i',
            'Y-m-d\TH:i',
            'Y-m-d',
        ];

        foreach ($patterns as $pattern) {
            try {
                $parsed = CarbonImmutable::createFromFormat($pattern, trim($date), config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }

            if ($parsed !== false) {
                return $pattern === 'Y-m-d' ? $parsed->startOfDay() : $parsed;
            }
        }

        throw new RuntimeException('Invalid date format: '.$date);
    }

    public function generateInvoice(?int $adminId): string
    {
        return ((string) $adminId).now()->format('YmdHis');
    }

    /**
     * @return array<int, int>
     */
    private function assignedShopIds(?string $rawShopIds): array
    {
        if (blank($rawShopIds)) {
            return [];
        }

        $decoded = json_decode((string) $rawShopIds, true);

        if (! is_array($decoded)) {
            $decoded = explode(',', str_replace(['[', ']', '"', "'"], '', (string) $rawShopIds));
        }

        $ids = [];

        foreach ($decoded as $value) {
            $shopId = filter_var(trim((string) $value), FILTER_VALIDATE_INT);

            if ($shopId !== false && $shopId > 0) {
                $ids[] = $shopId;
            }
        }

        return array_values(array_unique($ids));
    }
}
