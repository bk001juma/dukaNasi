<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Services\BaseApp\CustomerSupplierService;
use App\Support\LegacyApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CustomerSupplierController extends Controller
{
    public function __construct(private CustomerSupplierService $customerSuppliers) {}

    public function customers(Request $request): JsonResponse
    {
        try {
            return response()->json($this->customerSuppliers->getCustomers($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function suppliers(Request $request): JsonResponse
    {
        try {
            return response()->json($this->customerSuppliers->getSuppliers($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        try {
            return response()->json($this->customerSuppliers->createCustomer($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        try {
            return response()->json($this->customerSuppliers->createSupplier($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }
}
