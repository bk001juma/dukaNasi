<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Support\LegacyApiResponse;
use App\Support\LegacyPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class UnitController extends Controller
{
    public function index(): JsonResponse
    {
        return LegacyApiResponse::json(1, 'success', Unit::query()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $unit = Unit::query()->create([
            'name' => LegacyPayload::string($payload, 'name'),
            'symbol' => LegacyPayload::string($payload, 'symbol'),
            'admin_id' => LegacyPayload::integer($payload, 'adminId', 'admin_id'),
        ]);

        return LegacyApiResponse::json(1, 'success', $unit);
    }

    public function sync(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $unit = Unit::withTrashed()->find(LegacyPayload::integer($payload, 'unitId', 'unit_id'));

        if ($unit === null) {
            throw new RuntimeException('Unit not found');
        }

        if ($unit->deleted_at !== null) {
            throw new RuntimeException('Unit is deleted');
        }

        $unit->update([
            'name' => LegacyPayload::string($payload, 'name'),
            'symbol' => LegacyPayload::string($payload, 'symbol'),
            'admin_id' => LegacyPayload::integer($payload, 'adminId', 'admin_id'),
        ]);

        return LegacyApiResponse::rawJson($unit);
    }
}
