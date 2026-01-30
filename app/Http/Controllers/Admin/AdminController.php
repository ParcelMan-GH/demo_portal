<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        $currentUser = Auth::guard('admin')->user();

        // Super admin sees all, others see only users they created
        if ($currentUser->isSuperAdmin()) {
            $admins = User::with(['creator', 'roles'])
                ->latest()
                ->paginate(20);
        } else {
            $admins = User::where('created_by_user_id', $currentUser->id)
                ->with(['creator', 'roles'])
                ->latest()
                ->paginate(20);
        }

        return view('admin.admins.index', compact('admins'));
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
    public function store(CreateAdminRequest $request): RedirectResponse
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
    public function update(UpdateAdminRequest $request, User $admin): RedirectResponse
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
