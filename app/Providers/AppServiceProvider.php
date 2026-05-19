<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\BackOfficeAccess;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
        // Register model observers
        \App\Models\Shipment::observe(\App\Observers\ShipmentObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);
        \App\Models\PickupAssignment::observe(\App\Observers\PickupAssignmentObserver::class);
        \App\Models\TransportManifest::observe(\App\Observers\TransportManifestObserver::class);
        \App\Models\DeliveryRunStop::observe(\App\Observers\DeliveryRunStopObserver::class);

        // Register authorization gates for permissions
        $this->registerPermissionGates();

        // Register Blade directives for permission checking
        $this->registerBladeDirectives();

        // Share the current warehouse/back-office scope with both legacy shells.
        $this->registerBackOfficeViewContext();
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
                    return app(BackOfficeAccess::class)->canUsePermission($user, $permission->name);
                });
            }
        } catch (\Exception $e) {
            // Permissions table might not exist yet during initial migration
        }

        // Special gate for role template management. HQ privilege comes from the
        // user's warehouse flags, not from a separate super-admin role.
        Gate::define('manage-role', function (User $user, Role $role) {
            return $user->isHqUser() && $user->hasPermission('roles.edit');
        });
    }

    /**
     * Register custom Blade directives for permission checking.
     */
    protected function registerBladeDirectives(): void
    {
        // @hasPermission('permission.name')
        Blade::if('hasPermission', function (string $permission) {
            $user = Auth::guard('admin')->user();

            return Auth::guard('admin')->check()
                && $user
                && app(BackOfficeAccess::class)->canUsePermission($user, $permission);
        });

        // @hasAnyPermission(['permission1', 'permission2'])
        Blade::if('hasAnyPermission', function (array $permissions) {
            $user = Auth::guard('admin')->user();

            return Auth::guard('admin')->check()
                && $user
                && collect($permissions)->contains(
                    fn (string $permission) => app(BackOfficeAccess::class)->canUsePermission($user, $permission)
                );
        });

        // @hasRole('role_slug')
        Blade::if('hasRole', function (string $role) {
            return Auth::guard('admin')->check()
                && Auth::guard('admin')->user()->hasRole($role);
        });
    }

    protected function registerBackOfficeViewContext(): void
    {
        View::composer(['admin.layouts.app', 'warehouse.layouts.app'], function ($view) {
            $user = Auth::guard('admin')->user();

            if (!$user) {
                return;
            }

            try {
                $access = app(BackOfficeAccess::class);
                $module = $access->moduleFromRequestPath(request()->path());
                $warehouses = $access->warehousesFor($user, $module);
                $currentWarehouse = $access->warehouseFor($user);
                $selectedWarehouse = $access->selectedWarehouse($user, $module);
                $isHq = $access->isHq($user);

                if (session()->has('backoffice.selected_warehouse_id') && !$selectedWarehouse) {
                    session()->forget('backoffice.selected_warehouse_id');
                }

                $view->with([
                    'backOfficeModule' => $module,
                    'backOfficeIsHq' => $isHq,
                    'backOfficeWarehouses' => $warehouses,
                    'backOfficeCurrentWarehouse' => $currentWarehouse,
                    'backOfficeSelectedWarehouse' => $selectedWarehouse,
                    'backOfficeCanSwitchWarehouse' => $warehouses->count() > 1 && ($isHq || $warehouses->count() > 1),
                    'backOfficeScopeLabel' => $selectedWarehouse?->name
                        ?? ($isHq ? 'All warehouses' : ($currentWarehouse?->name ?? 'Warehouse')),
                ]);
            } catch (\Throwable $e) {
                $view->with([
                    'backOfficeModule' => 'dashboard',
                    'backOfficeIsHq' => false,
                    'backOfficeWarehouses' => collect(),
                    'backOfficeCurrentWarehouse' => null,
                    'backOfficeSelectedWarehouse' => null,
                    'backOfficeCanSwitchWarehouse' => false,
                    'backOfficeScopeLabel' => 'Warehouse',
                ]);
            }
        });
    }
}
