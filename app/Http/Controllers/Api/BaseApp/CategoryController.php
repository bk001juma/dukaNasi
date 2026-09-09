<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Services\BaseApp\CategoryService;
use App\Support\LegacyApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function store(Request $request): JsonResponse
    {
        try {
            return response()->json($this->categories->createCategory($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function index(Request $request): JsonResponse
    {
        try {
            return response()->json($this->categories->getCategories($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }
}
