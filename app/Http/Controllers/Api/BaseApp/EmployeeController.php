<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Services\BaseApp\EmployeeService;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EmployeeController extends Controller
{
    public function __construct(private EmployeeService $employees) {}

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($this->preparePayload($request->json()->all()), [
            'adminId' => ['required', 'integer', 'min:1'],
            'shopId' => ['required', 'integer', 'min:1'],
            'userId' => ['nullable', 'integer', 'min:1'],
            'username' => ['required', 'string', 'max:255', Rule::unique('admins', 'username')],
            'password' => ['required', 'string', 'max:255'],
            'firstName' => ['required', 'string', 'max:255'],
            'middleName' => ['nullable', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'postalAddress' => ['nullable', 'string'],
            'physicalAddress' => ['nullable', 'string'],
            'salary' => ['required', 'numeric', 'min:0.01', 'max:9999999999999.99'],
            'salaryPeriod' => ['nullable', 'string', 'max:32'],
            'salaryDate' => ['nullable', 'date'],
            'paidFrom' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            return response()->json($this->employees->create($validator->validated(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function preparePayload(array $payload): array
    {
        return [
            'adminId' => LegacyPayload::get($payload, 'adminId', 'admin_id'),
            'shopId' => LegacyPayload::get($payload, 'shopId', 'shop_id'),
            'userId' => LegacyPayload::get($payload, 'userId', 'user_id'),
            'username' => $this->trimToNull(LegacyPayload::string($payload, 'username')),
            'password' => LegacyPayload::string($payload, 'password'),
            'firstName' => $this->trimToNull(LegacyPayload::string($payload, 'firstName', 'first_name')),
            'middleName' => $this->trimToNull(LegacyPayload::string($payload, 'middleName', 'middle_name', 'middlename')),
            'lastName' => $this->trimToNull(LegacyPayload::string($payload, 'lastName', 'last_name')),
            'phone' => $this->trimToNull(LegacyPayload::string($payload, 'phone')),
            'email' => $this->trimToNull(LegacyPayload::string($payload, 'email')),
            'postalAddress' => $this->trimToNull(LegacyPayload::string($payload, 'postalAddress', 'postal_address')),
            'physicalAddress' => $this->trimToNull(LegacyPayload::string($payload, 'physicalAddress', 'physical_address')),
            'salary' => LegacyPayload::get($payload, 'salary', 'salaryAmount', 'salary_amount'),
            'salaryPeriod' => $this->trimToNull(LegacyPayload::string($payload, 'salaryPeriod', 'salary_period')) ?? 'monthly',
            'salaryDate' => $this->trimToNull(LegacyPayload::string($payload, 'salaryDate', 'salary_date', 'date')),
            'paidFrom' => $this->trimToNull(LegacyPayload::string($payload, 'paidFrom', 'paid_from')) ?? 'Cash',
        ];
    }

    private function trimToNull(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function validationErrorResponse(ValidationValidator $validator): JsonResponse
    {
        return LegacyApiResponse::json(
            0,
            (string) $validator->errors()->first(),
            ['errors' => $validator->errors()->toArray()],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }
}
