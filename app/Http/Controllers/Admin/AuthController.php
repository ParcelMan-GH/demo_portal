<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Helpers\PhoneHelper;
use App\Services\AdminAuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private AdminAuditLogService $auditLogService)
    {
    }

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $identifier = trim((string) $request->input('email'));
        $loginField = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [
            $loginField => $loginField === 'phone' ? (PhoneHelper::format($identifier) ?? $identifier) : $identifier,
            'password' => (string) $request->input('password'),
        ];
        $remember = $request->boolean('remember');

        if (!Auth::guard('admin')->attempt($credentials, $remember)) {
            $this->auditLogService->logAuthEvent(
                action: 'admin.login.failed',
                description: 'Admin login failed due to invalid credentials.',
                request: $request,
                metadata: [
                    'identifier' => $identifier,
                    'login_field' => $loginField,
                    'reason' => 'invalid_credentials',
                ],
                statusCode: 422
            );

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid email or password.']);
        }

        $admin = Auth::guard('admin')->user();

        // Check if account is active
        if (!$admin->is_active) {
            $this->auditLogService->logAuthEvent(
                action: 'admin.login.failed',
                description: 'Admin login blocked because account is inactive.',
                request: $request,
                actor: $admin,
                metadata: [
                    'identifier' => $identifier,
                    'login_field' => $loginField,
                    'reason' => 'inactive_account',
                ],
                statusCode: 403
            );

            Auth::guard('admin')->logout();
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.']);
        }

        // Update last login timestamp
        $admin->update(['last_login_at' => now()]);
        $admin->flushPermissionCache();

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        $this->auditLogService->logAuthEvent(
            action: 'admin.login.success',
            description: 'Admin user logged in successfully.',
            request: $request,
            actor: $admin,
            metadata: [
                'identifier' => $identifier,
                'login_field' => $loginField,
                'remember' => $remember,
            ],
            statusCode: 200
        );

        if (!$admin->isSuperAdmin()
            && !empty($admin->warehouse_id)
            && $admin->roles()->where('is_warehouse_role', true)->exists()) {
            return redirect()->route('warehouse.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    /**
     * Handle logout request.
     */
    public function logout(): RedirectResponse
    {
        $request = request();

        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
