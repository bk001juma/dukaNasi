<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Expense;
use App\Models\Shop;
use App\Support\LegacyPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmployeeEndpointTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mauzo360.channel_token' => 'test-channel']);
    }

    public function test_owner_can_create_employee_for_shop_and_salary_expense(): void
    {
        [$owner, $shop] = $this->ownerWithShop();

        $response = $this->postJson('/api/v2/baseApp/create-employee', [
            'adminId' => $owner->id,
            'shopId' => $shop->id,
            'username' => 'amina-cashier',
            'password' => 'secret-password',
            'firstName' => 'Amina',
            'middleName' => 'Said',
            'lastName' => 'Juma',
            'phone' => '255712345678',
            'email' => 'amina@example.com',
            'salary' => 250000,
            'salaryPeriod' => 'monthly',
            'salaryDate' => '2026-09-01',
            'paidFrom' => 'Cash',
        ], $this->headers($owner));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.employee.username', 'amina-cashier')
            ->assertJsonPath('data.employee.active', 1)
            ->assertJsonPath('data.employee.adminId', $owner->id)
            ->assertJsonPath('data.employee.shopId', $shop->id)
            ->assertJsonPath('data.employee.userType', 'is_employee')
            ->assertJsonPath('data.employee.salary', '250000.00')
            ->assertJsonPath('data.salaryExpense.name', 'Salary')
            ->assertJsonPath('data.salaryExpense.category', 'Salary')
            ->assertJsonPath('data.salaryExpense.amount', '250000.00');

        $employee = Admin::query()->where('username', 'amina-cashier')->firstOrFail();

        $this->assertSame('250000.00', $employee->salary);
        $this->assertTrue(LegacyPassword::verify('secret-password', $employee->password));
        $this->assertDatabaseHas('admins', [
            'id' => $employee->id,
            'admin_id' => $owner->id,
            'shop_id' => $shop->id,
            'user_id' => $employee->id,
            'user_type' => 'is_employee',
            'shops_ids' => json_encode([(string) $shop->id]),
            'active' => 1,
            'salary_expense_id' => $response->json('data.salaryExpense.id'),
        ]);
        $this->assertDatabaseHas(Expense::class, [
            'id' => $response->json('data.salaryExpense.id'),
            'name' => 'Salary',
            'amount' => '250000.00',
            'details' => 'Monthly salary for Amina Said Juma',
            'type' => 1,
            'user_id' => $employee->id,
            'admin_id' => $owner->id,
            'shop_id' => $shop->id,
            'user_name' => 'Amina Said Juma',
            'date' => '2026-09-01',
            'category' => 'Salary',
            'sync' => 1,
            'paid_from' => 'Cash',
        ]);
    }

    public function test_v1_create_employee_endpoint_is_registered(): void
    {
        [$owner, $shop] = $this->ownerWithShop();

        $response = $this->postJson('/api/v1/baseApp/create-employee', [
            'adminId' => $owner->id,
            'shopId' => $shop->id,
            'username' => 'v1-employee',
            'password' => 'password',
            'firstName' => 'Vone',
            'lastName' => 'Worker',
            'phone' => '255700000001',
            'salary' => 150000,
        ], $this->headers($owner));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('data.employee.username', 'v1-employee');

        $this->assertDatabaseHas('admins', [
            'username' => 'v1-employee',
            'admin_id' => $owner->id,
            'shop_id' => $shop->id,
            'user_type' => 'is_employee',
            'active' => 1,
        ]);
    }

    public function test_owner_cannot_create_employee_for_another_owners_shop(): void
    {
        [$owner] = $this->ownerWithShop();
        [$anotherOwner, $anotherShop] = $this->ownerWithShop();

        $response = $this->postJson('/api/v2/baseApp/create-employee', [
            'adminId' => $owner->id,
            'shopId' => $anotherShop->id,
            'username' => 'wrong-shop-employee',
            'password' => 'password',
            'firstName' => 'Wrong',
            'lastName' => 'Shop',
            'phone' => '255700000002',
            'salary' => 100000,
        ], $this->headers($owner));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 0)
            ->assertJsonPath('message', 'Fail you dont have access to view this resource');

        $this->assertDatabaseMissing('admins', [
            'username' => 'wrong-shop-employee',
            'admin_id' => $owner->id,
            'shop_id' => $anotherShop->id,
        ]);
        $this->assertDatabaseMissing(Expense::class, [
            'admin_id' => $anotherOwner->id,
            'shop_id' => $anotherShop->id,
            'category' => 'Salary',
        ]);
    }

    public function test_employee_cannot_create_another_employee(): void
    {
        [$owner, $shop] = $this->ownerWithShop();
        $employee = Admin::factory()->employee($owner->id, [$shop->id])->create([
            'secret_key' => 'employee-token-'.Str::random(10),
            'shop_id' => $shop->id,
        ]);

        $response = $this->postJson('/api/v2/baseApp/create-employee', [
            'adminId' => $owner->id,
            'shopId' => $shop->id,
            'username' => 'second-employee',
            'password' => 'password',
            'firstName' => 'Second',
            'lastName' => 'Employee',
            'phone' => '255700000003',
            'salary' => 100000,
        ], $this->headers($employee));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 0)
            ->assertJsonPath('message', 'Fail you dont have access to view this resource');

        $this->assertDatabaseMissing('admins', [
            'username' => 'second-employee',
        ]);
    }

    public function test_returns_422_when_required_employee_fields_are_missing(): void
    {
        [$owner] = $this->ownerWithShop();

        $response = $this->postJson('/api/v2/baseApp/create-employee', [], $this->headers($owner));

        $response
            ->assertUnprocessable()
            ->assertJsonPath('statusCode', 0)
            ->assertJsonPath('message', 'The admin id field is required.')
            ->assertJsonPath('data.errors.adminId.0', 'The admin id field is required.')
            ->assertJsonPath('data.errors.salary.0', 'The salary field is required.');

        $this->assertDatabaseMissing('admins', [
            'user_type' => 'is_employee',
        ]);
    }

    /**
     * @return array{0: Admin, 1: Shop}
     */
    private function ownerWithShop(): array
    {
        $owner = Admin::factory()->create([
            'secret_key' => 'owner-token-'.Str::random(10),
        ]);
        $shop = Shop::factory()->create([
            'admin_id' => $owner->id,
        ]);

        return [$owner, $shop];
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
