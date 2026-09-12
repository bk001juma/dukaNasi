<?php

namespace App\Http\Controllers\Api\BaseApp;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\LegacyApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;  
use Throwable;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $payload = $request->json()->all();
            $user = Admin::query()
                ->where('username', (string) ($payload['username'] ?? ''))
                ->first();

            if ($user === null) {
                return LegacyApiResponse::json(0, 'Wrong username or password');
            }

            if (Hash::check((string) ($payload['password'] ?? ''), $user->password)) {
                $user->secret_key = $this->generateToken();
                $user->save();

                return LegacyApiResponse::json(1, 'Success', $user);
            }

            return LegacyApiResponse::json(0, 'Wrong username or password');
        } catch (Throwable $exception) {
            return LegacyApiResponse::json(0, 'Error ! '.$exception->getMessage());
        }
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}