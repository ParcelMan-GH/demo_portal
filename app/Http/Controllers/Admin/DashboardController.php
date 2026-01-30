<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
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

        // Get stats based on role
        if ($admin->isSuperAdmin()) {
            $stats = [
                'total_admins' => Admin::count(),
                'active_admins' => Admin::where('is_active', true)->count(),
                'inactive_admins' => Admin::where('is_active', false)->count(),
            ];
        } else {
            // For non-super admins, show only admins they created
            $stats = [
                'total_admins' => Admin::where('created_by_admin_id', $admin->id)->count(),
                'active_admins' => Admin::where('created_by_admin_id', $admin->id)->where('is_active', true)->count(),
                'inactive_admins' => Admin::where('created_by_admin_id', $admin->id)->where('is_active', false)->count(),
            ];
        }

        return view('admin.dashboard.index', compact('stats'));
    }
}
