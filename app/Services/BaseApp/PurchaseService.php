<?php

namespace App\Services\BaseApp;

use App\Models\Shoping;
use App\Models\Stock;
use App\Models\StockVariant;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseService
{
    private const PURCHASE_TYPE = 0;

    public function __construct(
        private LegacyAccessService $access,
        private StockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function createPurchase(array $payload, ?string $token): array
    {
        return DB::transaction(function () use ($payload, $token): array {
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

            $payload['stockId'] = LegacyPayload::integer($payload, 'stockId', 'stock_id') ?? 0;

            if ($payload['stockId'] === 0 && filled(LegacyPayload::string($payload, 'name'))) {
                $payload['stockId'] = -1;
            }

            if ($payload['stockId'] !== -1 && ! Stock::query()
                ->whereKey($payload['stockId'])
                ->where('admin_id', $adminId)
                ->where('shop_id', $shopId)
                ->exists()) {
                return LegacyApiResponse::array(0, 'Stock not found for this shop');
            }

            $this->normalizePurchasePayload($payload);

            $purchase = $this->performPurchaseTransaction(
                $payload,
                self::PURCHASE_TYPE,
                LegacyPayload::string($payload, 'file'),
                LegacyPayload::string($payload, 'customerName', 'customer_name'),
                $shopId,
                LegacyPayload::integer($payload, 'paymentMode', 'payment_mode') ?? 0,
                LegacyPayload::string($payload, 'paymentChannels', 'payment_channels'),
            );

            return LegacyApiResponse::array(1, 'Success', $purchase);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getPurchases(array $payload, ?string $token): array
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

        $fromDate = $this->access->parseDateTime(LegacyPayload::string($payload, 'fromDate'));
        $toDate = $this->access->parseDateTime(LegacyPayload::string($payload, 'toDate'));
        $from = $fromDate ?? now()->subDays(7);
        $to = $toDate ?? now();
        $purchases = Shoping::query()
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->where('type', self::PURCHASE_TYPE)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->get();

        return LegacyApiResponse::array(1, 'Success', $purchases);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function performPurchaseTransaction(
        array $payload,
        int $type,
        ?string $filename,
        ?string $customerName,
        int $shopId,
        int $paymentMode,
        ?string $channels,
    ): Shoping {
        if ((int) LegacyPayload::integer($payload, 'stockId', 'stock_id') !== -1) {
            return $this->purchaseExistingStock($payload, $type, $filename, $customerName, $paymentMode, $channels);
        }

        return $this->purchaseNewStock($payload, $type, $filename, $customerName, $shopId, $paymentMode, $channels);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function purchaseExistingStock(
        array $payload,
        int $type,
        ?string $filename,
        ?string $customerName,
        int $paymentMode,
        ?string $channels,
    ): Shoping {
        $stockId = LegacyPayload::integer($payload, 'stockId', 'stock_id');
        $stock = Stock::query()->whereKey($stockId)->first();

        if ($stock === null) {
            throw new RuntimeException('Stock not found');
        }

        $qty = LegacyPayload::float($payload, 'qty') ?? 0.0;

        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than 0');
        }

        $invoice = $this->access->generateInvoice(LegacyPayload::integer($payload, 'adminId', 'admin_id'));
        $variant = $this->updateOrCreateVariant($payload, $this->resolveVariant($payload));
        $entity = Shoping::query()->create([
            ...$this->purchaseShoppingAttributes($payload, $type, $filename, $customerName, $paymentMode, $channels, $invoice),
            'stock_id' => $stockId,
            'remaining_qty' => ((float) $stock->stock) + $qty,
            'created_at' => $this->resolvePurchaseDate($payload),
            'stock_name' => $this->buildStockName($payload),
            'variant_id' => $variant->id,
        ]);

        $this->stockService->updateItemQtyAndPrice($stockId, $variant->id);
        $this->updateStockImageIfProvided($stockId, LegacyPayload::string($payload, 'img'));

        return $entity;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function purchaseNewStock(
        array $payload,
        int $type,
        ?string $filename,
        ?string $customerName,
        int $shopId,
        int $paymentMode,
        ?string $channels,
    ): Shoping {
        $qty = LegacyPayload::float($payload, 'qty') ?? 0.0;

        if ($qty <= 0) {
            throw new RuntimeException('0 quantity is not allowed');
        }

        if (blank(LegacyPayload::string($payload, 'name'))) {
            throw new RuntimeException('Stock name cannot be blank');
        }

        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $invoice = $this->access->generateInvoice($adminId);
        $newStock = Stock::query()->create([
            'name' => LegacyPayload::string($payload, 'name'),
            'stock' => $qty,
            'unit' => LegacyPayload::string($payload, 'unit'),
            'barcode' => LegacyPayload::string($payload, 'barcode'),
            'img' => LegacyPayload::string($payload, 'img'),
            'purchase_price' => LegacyPayload::float($payload, 'purchasePrice', 'purchase_price') ?? 0.0,
            'sale_price' => LegacyPayload::float($payload, 'salePrice', 'sale_price') ?? 0.0,
            'min_stock' => 1,
            'mode' => 0,
            'status' => 0,
            'type' => 0,
            'shop_id' => $shopId,
            'category' => LegacyPayload::string($payload, 'category'),
            'brand' => LegacyPayload::string($payload, 'brand'),
            'admin_id' => $adminId,
            'invoice' => $invoice,
            'expr_date' => LegacyPayload::string($payload, 'exprDate', 'expr_date'),
            'app_id' => 0,
            'origin_stock' => (int) round($qty),
            'app_updated_at' => now(),
        ]);

        $entity = Shoping::query()->create([
            ...$this->purchaseShoppingAttributes($payload, $type, $filename, $customerName, $paymentMode, $channels, $invoice),
            'stock_id' => $newStock->stock_id,
            'remaining_qty' => $qty,
            'paydesc' => 'admin-purchase',
            'expr_date' => LegacyPayload::string($payload, 'expr'),
            'created_at' => now(),
            'stock_name' => $this->buildStockName($payload),
        ]);

        if ($this->hasVariant($payload)) {
            $variant = StockVariant::query()->create([
                'stock_id' => $newStock->stock_id,
                'type' => LegacyPayload::string($payload, 'type'),
                'size' => LegacyPayload::string($payload, 'size'),
                'color' => LegacyPayload::string($payload, 'color'),
                'brand' => LegacyPayload::string($payload, 'brand'),
                'imgs' => LegacyPayload::string($payload, 'img'),
                'qty' => $qty,
                'purchase_price' => LegacyPayload::float($payload, 'purchasePrice', 'purchase_price') ?? 0.0,
                'sale_price' => LegacyPayload::float($payload, 'salePrice', 'sale_price') ?? 0.0,
                'shop_id' => $shopId,
                'admin_id' => $adminId,
            ]);

            $entity->variant_id = $variant->id;
            $entity->save();
            $this->stockService->updateItemQtyAndPrice($newStock->stock_id, $variant->id);
        }

        return $entity;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function purchaseShoppingAttributes(
        array $payload,
        int $type,
        ?string $filename,
        ?string $customerName,
        int $paymentMode,
        ?string $channels,
        string $invoice,
    ): array {
        $qty = LegacyPayload::float($payload, 'qty') ?? 0.0;
        $discount = LegacyPayload::float($payload, 'discount') ?? 0.0;
        $totalPrice = LegacyPayload::float($payload, 'totalPrice', 'total_price') ?? 0.0;

        return [
            'type' => $type,
            'qty' => $qty,
            'details' => $this->buildDetails($payload),
            'amount' => $totalPrice,
            'payment_mode' => $paymentMode,
            'payment_channels' => $channels,
            'shop_id' => LegacyPayload::integer($payload, 'shopId', 'shop_id'),
            'admin_id' => LegacyPayload::integer($payload, 'adminId', 'admin_id'),
            'user_id' => LegacyPayload::integer($payload, 'userId', 'user_id'),
            'sale' => LegacyPayload::float($payload, 'salePrice', 'sale_price'),
            'purchase_price' => LegacyPayload::float($payload, 'purchasePrice', 'purchase_price'),
            'item_discount' => $discount * $qty,
            'credit_paid' => LegacyPayload::float($payload, 'creditPaid', 'credit_paid'),
            'credit_pending' => LegacyPayload::float($payload, 'creditPending', 'credit_pending'),
            'paydesc' => 'admin-purchases',
            'vat' => 0.0,
            'discount' => $discount,
            'total' => $totalPrice - $discount,
            'tin' => LegacyPayload::string($payload, 'tin'),
            'shipping' => LegacyPayload::float($payload, 'shipping'),
            'file' => $filename,
            'cust_name' => $customerName,
            'cust_id' => LegacyPayload::integer($payload, 'custId', 'cust_id'),
            'invoice' => $invoice,
            'expr_date' => LegacyPayload::string($payload, 'exprDate', 'expr_date'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function updateOrCreateVariant(array $payload, ?StockVariant $variant): StockVariant
    {
        if ($variant === null) {
            return StockVariant::query()->create([
                'stock_id' => LegacyPayload::integer($payload, 'stockId', 'stock_id'),
                'shop_id' => LegacyPayload::integer($payload, 'shopId', 'shop_id'),
                'admin_id' => LegacyPayload::integer($payload, 'adminId', 'admin_id'),
                'type' => LegacyPayload::string($payload, 'type'),
                'size' => LegacyPayload::string($payload, 'size'),
                'color' => LegacyPayload::string($payload, 'color'),
                'brand' => LegacyPayload::string($payload, 'brand'),
                'imgs' => LegacyPayload::string($payload, 'img'),
                'purchase_price' => LegacyPayload::float($payload, 'purchasePrice', 'purchase_price') ?? 0.0,
                'sale_price' => LegacyPayload::float($payload, 'salePrice', 'sale_price') ?? 0.0,
                'qty' => LegacyPayload::float($payload, 'qty') ?? 0.0,
            ]);
        }

        $variant->qty = ((float) $variant->qty) + (LegacyPayload::float($payload, 'qty') ?? 0.0);

        if (filled(LegacyPayload::string($payload, 'type'))) {
            $variant->type = trim((string) LegacyPayload::string($payload, 'type'));
        }

        $variant->purchase_price = LegacyPayload::float($payload, 'purchasePrice', 'purchase_price') ?? 0.0;
        $variant->sale_price = LegacyPayload::float($payload, 'salePrice', 'sale_price') ?? 0.0;

        if (filled(LegacyPayload::string($payload, 'img'))) {
            $variant->imgs = trim((string) LegacyPayload::string($payload, 'img'));
        }

        $variant->save();

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolveVariant(array $payload): ?StockVariant
    {
        $varietyId = LegacyPayload::integer($payload, 'varietyId', 'variantId', 'variety_id');

        if ($varietyId === null || $varietyId === 0) {
            return null;
        }

        return StockVariant::query()
            ->whereKey($varietyId)
            ->where('brand', LegacyPayload::string($payload, 'brand'))
            ->where('color', LegacyPayload::string($payload, 'color'))
            ->where('size', LegacyPayload::string($payload, 'size'))
            ->first();
    }

    private function updateStockImageIfProvided(?int $stockId, ?string $image): void
    {
        if ($stockId === null || blank($image)) {
            return;
        }

        $stock = Stock::query()->whereKey($stockId)->first();

        if ($stock === null) {
            return;
        }

        $stock->img = trim($image);
        $stock->app_updated_at ??= now();
        $stock->save();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resolvePurchaseDate(array $payload): mixed
    {
        $purchaseDate = LegacyPayload::string($payload, 'pdate');

        if (blank($purchaseDate)) {
            return now();
        }

        return $this->access->parseDateTime($purchaseDate) ?? now();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function normalizePurchasePayload(array &$payload): void
    {
        $qty = LegacyPayload::float($payload, 'qty') ?? 0.0;

        if ($qty <= 0) {
            throw new RuntimeException('Quantity must be greater than 0');
        }

        $totalPrice = LegacyPayload::float($payload, 'totalPrice', 'total_price') ?? 0.0;

        if ($totalPrice <= 0) {
            $payload['totalPrice'] = $qty * (LegacyPayload::float($payload, 'purchasePrice', 'purchase_price') ?? 0.0);
        }

        if (blank(LegacyPayload::string($payload, 'unit'))) {
            $payload['unit'] = 'pcs';
        }

        if (LegacyPayload::get($payload, 'details') === null) {
            $payload['details'] = '';
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildDetails(array $payload): string
    {
        return (string) LegacyPayload::string($payload, 'details')
            .' type: '.LegacyPayload::string($payload, 'type')
            .' size: '.LegacyPayload::string($payload, 'size')
            .' color: '.LegacyPayload::string($payload, 'color')
            .' brand: '.LegacyPayload::string($payload, 'brand');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function buildStockName(array $payload): ?string
    {
        $name = LegacyPayload::string($payload, 'name');

        foreach (['type', 'size', 'color', 'brand'] as $field) {
            $value = LegacyPayload::string($payload, $field);

            if (filled($value)) {
                $name = ($name ?? '').' | '.$value;
            }
        }

        return $name;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hasVariant(array $payload): bool
    {
        return filled(LegacyPayload::string($payload, 'type'))
            || filled(LegacyPayload::string($payload, 'size'))
            || filled(LegacyPayload::string($payload, 'color'))
            || filled(LegacyPayload::string($payload, 'brand'));
    }
}
