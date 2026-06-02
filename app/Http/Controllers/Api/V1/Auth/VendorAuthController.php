<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\RegisterRequest;
use App\Http\Requests\Api\Vendor\SendOtpRequest;
use App\Http\Requests\Api\Vendor\VerifyPhoneRequest;
use App\Services\VendorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorAuthController extends Controller
{
    protected VendorAuthService $authService;

    public function __construct(VendorAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Send OTP to phone number.
     * POST /api/v1/auth/vendor/send-otp
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $result = $this->authService->sendOtp($request->phone, $request);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Verify phone with OTP.
     * POST /api/v1/auth/vendor/verify-phone
     */
    public function verifyPhone(VerifyPhoneRequest $request): JsonResponse
    {
        $result = $this->authService->verifyPhone(
            $request->phone,
            $request->otp,
            $request
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Register a new vendor.
     * POST /api/v1/auth/vendor/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated(), $request);

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Logout vendor.
     * POST /api/v1/auth/vendor/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $result = $this->authService->logout($vendor, $request);

        return response()->json($result);
    }
}
