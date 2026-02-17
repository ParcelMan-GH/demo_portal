<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard.
     */
    public function index(): View
    {
        $admin = Auth::guard('admin')->user();

        // Get stats based on role using the unified users table.
        if ($admin->isSuperAdmin()) {
            $stats = [
                'total_admins' => User::query()->whereNull('warehouse_id')->count(),
                'active_admins' => User::query()->whereNull('warehouse_id')->where('is_active', true)->count(),
                'inactive_admins' => User::query()->whereNull('warehouse_id')->where('is_active', false)->count(),
            ];
        } else {
            // For non-super admins, show only users they created.
            $stats = [
                'total_admins' => User::query()->where('created_by_user_id', $admin->id)->count(),
                'active_admins' => User::query()->where('created_by_user_id', $admin->id)->where('is_active', true)->count(),
                'inactive_admins' => User::query()->where('created_by_user_id', $admin->id)->where('is_active', false)->count(),
            ];
        }

        return view('admin.dashboard.index', compact('stats'));
    }
}
