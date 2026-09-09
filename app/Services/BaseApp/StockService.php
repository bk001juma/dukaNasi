<?php

namespace App\Services\BaseApp;

use App\Models\Stock;
use App\Models\StockVariant;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function __construct(private LegacyAccessService $access) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getStocks(array $payload, ?string $token): array
    {
        $user = $this->access->currentUserOrFail($token);
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');

        if ($adminId === null || $shopId === null) {
            return LegacyApiResponse::array(0, 'Fail invalid request');
        }

        if (! $this->access->canAccessShop($user, $adminId, $shopId)) {
            return $this->access->noAccessResponse();
        }

        $stocks = $this->availableStocks($shopId, $adminId);

        if ($stocks !== []) {
            return LegacyApiResponse::array(1, 'Success', $stocks);
        }

        return LegacyApiResponse::array(0, 'No Items found for this shop', $stocks);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function confirmAndDeductStock(array $payload, ?string $token): float
    {
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');
        $stockId = LegacyPayload::integer($payload, 'stockId', 'stock_id');
        $qty = LegacyPayload::float($payload, 'qty');

        if ($adminId === null || $shopId === null || $stockId === null) {
            throw new RuntimeException('adminId, shopId and stockId are required');
        }

        if ($qty === null || $qty <= 0) {
            throw new RuntimeException('Quantity must be greater than 0');
        }

        $user = $this->access->currentUserOrFail($token);

        if (! $this->access->canAccessShop($user, $adminId, $shopId)) {
            throw new RuntimeException('Fail you dont have access to view this resource');
        }

        $stock = Stock::query()
            ->whereKey($stockId)
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->first();

        if ($stock === null) {
            throw new RuntimeException('Stock not found on server');
        }

        if ((int) $stock->mode === 1) {
            return (float) $stock->stock;
        }

        $variantId = LegacyPayload::integer($payload, 'variantId', 'variant_id');

        if ($variantId !== null && $variantId > 0) {
            return $this->deductVariantStock($stock, $variantId, $stockId, $adminId, $shopId, $qty);
        }

        $currentQty = (float) ($stock->stock ?? 0);

        if ($currentQty < $qty) {
            throw new RuntimeException('Insufficient stock on server. Available: '.$currentQty);
        }

        $remainingQty = $currentQty - $qty;
        $stock->stock = $remainingQty;
        $this->updateStockStatus($stock, $remainingQty);
        $stock->app_updated_at ??= now();
        $stock->save();

        return $remainingQty;
    }

    public function updateItemQtyAndPrice(int $stockId, ?int $variantId = null): void
    {
        $stock = Stock::query()->whereKey($stockId)->first();

        if ($stock === null) {
            throw new RuntimeException('Stock not found for ID: '.$stockId);
        }

        $totals = StockVariant::query()
            ->where('stock_id', $stockId)
            ->selectRaw('SUM(qty) as total_qty, SUM(sale_price * qty) as total_sale, SUM(purchase_price * qty) as total_purchase')
            ->first();

        $totalQty = (float) ($totals?->total_qty ?? 0);

        $stock->stock = $totalQty;
        $stock->total_sale = $totals?->total_sale;
        $stock->total_purchase = $totals?->total_purchase;
        $this->updateStockStatus($stock, $totalQty);
        $stock->app_updated_at ??= now();
        $stock->save();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function availableStocks(int $shopId, int $adminId): array
    {
        return DB::table('stock as s')
            ->leftJoin('stock_variants as v', 's.stock_id', '=', 'v.stock_id')
            ->where('s.type', '<>', 2)
            ->whereRaw('COALESCE(v.qty, s.stock, 0) > 0')
            ->where('s.shop_id', $shopId)
            ->where('s.admin_id', $adminId)
            ->where('s.name', '<>', '')
            ->orderByDesc('s.created_at')
            ->get([
                's.stock_id',
                's.barcode',
                's.invoice',
                's.type',
                's.mode',
                's.name',
                DB::raw("CASE WHEN v.imgs IS NOT NULL AND v.imgs <> '' THEN v.imgs WHEN s.img IS NOT NULL AND s.img <> '' THEN s.img ELSE s.imgs END as img"),
                's.stock',
                's.qty_mapping',
                's.qty_mapping_unit',
                's.qty_actual',
                's.cat_id',
                's.purchase_price',
                's.sale_price',
                's.sale_robo_price',
                's.sale_nusu_price',
                's.sale_nusurobo_price',
                's.min_stock',
                's.unit',
                's.status',
                's.expr_date',
                's.app_updated_at',
                's.created_at',
                's.updated_at',
                's.store_id',
                's.app_id',
                's.shop_id',
                's.admin_id',
                's.origin_stock',
                DB::raw("CASE WHEN s.category = '' OR s.category IS NULL THEN 'Uncategorized' ELSE s.category END as category"),
                's.sub_category',
                DB::raw("CASE WHEN v.imgs IS NOT NULL AND v.imgs <> '' THEN v.imgs ELSE s.imgs END as imgs"),
                's.brand',
                's.total_sale',
                's.total_purchase',
                'v.color as variety_color',
                'v.size as variety_size',
                'v.id as variety_id',
                'v.brand as variety_brand',
                'v.sale_price as variety_sale_price',
                'v.purchase_price as variety_purchase_price',
                'v.qty as variety_qty',
            ])
            ->map(fn (object $row): array => [
                'stockId' => $row->stock_id,
                'barcode' => $row->barcode,
                'invoice' => $row->invoice,
                'type' => $row->type,
                'mode' => $row->mode,
                'name' => $row->name,
                'img' => $row->img,
                'stock' => $this->floatOrNull($row->stock),
                'qtyMapping' => $row->qty_mapping,
                'qtyMappingUnit' => $row->qty_mapping_unit,
                'qtyActual' => $row->qty_actual,
                'catId' => $row->cat_id,
                'purchasePrice' => $this->floatOrNull($row->purchase_price),
                'salePrice' => $this->floatOrNull($row->sale_price),
                'saleRoboPrice' => $this->floatOrNull($row->sale_robo_price),
                'saleNusuPrice' => $this->floatOrNull($row->sale_nusu_price),
                'saleNusuroboPrice' => $this->floatOrNull($row->sale_nusurobo_price),
                'minStock' => $row->min_stock,
                'unit' => $row->unit,
                'status' => $row->status,
                'exprDate' => $row->expr_date,
                'appUpdatedAt' => $row->app_updated_at,
                'createdAt' => $row->created_at,
                'updatedAt' => $row->updated_at,
                'storeId' => $row->store_id,
                'appId' => $row->app_id,
                'shopId' => $row->shop_id,
                'adminId' => $row->admin_id,
                'originStock' => $row->origin_stock,
                'category' => $row->category,
                'subCategory' => $row->sub_category,
                'imgs' => $row->imgs,
                'brand' => $row->brand,
                'totalSale' => $this->floatOrNull($row->total_sale),
                'totalPurchase' => $this->floatOrNull($row->total_purchase),
                'varietyColor' => $row->variety_color,
                'varietySize' => $row->variety_size,
                'varietyId' => $row->variety_id === null ? null : (string) $row->variety_id,
                'varietyBrand' => $row->variety_brand,
                'varietySalePrice' => $row->variety_sale_price === null ? null : (string) $row->variety_sale_price,
                'varietyPurchasePrice' => $row->variety_purchase_price === null ? null : (string) $row->variety_purchase_price,
                'varietyQty' => $row->variety_qty === null ? null : (string) $row->variety_qty,
            ])
            ->all();
    }

    private function deductVariantStock(
        Stock $stock,
        int $variantId,
        int $stockId,
        int $adminId,
        int $shopId,
        float $qty,
    ): float {
        $variant = StockVariant::query()
            ->whereKey($variantId)
            ->where('stock_id', $stockId)
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->first();

        if ($variant === null) {
            throw new RuntimeException('Stock variant not found on server');
        }

        $currentQty = (float) ($variant->qty ?? 0);

        if ($currentQty < $qty) {
            throw new RuntimeException('Insufficient variant stock on server. Available: '.$currentQty);
        }

        $remainingQty = $currentQty - $qty;
        $variant->qty = $remainingQty;
        $variant->save();

        $this->updateItemQtyAndPrice($stockId, $variantId);

        return $remainingQty;
    }

    private function updateStockStatus(Stock $stock, float $remainingQty): void
    {
        $stock->type = $remainingQty <= 0 ? 1 : 0;
        $stock->status = $remainingQty <= (float) $stock->min_stock ? 1 : 0;
    }

    private function floatOrNull(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
