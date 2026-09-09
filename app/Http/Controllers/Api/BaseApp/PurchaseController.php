<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Services\BaseApp\PurchaseService;
use App\Support\LegacyApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    public function store(Request $request): JsonResponse
    {
        try {
            return response()->json($this->purchases->createPurchase($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json($this->purchases->getPurchases($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }
}
