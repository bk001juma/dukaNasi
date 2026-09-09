<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\BaseApp\LegacyAccessService;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidationValidator;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionPlanController extends Controller
{
    public function __construct(private LegacyAccessService $access) {}

    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return LegacyApiResponse::json(
            1,
            $plans->isEmpty() ? 'No subscription plans found' : 'success',
            $plans,
        );
    }

    public function store(Request $request): JsonResponse
    {
        $accessResponse = $this->authorizePlanManagement($request);

        if ($accessResponse !== null) {
            return $accessResponse;
        }

        $validator = Validator::make($request->json()->all(), [
            'title' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subscription_plans', 'title')->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $validator->validated();
        $plan = SubscriptionPlan::query()->create([
            'title' => trim((string) $data['title']),
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'duration_days' => SubscriptionPlan::DURATION_DAYS,
        ]);

        return LegacyApiResponse::json(1, 'success', $plan);
    }

    public function update(Request $request, ?string $planId = null): JsonResponse
    {
        $accessResponse = $this->authorizePlanManagement($request);

        if ($accessResponse !== null) {
            return $accessResponse;
        }

        $plan = $this->findPlan($request, $planId);

        if ($plan === null) {
            return LegacyApiResponse::json(
                0,
                'Subscription plan not found',
                null,
                Response::HTTP_NOT_FOUND,
            );
        }

        $payload = $request->json()->all();
        $validator = Validator::make($payload, [
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('subscription_plans', 'title')
                    ->ignore($plan->id)
                    ->whereNull('deleted_at'),
            ],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        $validator->after(function (ValidationValidator $validator) use ($payload): void {
            if (
                ! array_key_exists('title', $payload)
                && ! array_key_exists('amount', $payload)
                && ! array_key_exists('description', $payload)
            ) {
                $validator->errors()->add('plan', 'At least one plan field is required.');
            }
        });

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $data = $validator->validated();
        $updates = [
            'duration_days' => SubscriptionPlan::DURATION_DAYS,
        ];

        if (array_key_exists('title', $data)) {
            $updates['title'] = trim((string) $data['title']);
        }

        if (array_key_exists('amount', $data)) {
            $updates['amount'] = $data['amount'];
        }

        if (array_key_exists('description', $data)) {
            $updates['description'] = $data['description'];
        }

        $plan->update($updates);

        return LegacyApiResponse::json(1, 'success', $plan->refresh());
    }

    public function destroy(Request $request, ?string $planId = null): JsonResponse
    {
        $accessResponse = $this->authorizePlanManagement($request);

        if ($accessResponse !== null) {
            return $accessResponse;
        }

        $plan = $this->findPlan($request, $planId);

        if ($plan === null) {
            return LegacyApiResponse::json(
                0,
                'Subscription plan not found',
                null,
                Response::HTTP_NOT_FOUND,
            );
        }

        $plan->delete();

        return LegacyApiResponse::json(1, 'success');
    }

    private function authorizePlanManagement(Request $request): ?JsonResponse
    {
        $user = $this->access->currentUserFromToken($request->header('token'));

        if ($user === null) {
            return LegacyApiResponse::json(0, 'Fail invalid request');
        }

        if ((int) $user->super_user !== 1) {
            return LegacyApiResponse::json(0, 'Fail you dont have access to view this resource');
        }

        return null;
    }

    private function findPlan(Request $request, ?string $planId): ?SubscriptionPlan
    {
        $payload = $request->json()->all();
        $id = $planId !== null ? (int) $planId : LegacyPayload::integer($payload, 'planId', 'plan_id', 'id');

        if ($id === null || $id < 1) {
            return null;
        }

        return SubscriptionPlan::query()->find($id);
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
