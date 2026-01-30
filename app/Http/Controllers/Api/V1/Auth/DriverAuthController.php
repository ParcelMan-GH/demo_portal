<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\LoginRequest;
use App\Services\DriverAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverAuthController extends Controller
{
    protected DriverAuthService $authService;

    public function __construct(DriverAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login with email and password.
     * POST /api/v1/driver/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->email,
            $request->password,
            $request
        );

        $statusCode = $result['success'] ? 200 : 401;

        return response()->json($result, $statusCode);
    }

    /**
     * Logout driver.
     * POST /api/v1/driver/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $driver = $request->user();

        $result = $this->authService->logout($driver, $request);

        return response()->json($result);
    }
}
