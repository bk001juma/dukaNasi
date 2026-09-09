<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Services\BaseApp\ShopService;
use App\Support\LegacyApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ShopController extends Controller
{
    public function __construct(private ShopService $shops) {}

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json($this->shops->getShops($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            return response()->json($this->shops->createShop($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }
}
