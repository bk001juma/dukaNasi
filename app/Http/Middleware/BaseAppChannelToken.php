<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Services\BaseApp\LegacyAccessService;
use App\Support\LegacyApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class BaseAppChannelToken
{
    public function __construct(private LegacyAccessService $access) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $apiKey = (string) $request->header('X-Api-Key', '');
        $token = (string) $request->header('token', '');
        $username = (string) $request->header('username', '');
        $isLogin = $request->is('api/*/baseApp/login');

        if ($isLogin && blank($username)) {
            return LegacyApiResponse::json(
                0,
                'Invalid or missing correct username header',
                null,
                SymfonyResponse::HTTP_UNAUTHORIZED,
            );
        }

        if (! $isLogin && blank($token)) {
            return LegacyApiResponse::json(
                0,
                'Invalid or missing correct Token to access this service',
                null,
                SymfonyResponse::HTTP_UNAUTHORIZED,
            );
        }

        if (blank(config('mauzo360.channel_token')) || $apiKey !== config('mauzo360.channel_token')) {
            return LegacyApiResponse::json(
                0,
                'Invalid or missing correct Api key to access this service',
                null,
                SymfonyResponse::HTTP_UNAUTHORIZED,
            );
        }

        if (! $isLogin && $this->access->noLicense($token, $username)) {
            if (! Admin::query()->where('secret_key', $token)->exists()) {
                return LegacyApiResponse::json(
                    2,
                    'User token expired ! login again',
                    null,
                    SymfonyResponse::HTTP_UNAUTHORIZED,
                );
            }

            return LegacyApiResponse::json(
                -1,
                'User License Expired, Kindly renew to proceed using these services',
                null,
                SymfonyResponse::HTTP_UNAUTHORIZED,
            );
        }

        return $next($request);
    }
}
