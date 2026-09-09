<?php

namespace App\Services\BaseApp;

use App\Models\Expense;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;

class ExpenseService
{
    public function __construct(private LegacyAccessService $access) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getExpenses(array $payload, ?string $token): array
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

        $expenses = Expense::query()
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->get();

        return LegacyApiResponse::array(1, $expenses->isEmpty() ? 'No Expense available' : 'success', $expenses);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function create(array $payload, ?string $token): array
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

        $expense = Expense::query()->create([
            'name' => LegacyPayload::string($payload, 'name'),
            'amount' => LegacyPayload::string($payload, 'amount'),
            'details' => LegacyPayload::string($payload, 'details'),
            'type' => LegacyPayload::integer($payload, 'type'),
            'user_id' => $userId,
            'admin_id' => $adminId,
            'shop_id' => $shopId,
            'store_id' => LegacyPayload::integer($payload, 'storeId', 'store_id'),
            'user_name' => LegacyPayload::string($payload, 'userName', 'user_name'),
            'date' => LegacyPayload::string($payload, 'date'),
            'attachment' => LegacyPayload::string($payload, 'attachment'),
            'category' => LegacyPayload::string($payload, 'category'),
            'sync' => LegacyPayload::integer($payload, 'sync'),
            'paid_from' => LegacyPayload::string($payload, 'paidFrom', 'paid_from'),
            'project' => LegacyPayload::string($payload, 'project'),
            'VAT' => LegacyPayload::string($payload, 'vat', 'VAT'),
        ]);

        return LegacyApiResponse::array(1, 'success', $expense);
    }
}
