<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register authorization gates for permissions
        $this->registerPermissionGates();

        // Register Blade directives for permission checking
        $this->registerBladeDirectives();
    }

    /**
     * Register permission-based authorization gates.
     */
    protected function registerPermissionGates(): void
    {
        // Auto-define gates for all permissions
        try {
            $permissions = Permission::all();
            foreach ($permissions as $permission) {
                Gate::define($permission->name, function (User $user) use ($permission) {
                    return $user->hasPermission($permission->name);
                });
            }
        } catch (\Exception $e) {
            // Permissions table might not exist yet during initial migration
        }

        // Special gate for role management
        Gate::define('manage-role', function (User $user, Role $role) {
            // Super admin can manage all roles
            if ($user->hasRole('super_admin')) {
                return true;
            }

            // Non-super admins cannot manage system roles
            if ($role->is_system_role) {
                return false;
            }

            // Check if user has permission to edit roles
            return $user->hasPermission('roles.edit');
        });
    }

    /**
     * Register custom Blade directives for permission checking.
     */
    protected function registerBladeDirectives(): void
    {
        // @hasPermission('permission.name')
        Blade::if('hasPermission', function (string $permission) {
            return Auth::guard('admin')->check()
                && Auth::guard('admin')->user()->hasPermission($permission);
        });

        // @hasAnyPermission(['permission1', 'permission2'])
        Blade::if('hasAnyPermission', function (array $permissions) {
            return Auth::guard('admin')->check()
                && Auth::guard('admin')->user()->hasAnyPermission($permissions);
        });

        // @hasRole('role_slug')
        Blade::if('hasRole', function (string $role) {
            return Auth::guard('admin')->check()
                && Auth::guard('admin')->user()->hasRole($role);
        });
    }
}
