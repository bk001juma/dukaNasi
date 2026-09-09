<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Services\BaseApp\SaleService;
use App\Support\LegacyApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales) {}

    public function receiptItems(Request $request): JsonResponse
    {
        try {
            return response()->json($this->sales->getReceiptItems($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->sales->getSales($request->json()->all());

        return response()->json($result['body'], $result['status']);
    }

    public function count(Request $request): JsonResponse
    {
        $result = $this->sales->count($request->query());

        return response()->json($result['body'], $result['status']);
    }

    public function paginated(Request $request): JsonResponse
    {
        $result = $this->sales->paginated($request->query());

        return response()->json($result['body'], $result['status']);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            return response()->json($this->sales->create($request->json()->all(), $request->header('token')));
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    public function performTransaction(Request $request): Response
    {
        try {
            $response = $this->sales->performTransaction($request->json()->all());

            return response($response ?? '', 200)
                ->header('Content-Type', 'application/json');
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }
}
