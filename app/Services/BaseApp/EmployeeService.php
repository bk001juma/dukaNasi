<?php

namespace App\Services\BaseApp;

use App\Models\Admin;
use App\Models\Expense;
use App\Models\Shop;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPassword;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmployeeService
{
    private const EMPLOYEE_USER_TYPE = 'is_employee';

    private const DEFAULT_SALARY_PERIOD = 'monthly';

    private const SALARY_EXPENSE_NAME = 'Salary';

    private const SALARY_EXPENSE_TYPE = 1;

    public function __construct(private LegacyAccessService $access) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{statusCode: int, message: string, data: mixed}
     */
    public function create(array $data, ?string $token): array
    {
        $owner = $this->access->currentUserFromToken($token);

        if ($owner === null) {
            return LegacyApiResponse::array(0, 'Fail invalid request');
        }

        $adminId = (int) $data['adminId'];
        $shopId = (int) $data['shopId'];

        if ($owner->user_type !== 'is_owner' || (int) $owner->id !== $adminId) {
            return $this->access->noAccessResponse();
        }

        $shop = Shop::query()
            ->whereKey($shopId)
            ->where('admin_id', $adminId)
            ->where('status', 1)
            ->first();

        if ($shop === null) {
            return $this->access->noAccessResponse();
        }

        [$employee, $salaryExpense] = DB::transaction(function () use ($data, $owner, $adminId, $shopId): array {
            $salary = $this->formatSalaryAmount($data['salary']);
            $employee = Admin::query()->create([
                'username' => (string) $data['username'],
                'password' => LegacyPassword::make((string) $data['password']),
                'email' => $data['email'] ?? null,
                'active' => 1,
                'first_name' => (string) $data['firstName'],
                'middlename' => $data['middleName'] ?? null,
                'last_name' => (string) $data['lastName'],
                'phone' => (string) $data['phone'],
                'joining_date' => today()->toDateString(),
                'expiring_date' => $owner->expiring_date?->toDateString(),
                'postal_address' => $data['postalAddress'] ?? null,
                'physical_address' => $data['physicalAddress'] ?? null,
                'super_user' => 0,
                'payment_status' => 1,
                'admin_id' => $adminId,
                'shop_id' => $shopId,
                'createdby' => $owner->id,
                'shop_limit' => 1,
                'user_id' => $data['userId'] ?? null,
                'user_type' => self::EMPLOYEE_USER_TYPE,
                'shops_ids' => json_encode([(string) $shopId]),
                'salary' => $salary,
                'salary_period' => $data['salaryPeriod'] ?? self::DEFAULT_SALARY_PERIOD,
                'created_on' => now(),
                'first_login' => 1,
            ]);

            if ($employee->user_id === null) {
                $employee->user_id = (int) $employee->id;
                $employee->save();
            }

            $salaryExpense = Expense::query()->create([
                'name' => self::SALARY_EXPENSE_NAME,
                'amount' => $salary,
                'details' => $this->salaryExpenseDetails($employee),
                'type' => self::SALARY_EXPENSE_TYPE,
                'user_id' => (int) $employee->user_id,
                'admin_id' => $adminId,
                'shop_id' => $shopId,
                'user_name' => $this->employeeFullName($employee),
                'date' => $this->salaryDate($data['salaryDate'] ?? null),
                'category' => self::SALARY_EXPENSE_NAME,
                'sync' => 1,
                'paid_from' => $data['paidFrom'] ?? 'Cash',
            ]);

            $employee->salary_expense_id = (int) $salaryExpense->id;
            $employee->save();

            return [$employee->refresh(), $salaryExpense];
        });

        return LegacyApiResponse::array(1, 'success', [
            'employee' => $this->employeePayload($employee),
            'salaryExpense' => $salaryExpense,
        ]);
    }

    private function formatSalaryAmount(mixed $salary): string
    {
        return number_format((float) $salary, 2, '.', '');
    }

    private function salaryDate(mixed $salaryDate): string
    {
        if (blank($salaryDate)) {
            return today()->toDateString();
        }

        return CarbonImmutable::parse((string) $salaryDate)->toDateString();
    }

    private function salaryExpenseDetails(Admin $employee): string
    {
        return Str::limit(
            ucfirst((string) ($employee->salary_period ?? self::DEFAULT_SALARY_PERIOD)).' salary for '.$this->employeeFullName($employee),
            110,
            '',
        );
    }

    private function employeeFullName(Admin $employee): string
    {
        return trim(implode(' ', array_filter([
            $employee->first_name,
            $employee->middlename,
            $employee->last_name,
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    private function employeePayload(Admin $employee): array
    {
        return [
            'id' => (int) $employee->id,
            'username' => $employee->username,
            'first_name' => $employee->first_name,
            'middlename' => $employee->middlename,
            'last_name' => $employee->last_name,
            'phone' => $employee->phone,
            'email' => $employee->email,
            'active' => (int) $employee->active,
            'admin_id' => (int) $employee->admin_id,
            'shop_id' => (int) $employee->shop_id,
            'user_id' => (int) $employee->user_id,
            'user_type' => $employee->user_type,
            'shops_ids' => $employee->shops_ids,
            'salary' => $employee->salary,
            'salary_period' => $employee->salary_period,
            'salary_expense_id' => (int) $employee->salary_expense_id,
        ];
    }
}
