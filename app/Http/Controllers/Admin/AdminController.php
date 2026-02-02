<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Role;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class AdminController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $roles = Role::active()->get();
        return view('admin.admins.index', compact('roles'));
    }

    /**
     * Get users data for datatable (AJAX).
     */
    public function data(Request $request): JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Base query
        if ($currentUser->isSuperAdmin()) {
            $query = User::with(['creator', 'roles']);
        } else {
            $query = User::where('created_by_user_id', $currentUser->id)
                ->with(['creator', 'roles']);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($roleId = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        // Filter by status
        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        // Filter by date range
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        // Get total before pagination
        $total = $query->count();

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        }

        // Pagination
        $perPage = min((int) $request->input('per_page', 10), 100);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $admins = $query->skip($offset)->take($perPage)->get();

        // Transform data
        $data = $admins->map(function ($admin) use ($currentUser) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar' => strtoupper(substr($admin->name, 0, 1)),
                'roles' => $admin->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
                'is_active' => $admin->is_active,
                'creator' => $admin->creator?->name ?? 'System',
                'created_at' => $admin->created_at->format('d/m/Y, H:i:s'),
                'last_login_at' => $admin->last_login_at?->diffForHumans() ?? 'Never',
                'can_manage' => $currentUser->canManage($admin),
                'is_self' => $admin->id === $currentUser->id,
                'view_url' => route('admin.admins.show', $admin),
                'edit_url' => route('admin.admins.edit', $admin),
                'toggle_url' => route('admin.admins.toggle-active', $admin),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Export users data.
     */
    public function export(Request $request)
    {
        $currentUser = Auth::guard('admin')->user();

        if ($currentUser->isSuperAdmin()) {
            $query = User::with(['creator', 'roles']);
        } else {
            $query = User::where('created_by_user_id', $currentUser->id)
                ->with(['creator', 'roles']);
        }

        // Apply same filters as data method
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->boolean('status'));
        }

        $admins = $query->latest()->get();

        $data = $admins->map(function ($admin) {
            return [
                'Name' => $admin->name,
                'Email' => $admin->email,
                'Roles' => $admin->roles->pluck('name')->implode(', '),
                'Status' => $admin->is_active ? 'Active' : 'Inactive',
                'Created By' => $admin->creator?->name ?? 'System',
                'Created At' => $admin->created_at->format('Y-m-d H:i:s'),
                'Last Login' => $admin->last_login_at?->format('Y-m-d H:i:s') ?? 'Never',
            ];
        });

        $format = $request->input('format', 'json');

        $rows = $data->values()->toArray();

        if ($format === 'excel') {
            return $this->exportExcel($rows);
        }

        if ($format === 'pdf') {
            return $this->exportPDF($rows);
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * Export users to Excel format.
     */
    private function exportExcel(array $rows)
    {
        $filename = 'users_' . date('Y-m-d_His') . '.xlsx';
        return Excel::download(new UsersExport($rows), $filename);
    }

    /**
     * Export users to PDF format.
     */
    private function exportPDF(array $rows)
    {
        $filename = 'users_' . date('Y-m-d_His') . '.pdf';
        $pdf = Pdf::loadView('admin.admins.export-pdf', [
            'rows' => $rows,
            'generatedAt' => now()->format('F d, Y H:i:s'),
        ]);
        return $pdf->download($filename);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View|RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        if (!$currentUser->hasPermission('users.create')) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You do not have permission to create users.');
        }

        $roles = Role::active()->get();

        return view('admin.admins.create', compact('roles'));
    }

    /**
     * Store a newly created user.
     */
    public function store(CreateAdminRequest $request): RedirectResponse|JsonResponse
    {
        $currentUser = Auth::guard('admin')->user();

        $admin = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => $request->boolean('is_active', true),
            'created_by_user_id' => $currentUser->id,
        ]);

        // Assign selected roles
        if ($request->has('roles')) {
            $admin->syncRoles($request->roles);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'user' => $admin,
            ]);
        }

        return redirect()->route('admin.admins.show', $admin)
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $admin): View|RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Check if can view this user
        if (!$currentUser->isSuperAdmin() && $admin->created_by_user_id !== $currentUser->id && $admin->id !== $currentUser->id) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You do not have permission to view this user.');
        }

        $admin->load(['roles.permissions', 'creator']);

        return view('admin.admins.show', compact('admin'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $admin): View|RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Check if can edit this user
        if (!$currentUser->canManage($admin)) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You do not have permission to edit this user.');
        }

        $roles = Role::active()->get();

        return view('admin.admins.edit', compact('admin', 'roles'));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateAdminRequest $request, User $admin): RedirectResponse|JsonResponse
    {
        $data = $request->only(['name', 'email', 'is_active']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        // Sync roles if provided and if user is not updating themselves
        if ($request->has('roles') && $admin->id !== Auth::guard('admin')->id()) {
            $admin->syncRoles($request->roles);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'user' => $admin,
            ]);
        }

        return redirect()->route('admin.admins.show', $admin)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Toggle the active status of the specified user.
     */
    public function toggleActive(User $admin): RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Cannot toggle your own status
        if ($admin->id === $currentUser->id) {
            return back()->with('error', 'You cannot change your own active status.');
        }

        // Check permission
        if (!$currentUser->canManage($admin)) {
            return back()->with('error', 'You do not have permission to modify this user.');
        }

        $admin->update(['is_active' => !$admin->is_active]);

        $status = $admin->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    /**
     * Assign roles to the specified user.
     */
    public function assignRoles(Request $request, User $admin): RedirectResponse
    {
        $currentUser = Auth::guard('admin')->user();

        // Check permission
        if (!$currentUser->hasPermission('roles.assign')) {
            return back()->with('error', 'You do not have permission to assign roles.');
        }

        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $admin->syncRoles($validated['roles']);

        return back()->with('success', 'Roles assigned successfully.');
    }
}
