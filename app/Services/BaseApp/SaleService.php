<?php

namespace App\Services\BaseApp;

use App\Models\Account;
use App\Models\CustomerSupplierAccount;
use App\Models\LenderStatement;
use App\Models\Shop;
use App\Models\Shoping;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class SaleService
{
    public function __construct(
        private LegacyAccessService $access,
        private StockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function getReceiptItems(array $payload, ?string $token): array
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

        $items = Shoping::query()
            ->where('invoice', LegacyPayload::string($payload, 'search'))
            ->get();

        if ($items->isNotEmpty()) {
            return LegacyApiResponse::array(1, 'Success', $items);
        }

        return LegacyApiResponse::array(0, 'No Items found for this receipt', $items);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{body: array<string, mixed>, status: int}
     */
    public function getSales(array $payload): array
    {
        try {
            $fromDate = $this->access->parseDateTime(LegacyPayload::string($payload, 'fromDate'));
            $toDate = $this->access->parseDateTime(LegacyPayload::string($payload, 'toDate'));
            $from = $fromDate ?? CarbonImmutable::now()->subDays(7);
            $to = $toDate ?? CarbonImmutable::now();

            $this->validateDateRange($from, $to);

            $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
            $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');
            $hoursBetween = $this->hoursBetween($from, $to);
            $maxRecords = match (true) {
                $hoursBetween <= 1 => 100,
                $hoursBetween <= 24 => 1000,
                $hoursBetween <= 168 => 5000,
                default => 10000,
            };

            $count = $this->shoppingQuery($adminId, $shopId, $from, $to)->count();

            if ($count > $maxRecords) {
                return [
                    'body' => LegacyApiResponse::array(
                        0,
                        sprintf(
                            'Date range too large (%d hours). Maximum %d records allowed for this range.',
                            $hoursBetween,
                            $maxRecords,
                        ),
                    ),
                    'status' => 429,
                ];
            }

            return [
                'body' => LegacyApiResponse::array(1, 'success', $this->shoppingQuery($adminId, $shopId, $from, $to)->get()),
                'status' => 200,
            ];
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            return [
                'body' => LegacyApiResponse::array(0, $exception->getMessage(), null),
                'status' => 400,
            ];
        } catch (Throwable) {
            return [
                'body' => LegacyApiResponse::array(0, 'An unexpected error occurred', null),
                'status' => 500,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{body: array<string, mixed>, status: int}
     */
    public function count(array $query): array
    {
        try {
            if (blank($query['fromDate'] ?? null) || blank($query['toDate'] ?? null)) {
                return [
                    'body' => LegacyApiResponse::array(0, 'Both fromDate and toDate are required'),
                    'status' => 400,
                ];
            }

            $fromDate = $this->access->parseDateTime((string) $query['fromDate']);
            $toDate = $this->access->parseDateTime((string) $query['toDate']);

            return [
                'body' => [
                    'adminId' => isset($query['adminId']) ? (int) $query['adminId'] : null,
                    'shopId' => isset($query['shopId']) ? (int) $query['shopId'] : null,
                    'fromDate' => $fromDate?->format('Y-m-d\TH:i:s'),
                    'toDate' => $toDate?->format('Y-m-d\TH:i:s'),
                ],
                'status' => 200,
            ];
        } catch (Throwable $exception) {
            return [
                'body' => LegacyApiResponse::array(0, $exception->getMessage()),
                'status' => 500,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array{body: array<string, mixed>, status: int}
     */
    public function paginated(array $query): array
    {
        try {
            $adminId = isset($query['adminId']) ? (int) $query['adminId'] : null;
            $shopId = isset($query['shopId']) ? (int) $query['shopId'] : null;
            $fromDate = $this->access->parseDateTime((string) ($query['fromDate'] ?? ''));
            $toDate = $this->access->parseDateTime((string) ($query['toDate'] ?? ''));

            $this->validateDateRange($fromDate, $toDate);

            $allShopping = $this->shoppingQuery($adminId, $shopId, $fromDate, $toDate)->get();
            $total = $allShopping->count();

            if ($total > 10000) {
                return [
                    'body' => LegacyApiResponse::array(
                        0,
                        sprintf('Too many records (%d) in the specified date range. Maximum allowed: %d', $total, 10000),
                    ),
                    'status' => 429,
                ];
            }

            $page = isset($query['page']) ? max(0, (int) $query['page']) : 0;
            $size = isset($query['size']) ? max(1, (int) $query['size']) : 50;
            $fromIndex = min($page * $size, $total);
            $items = $allShopping->slice($fromIndex, $size)->values();

            return [
                'body' => [
                    'data' => LegacyApiResponse::serialize($items),
                    'page' => $page,
                    'size' => $size,
                    'total' => $total,
                    'hasNext' => ($fromIndex + $size) < $total,
                ],
                'status' => 200,
            ];
        } catch (Throwable $exception) {
            return [
                'body' => LegacyApiResponse::array(0, $exception->getMessage()),
                'status' => 500,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function create(array $payload, ?string $token): array
    {
        if (array_key_exists('data', $payload) && is_array($payload['data'])) {
            return $this->createTransaction($payload, $token);
        }

        return DB::transaction(fn (): array => $this->createSingleSale($payload, $token));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function performTransaction(array $payload): ?string
    {
        try {
            return Http::acceptJson()
                ->asJson()
                ->timeout(40)
                ->withOptions(['verify' => false])
                ->post((string) config('mauzo360.legacy_app_url'), $payload)
                ->body();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    private function createSingleSale(array $payload, ?string $token): array
    {
        $appId = LegacyPayload::integer($payload, 'appId', 'app_id');

        if ($appId !== null) {
            $existingTransaction = Shoping::query()
                ->where('admin_id', LegacyPayload::integer($payload, 'adminId', 'admin_id'))
                ->where('shop_id', LegacyPayload::integer($payload, 'shopId', 'shop_id'))
                ->where('app_id', $appId)
                ->first();

            if ($existingTransaction !== null) {
                return LegacyApiResponse::array(1, 'Already synced', $existingTransaction->id);
            }
        }

        $createdSale = $this->saveShoppingRow($payload, $token);

        return LegacyApiResponse::array(1, 'success', $createdSale->id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{statusCode: int, message: string, data: mixed}
     */
    private function createTransaction(array $payload, ?string $token): array
    {
        $items = LegacyPayload::list($payload, 'data');
        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');
        $invoice = LegacyPayload::string($payload, 'invoice');
        $custId = LegacyPayload::integer($payload, 'custId', 'cust_id') ?? 0;
        $paymentModes = LegacyPayload::integerList($payload, 'paymentMode', 'payment_mode');

        if ($items === []) {
            return LegacyApiResponse::array(0, 'No sale items provided');
        }

        if ($adminId === null || $shopId === null) {
            return LegacyApiResponse::array(0, 'adminId and shopId are required');
        }

        if (filled($invoice)) {
            $existing = Shoping::query()
                ->where('admin_id', $adminId)
                ->where('shop_id', $shopId)
                ->where('invoice', $invoice)
                ->get();

            if ($existing->isNotEmpty()) {
                return LegacyApiResponse::array(1, 'Already synced', $existing->pluck('id')->values()->all());
            }
        }

        if (in_array(0, $paymentModes, true) && $custId === 0) {
            return LegacyApiResponse::array(
                0,
                'Please select  a customer / supplier or add new before perform credit transaction',
            );
        }

        if ($paymentModes === []) {
            return LegacyApiResponse::array(0, 'Please select at least one payment method');
        }

        if (! $this->paymentAmountsMatchGrandTotal($payload, $paymentModes)) {
            return LegacyApiResponse::array(0, 'Payment amounts must equal grand total');
        }

        $customerName = CustomerSupplierAccount::query()
            ->whereKey($custId)
            ->value('name');

        if (filled($customerName)) {
            $payload['custName'] = $customerName;
        }

        if (blank(LegacyPayload::string($payload, 'custName', 'cust_name'))) {
            $payload['custName'] = 'Walk-in';
        }

        DB::beginTransaction();

        try {
            try {
                $this->performAccountingForPayment($payload, $paymentModes);
            } catch (Throwable) {
                DB::rollBack();

                return LegacyApiResponse::array(0, 'Failed to perform transaction! Try again later');
            }

            $createdIds = [];

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $createdIds[] = $this->saveShoppingRow(
                    $this->transactionItemToShoppingPayload($payload, $item, $paymentModes),
                    $token,
                )->id;
            }

            DB::commit();

            return LegacyApiResponse::array(1, 'success', $createdIds);
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function saveShoppingRow(array $payload, ?string $token): Shoping
    {
        if ($this->shouldDeductStock($payload)) {
            $remainingQty = $this->stockService->confirmAndDeductStock($payload, $token);
            $payload['remainingQty'] = $remainingQty;
        }

        return Shoping::query()->create([
            'type' => LegacyPayload::integer($payload, 'type'),
            'cust_id' => LegacyPayload::integer($payload, 'custId', 'cust_id'),
            'cust_name' => LegacyPayload::string($payload, 'custName', 'cust_name'),
            'stock_id' => LegacyPayload::integer($payload, 'stockId', 'stock_id'),
            'invoice' => LegacyPayload::string($payload, 'invoice'),
            'file' => LegacyPayload::string($payload, 'file'),
            'discount' => LegacyPayload::float($payload, 'discount'),
            'qty' => LegacyPayload::float($payload, 'qty'),
            'remaining_qty' => LegacyPayload::float($payload, 'remainingQty', 'remaining_qty'),
            'amount' => LegacyPayload::float($payload, 'amount'),
            'sale' => LegacyPayload::float($payload, 'sale'),
            'stock_name' => LegacyPayload::string($payload, 'stockName', 'stock_name'),
            'variant' => LegacyPayload::string($payload, 'variant'),
            'purchase_price' => LegacyPayload::float($payload, 'purchasePrice', 'purchase_price'),
            'credit_paid' => LegacyPayload::float($payload, 'creditPaid', 'credit_paid'),
            'credit_pending' => LegacyPayload::float($payload, 'creditPending', 'credit_pending'),
            'paydesc' => LegacyPayload::string($payload, 'paydesc'),
            'vat' => LegacyPayload::float($payload, 'vat'),
            'shipping' => LegacyPayload::float($payload, 'shipping'),
            'total' => LegacyPayload::float($payload, 'total'),
            'payment_mode' => LegacyPayload::integer($payload, 'paymentMode', 'payment_mode'),
            'payment_channels' => LegacyPayload::string($payload, 'paymentChannels', 'payment_channels'),
            'expr_date' => LegacyPayload::string($payload, 'exprDate', 'expr_date'),
            'details' => LegacyPayload::string($payload, 'details'),
            'tin' => LegacyPayload::string($payload, 'tin'),
            'user_id' => LegacyPayload::integer($payload, 'userId', 'user_id'),
            'admin_id' => LegacyPayload::integer($payload, 'adminId', 'admin_id'),
            'app_id' => LegacyPayload::integer($payload, 'appId', 'app_id'),
            'shop_id' => LegacyPayload::integer($payload, 'shopId', 'shop_id'),
            'app_sync' => LegacyPayload::integer($payload, 'appSync', 'app_sync'),
            'app_created_at' => LegacyPayload::string($payload, 'appCreatedAt', 'app_created_at'),
            'unit' => LegacyPayload::string($payload, 'unit'),
            'variant_id' => LegacyPayload::integer($payload, 'variantId', 'variant_id'),
            'item_discount' => LegacyPayload::float($payload, 'itemDiscount', 'item_discount'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $transaction
     * @param  array<string, mixed>  $item
     * @param  array<int, int>  $paymentModes
     * @return array<string, mixed>
     */
    private function transactionItemToShoppingPayload(array $transaction, array $item, array $paymentModes): array
    {
        $qty = LegacyPayload::float($item, 'qty') ?? 0.0;
        $salePrice = LegacyPayload::float($item, 'salePrice', 'sale_price') ?? 0.0;
        $itemDiscount = $this->itemDiscount($item);
        $itemTotal = LegacyPayload::float($item, 'totalPrice', 'total_price') ?? (($salePrice * $qty) - $itemDiscount);

        return [
            'type' => $this->firstNonNull(
                LegacyPayload::integer($item, 'type'),
                LegacyPayload::integer($transaction, 'type'),
                1,
            ),
            'custId' => LegacyPayload::integer($transaction, 'custId', 'cust_id'),
            'custName' => LegacyPayload::string($transaction, 'custName', 'cust_name'),
            'stockId' => LegacyPayload::integer($item, 'stockId', 'stock_id'),
            'invoice' => $this->firstNonBlank(
                LegacyPayload::string($transaction, 'invoice'),
                LegacyPayload::string($item, 'invoice'),
            ),
            'file' => LegacyPayload::string($item, 'file'),
            'discount' => $this->defaultDouble(LegacyPayload::float($transaction, 'discount')),
            'qty' => LegacyPayload::float($item, 'qty'),
            'remainingQty' => 0.0,
            'amount' => $itemTotal,
            'sale' => LegacyPayload::float($item, 'salePrice', 'sale_price'),
            'stockName' => LegacyPayload::string($item, 'name'),
            'variant' => LegacyPayload::string($item, 'variant'),
            'purchasePrice' => LegacyPayload::float($item, 'purchasePrice', 'purchase_price'),
            'creditPaid' => $this->nonCreditPaid($transaction),
            'creditPending' => $this->defaultDouble(LegacyPayload::float($transaction, 'receivableAmount', 'creditAmount')),
            'paydesc' => $this->paymentDescription($transaction),
            'vat' => $this->defaultDouble(LegacyPayload::float($transaction, 'vat')),
            'shipping' => $this->defaultDouble(LegacyPayload::float($transaction, 'shipping')),
            'total' => $itemTotal,
            'paymentMode' => $this->paymentMode($paymentModes),
            'paymentChannels' => $this->paymentChannels($paymentModes),
            'exprDate' => LegacyPayload::string($item, 'exprDate', 'expr_date'),
            'details' => $this->firstNonBlank(
                LegacyPayload::string($item, 'details'),
                LegacyPayload::string($transaction, 'details'),
            ),
            'tin' => LegacyPayload::string($transaction, 'tin'),
            'userId' => $this->firstNonNull(
                LegacyPayload::integer($item, 'userId', 'user_id'),
                LegacyPayload::integer($transaction, 'userId', 'user_id'),
            ),
            'adminId' => $this->firstNonNull(
                LegacyPayload::integer($item, 'adminId', 'admin_id'),
                LegacyPayload::integer($transaction, 'adminId', 'admin_id'),
            ),
            'shopId' => $this->firstNonNull(
                LegacyPayload::integer($item, 'shopId', 'shop_id'),
                LegacyPayload::integer($transaction, 'shopId', 'shop_id'),
            ),
            'appSync' => 1,
            'appCreatedAt' => $this->firstNonBlank(
                LegacyPayload::string($item, 'createdAt', 'created_at'),
                LegacyPayload::string($transaction, 'pDate'),
            ),
            'unit' => LegacyPayload::string($item, 'unit'),
            'variantId' => LegacyPayload::integer($item, 'varietyId', 'variantId', 'variety_id'),
            'itemDiscount' => $itemDiscount,
        ];
    }

    /**
     * @param  array<int, int>  $paymentModes
     */
    private function performAccountingForPayment(array $payload, array $paymentModes): void
    {
        foreach ($paymentModes as $mode) {
            $account = $this->accountForPaymentMode($payload, $mode);

            if ($account === null || (float) $account->amount <= 0) {
                continue;
            }

            $account->save();

            if ($mode === 0) {
                LenderStatement::query()->create($this->lenderStatementForCredit($payload));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function accountForPaymentMode(array $payload, int $mode): ?Account
    {
        $invoice = LegacyPayload::string($payload, 'invoice');
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');

        [$name, $type, $details, $reference, $amount] = match ($mode) {
            0 => [
                $this->firstNonBlank(LegacyPayload::string($payload, 'creditProvider'), 'Receivable'),
                'Credit',
                $this->shopName($shopId).'|'.$invoice,
                $this->firstNonBlank(LegacyPayload::string($payload, 'creditReference'), $invoice),
                $this->defaultDouble(LegacyPayload::float($payload, 'receivableAmount', 'creditAmount')),
            ],
            1 => [
                'Cash',
                'Cash',
                'Cash Payment',
                $invoice,
                $this->defaultDouble(LegacyPayload::float($payload, 'cashAmount')),
            ],
            2 => [
                $this->firstNonBlank(LegacyPayload::string($payload, 'bankId'), 'Bank'),
                'Bank',
                'shop Bank payment',
                $this->firstNonBlank(LegacyPayload::string($payload, 'bankReference'), $invoice),
                $this->defaultDouble(LegacyPayload::float($payload, 'bankAmount')),
            ],
            3 => [
                $this->firstNonBlank(LegacyPayload::string($payload, 'mobileProvider'), 'Mobile'),
                'Mobile',
                'Mobile payment',
                $this->firstNonBlank(LegacyPayload::string($payload, 'mobileReference'), $invoice),
                $this->defaultDouble(LegacyPayload::float($payload, 'mobileAmount')),
            ],
            4 => [
                'Mauzo360',
                'Mauzo360',
                'Mauzo360 Payment',
                $invoice,
                $this->defaultDouble(LegacyPayload::float($payload, 'mauzo360Amount')),
            ],
            default => [null, null, null, null, 0.0],
        };

        if ($name === null || $type === null) {
            return null;
        }

        $adminId = LegacyPayload::integer($payload, 'adminId', 'admin_id');
        $balance = $this->runningBalance($name, $adminId);
        $transactionType = LegacyPayload::integer($payload, 'type') ?? 0;
        $account = new Account([
            'type' => $type,
            'gl_type' => $mode === 0 ? $name : 'Income',
            'name' => $name,
            'date' => now()->format('Y-m-d H:i:s'),
            'shop_id' => $shopId,
            'details' => $details,
            'user_id' => LegacyPayload::integer($payload, 'userId', 'user_id') ?? $adminId,
            'admin_id' => $adminId,
            'amount' => $amount,
            'reference' => $reference,
            'acc_type' => $transactionType === 0 ? 'Purchase' : 'Sale',
        ]);

        if ($transactionType === 0) {
            $account->crdr = $mode === 0 ? 'CR' : 'DR';
            $account->balance = $mode === 0 ? $balance + $amount : $balance - $amount;
        } else {
            $account->crdr = $mode === 0 ? 'DR' : 'CR';
            $account->balance = $balance + $amount;
        }

        return $account;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function lenderStatementForCredit(array $payload): array
    {
        $receivableAmount = $this->defaultDouble(LegacyPayload::float($payload, 'receivableAmount', 'creditAmount'));
        $shopId = LegacyPayload::integer($payload, 'shopId', 'shop_id');
        $invoice = LegacyPayload::string($payload, 'invoice');
        $shopInvoice = $this->shopName($shopId).'|'.$invoice;
        $type = LegacyPayload::integer($payload, 'type') ?? 0;

        return [
            'date' => today()->toDateString(),
            'lender_id' => LegacyPayload::integer($payload, 'custId', 'cust_id'),
            'reference' => $invoice,
            'paydesc' => 'Inventory',
            'admin_id' => LegacyPayload::integer($payload, 'adminId', 'admin_id'),
            'shop_id' => $shopId,
            'user_id' => LegacyPayload::integer($payload, 'userId', 'user_id')
                ?? LegacyPayload::integer($payload, 'adminId', 'admin_id'),
            'is_collection' => 0,
            'details' => $type === 0 ? 'Credit purchase : '.$shopInvoice : 'Credit Sale : '.$shopInvoice,
            'type' => $type === 0 ? 1 : 0,
            'amount' => $this->javaDoubleString($type === 0 ? $receivableAmount : -$receivableAmount),
        ];
    }

    private function shoppingQuery(?int $adminId, ?int $shopId, CarbonImmutable $from, CarbonImmutable $to): mixed
    {
        return Shoping::query()
            ->where('admin_id', $adminId)
            ->where('shop_id', $shopId)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at');
    }

    private function validateDateRange(?CarbonImmutable $fromDate, ?CarbonImmutable $toDate): void
    {
        if ($fromDate === null || $toDate === null) {
            throw new \InvalidArgumentException('Both fromDate and toDate must be provided');
        }

        if ($fromDate->greaterThan($toDate)) {
            throw new \InvalidArgumentException('fromDate must be before toDate');
        }

        if ($fromDate->diffInDays($toDate) > 30) {
            throw new \InvalidArgumentException('Date range cannot exceed 30 days');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, int>  $paymentModes
     */
    private function paymentAmountsMatchGrandTotal(array $payload, array $paymentModes): bool
    {
        $sum = 0.0;

        foreach ($paymentModes as $mode) {
            $sum += $this->amountForPaymentMode($payload, $mode);
        }

        $grandTotal = $this->defaultDouble(LegacyPayload::float($payload, 'grandTotal', 'total'));

        return abs($sum - $grandTotal) <= 0.01;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function amountForPaymentMode(array $payload, int $mode): float
    {
        return match ($mode) {
            0 => $this->defaultDouble(LegacyPayload::float($payload, 'receivableAmount', 'creditAmount')),
            1 => $this->defaultDouble(LegacyPayload::float($payload, 'cashAmount')),
            2 => $this->defaultDouble(LegacyPayload::float($payload, 'bankAmount')),
            3 => $this->defaultDouble(LegacyPayload::float($payload, 'mobileAmount')),
            4 => $this->defaultDouble(LegacyPayload::float($payload, 'mauzo360Amount')),
            default => 0.0,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function itemDiscount(array $payload): float
    {
        return $this->defaultDouble($this->firstNonNull(
            LegacyPayload::float($payload, 'itemDiscount', 'item_discount'),
            LegacyPayload::float($payload, 'discount'),
        ));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nonCreditPaid(array $payload): float
    {
        return $this->defaultDouble(LegacyPayload::float($payload, 'cashAmount'))
            + $this->defaultDouble(LegacyPayload::float($payload, 'bankAmount'))
            + $this->defaultDouble(LegacyPayload::float($payload, 'mobileAmount'))
            + $this->defaultDouble(LegacyPayload::float($payload, 'mauzo360Amount'));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function paymentDescription(array $payload): string
    {
        $parts = [];
        $this->addPaymentPart($parts, 'Cash', LegacyPayload::float($payload, 'cashAmount'));
        $this->addPaymentPart($parts, 'Bank', LegacyPayload::float($payload, 'bankAmount'));
        $this->addPaymentPart($parts, 'Mobile', LegacyPayload::float($payload, 'mobileAmount'));
        $this->addPaymentPart($parts, 'Credit', LegacyPayload::float($payload, 'receivableAmount', 'creditAmount'));
        $this->addPaymentPart($parts, 'Mauzo360', LegacyPayload::float($payload, 'mauzo360Amount'));

        return implode(', ', $parts);
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function addPaymentPart(array &$parts, string $label, ?float $amount): void
    {
        if ($amount !== null && $amount > 0) {
            $parts[] = $label.': '.number_format($amount, 2, '.', '');
        }
    }

    /**
     * @param  array<int, int>  $paymentModes
     */
    private function paymentMode(array $paymentModes): int
    {
        if ($paymentModes === []) {
            return 1;
        }

        if (count($paymentModes) > 1) {
            return 5;
        }

        return $paymentModes[0];
    }

    /**
     * @param  array<int, int>  $paymentModes
     */
    private function paymentChannels(array $paymentModes): string
    {
        return '['.implode(', ', $paymentModes).']';
    }

    private function runningBalance(string $accountName, ?int $adminId): float
    {
        return (float) (Account::query()
            ->where('admin_id', $adminId)
            ->where('name', $accountName)
            ->orderByDesc('id')
            ->value('balance') ?? 0.0);
    }

    private function shopName(?int $shopId): string
    {
        if ($shopId === null) {
            return '';
        }

        return (string) (Shop::query()->whereKey($shopId)->value('name') ?? '');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shouldDeductStock(array $payload): bool
    {
        $type = LegacyPayload::integer($payload, 'type');

        return $type === null || $type === 1 || $type === 2;
    }

    private function defaultDouble(?float $value): float
    {
        return $value ?? 0.0;
    }

    private function firstNonNull(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function firstNonBlank(?string ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return $value;
            }
        }

        return null;
    }

    private function hoursBetween(CarbonImmutable $fromDate, CarbonImmutable $toDate): int
    {
        return (int) floor(($toDate->getTimestamp() - $fromDate->getTimestamp()) / 3600);
    }

    private function javaDoubleString(float $value): string
    {
        $string = (string) $value;

        return str_contains($string, '.') ? $string : $string.'.0';
    }
}
