<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Admin;
use App\Models\CustomerSupplierAccount;
use App\Models\LenderStatement;
use App\Models\Shop;
use App\Models\Stock;
use App\Models\StockVariant;
use App\Support\LegacyPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BaseAppEndpointTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mauzo360.channel_token' => 'test-channel']);
    }

    public function test_returns_401_when_api_key_is_missing_from_login(): void
    {
        $response = $this->postJson('/api/v2/baseApp/login', [
            'username' => 'owner',
            'password' => 'password',
        ], [
            'username' => 'owner',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('statusCode', 0)
            ->assertJsonPath('message', 'Invalid or missing correct Api key to access this service')
            ->assertJsonPath('data', null);
    }

    public function test_login_returns_success_and_persists_secret_key(): void
    {
        $admin = Admin::factory()->create([
            'username' => 'owner-login',
            'password' => LegacyPassword::make('secret'),
            'secret_key' => null,
        ]);

        $response = $this->postJson('/api/v2/baseApp/login', [
            'username' => 'owner-login',
            'password' => 'secret',
        ], [
            'X-Api-Key' => 'test-channel',
            'username' => 'owner-login',
            'deviceId' => 'phone-1',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'Success');

        $this->assertNotNull($admin->refresh()->secret_key);
        $this->assertSame($admin->secret_key, $response->json('data.secretKey'));
    }

    public function test_get_stocks_returns_legacy_stock_variant_projection(): void
    {
        [$admin, $shop] = $this->ownerWithShop();
        $stock = Stock::factory()->create([
            'admin_id' => $admin->id,
            'shop_id' => $shop->id,
            'name' => 'Sugar',
            'stock' => 5,
            'category' => null,
        ]);
        $variant = StockVariant::factory()->create([
            'stock_id' => $stock->stock_id,
            'admin_id' => $admin->id,
            'shop_id' => $shop->id,
            'color' => 'white',
            'size' => '1kg',
            'qty' => 5,
        ]);

        $response = $this->postJson('/api/v2/baseApp/get-stocks', [
            'adminId' => $admin->id,
            'shopId' => $shop->id,
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('data.0.stockId', $stock->stock_id)
            ->assertJsonPath('data.0.category', 'Uncategorized')
            ->assertJsonPath('data.0.varietyId', (string) $variant->id)
            ->assertJsonPath('data.0.varietyColor', 'white');
    }

    public function test_create_sale_deducts_stock_and_returns_created_id(): void
    {
        [$admin, $shop] = $this->ownerWithShop();
        $stock = Stock::factory()->create([
            'admin_id' => $admin->id,
            'shop_id' => $shop->id,
            'stock' => 5,
            'min_stock' => 1,
            'name' => 'Soap',
        ]);

        $response = $this->postJson('/api/v2/baseApp/create-sale', [
            'type' => 1,
            'stockId' => $stock->stock_id,
            'invoice' => 'INV-1',
            'qty' => 2,
            'amount' => 2000,
            'sale' => 1000,
            'stockName' => 'Soap',
            'purchasePrice' => 700,
            'total' => 2000,
            'paymentMode' => 1,
            'userId' => $admin->id,
            'adminId' => $admin->id,
            'shopId' => $shop->id,
            'unit' => 'pcs',
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success');

        $this->assertDatabaseHas('shopings', [
            'invoice' => 'INV-1',
            'remaining_qty' => 3,
            'admin_id' => $admin->id,
            'shop_id' => $shop->id,
        ]);
        $this->assertDatabaseHas('stock', [
            'stock_id' => $stock->stock_id,
            'stock' => 3,
        ]);
    }

    public function test_create_transaction_writes_accounts_lender_statement_and_sale_items(): void
    {
        [$admin, $shop] = $this->ownerWithShop();
        $stock = Stock::factory()->create([
            'admin_id' => $admin->id,
            'shop_id' => $shop->id,
            'stock' => 3,
            'name' => 'Rice',
        ]);
        $customer = CustomerSupplierAccount::factory()->create([
            'admin_id' => $admin->id,
            'shop_id' => $shop->id,
            'name' => 'Amina',
            'dealer' => 0,
        ]);

        $response = $this->postJson('/api/v2/baseApp/push-transaction', [
            'adminId' => $admin->id,
            'shopId' => $shop->id,
            'userId' => $admin->id,
            'custId' => $customer->id,
            'paymentMode' => [1, 0],
            'cashAmount' => 60,
            'receivableAmount' => 40,
            'grandTotal' => 100,
            'invoice' => 'INV-CREDIT-1',
            'type' => 1,
            'data' => [
                [
                    'stockId' => $stock->stock_id,
                    'name' => 'Rice',
                    'qty' => 1,
                    'salePrice' => 100,
                    'purchasePrice' => 70,
                    'totalPrice' => 100,
                    'unit' => 'kg',
                ],
            ],
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success');

        $this->assertDatabaseHas('shopings', [
            'invoice' => 'INV-CREDIT-1',
            'cust_name' => 'Amina',
            'credit_paid' => 60,
            'credit_pending' => 40,
            'payment_mode' => 5,
            'payment_channels' => '[1, 0]',
        ]);
        $this->assertDatabaseHas(Account::class, [
            'reference' => 'INV-CREDIT-1',
            'name' => 'Cash',
            'amount' => 60,
            'crdr' => 'CR',
        ]);
        $this->assertDatabaseHas(Account::class, [
            'reference' => 'INV-CREDIT-1',
            'name' => 'Receivable',
            'amount' => 40,
            'crdr' => 'DR',
        ]);
        $this->assertDatabaseHas(LenderStatement::class, [
            'reference' => 'INV-CREDIT-1',
            'lender_id' => $customer->id,
            'type' => 0,
            'amount' => '-40.0',
        ]);
    }

    public function test_delete_expense_returns_204_no_content(): void
    {
        [$admin] = $this->ownerWithShop();

        $response = $this->postJson('/api/v2/baseApp/delete-expense', [], $this->headers($admin));

        $response->assertNoContent();
    }

    public function test_get_sales_accepts_date_only_filters_like_the_spring_endpoint(): void
    {
        [$admin, $shop] = $this->ownerWithShop();

        $response = $this->postJson('/api/v2/baseApp/get-sales', [
            'adminId' => $admin->id,
            'shopId' => $shop->id,
            'fromDate' => now()->subDay()->toDateString(),
            'toDate' => now()->addDay()->toDateString(),
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success');
    }

    /**
     * @return array{0: Admin, 1: Shop}
     */
    private function ownerWithShop(): array
    {
        $admin = Admin::factory()->create([
            'secret_key' => 'owner-token-'.Str::random(10),
        ]);
        $shop = Shop::factory()->create([
            'admin_id' => $admin->id,
        ]);

        return [$admin, $shop];
    }

    /**
     * @return array<string, string>
     */
    private function headers(Admin $admin): array
    {
        return [
            'X-Api-Key' => 'test-channel',
            'token' => (string) $admin->secret_key,
            'username' => (string) $admin->username,
        ];
    }
}
