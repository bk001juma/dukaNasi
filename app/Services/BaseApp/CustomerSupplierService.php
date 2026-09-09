<?php

namespace App\Services\BaseApp;

use App\Models\CustomerSupplierAccount;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;

class CustomerSupplierService
{
    private const CUSTOMER = 0;

    private const SUPPLIER = 1;

    private const CUSTOMER_LENDER_TYPE = 2;

    private const SUPPLIER_LENDER_TYPE = 1;

    public function __construct(private LegacyAccessService $access) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getCustomers(array $payload, ?string $token): array
    {
        return $this->getAccounts($payload, $token, self::CUSTOMER, 'customers');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getSuppliers(array $payload, ?string $token): array
    {
        return $this->getAccounts($payload, $token, self::SUPPLIER, 'suppliers');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function createCustomer(array $payload, ?string $token): array
    {
        $payload['isCustomer'] = self::CUSTOMER;

        return $this->createAccount($payload, $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function createSupplier(array $payload, ?string $token): array
    {
        $payload['isCustomer'] = self::SUPPLIER;

        return $this->createAccount($payload, $token);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    private function getAccounts(array $payload, ?string $token, int $isCustomer, string $label): array
    {
        $user = $this->access->currentUserOrFail($token);
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');
        $userId = LegacyPayload::integer($payload, 'userId', 'user_id');

        if ($adminId === null || $shopId === null) {
            return LegacyApiResponse::array(0, 'adminId and shopId are required');
        }

        if (! $this->access->canAccessShop($user, $adminId, $shopId, $userId)) {
            return $this->access->noAccessResponse();
        }

        $accounts = CustomerSupplierAccount::query()
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->where('dealer', $isCustomer)
            ->orderBy('name')
            ->get();

        return LegacyApiResponse::array(1, $accounts->isEmpty() ? 'No '.$label.' found' : 'Success', $accounts);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    private function createAccount(array $payload, ?string $token): array
    {
        $user = $this->access->currentUserOrFail($token);
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');
        $userId = LegacyPayload::integer($payload, 'userId', 'user_id');

        if ($adminId === null || $shopId === null || $userId === null) {
            return LegacyApiResponse::array(0, 'adminId, shopId and userId are required');
        }

        if (! $this->access->canAccessShop($user, $adminId, $shopId, $userId)) {
            return $this->access->noAccessResponse();
        }

        $name = trim((string) LegacyPayload::string($payload, 'name'));
        $phone = trim((string) LegacyPayload::string($payload, 'phone'));

        if ($name === '' || $phone === '') {
            return LegacyApiResponse::array(0, 'Name and phone are required');
        }

        $isCustomer = LegacyPayload::integer($payload, 'isCustomer', 'is_customer') ?? self::CUSTOMER;
        $account = CustomerSupplierAccount::query()
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->where('dealer', $isCustomer)
            ->whereRaw('LOWER(phone) = ?', [mb_strtolower($phone)])
            ->first() ?? new CustomerSupplierAccount;

        $account->fill([
            'name' => $name,
            'phone' => $phone,
            'dealer' => $isCustomer,
            'type' => $isCustomer === self::CUSTOMER ? self::CUSTOMER_LENDER_TYPE : self::SUPPLIER_LENDER_TYPE,
            'email' => $this->trimToNull(LegacyPayload::string($payload, 'email')),
            'admin_id' => $adminId,
            'shop_id' => $shopId,
            'address' => $this->trimToNull(LegacyPayload::string($payload, 'address')),
            'location' => $this->trimToNull(LegacyPayload::string($payload, 'location')),
            'details' => $this->trimToNull(LegacyPayload::string($payload, 'details')),
        ]);
        $account->save();

        $account->setAttribute('profile_image', $this->trimToNull(LegacyPayload::string($payload, 'profileImage', 'profile_image')));
        $account->setAttribute('user_id', $userId);
        $account->setAttribute('sync', LegacyPayload::integer($payload, 'sync'));
        $account->setAttribute('status', LegacyPayload::integer($payload, 'status'));

        return LegacyApiResponse::array(1, 'Success', $account);
    }

    private function trimToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
