<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\ForgotPinRequest;
use App\Http\Requests\Api\Vendor\LoginRequest;
use App\Http\Requests\Api\Vendor\RegisterRequest;
use App\Http\Requests\Api\Vendor\ResendOtpRequest;
use App\Http\Requests\Api\Vendor\ResetPinRequest;
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
     * Resend OTP for registration or pin reset.
     * POST /api/v1/auth/vendor/resend-otp
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $result = $this->authService->resendOtp(
            $request->phone,
            $request->purpose,
            $request
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Login with phone and PIN.
     * POST /api/v1/auth/vendor/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            $request->phone,
            $request->pin,
            $request
        );

        $statusCode = $result['success'] ? 200 : 401;

        return response()->json($result, $statusCode);
    }

    /**
     * Request forgot PIN OTP.
     * POST /api/v1/auth/vendor/forgot-pin
     */
    public function forgotPin(ForgotPinRequest $request): JsonResponse
    {
        $result = $this->authService->forgotPin($request->phone, $request);

        return response()->json($result);
    }

    /**
     * Reset PIN with OTP.
     * POST /api/v1/auth/vendor/reset-pin
     */
    public function resetPin(ResetPinRequest $request): JsonResponse
    {
        $result = $this->authService->resetPin(
            $request->phone,
            $request->otp,
            $request->new_pin,
            $request
        );

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
