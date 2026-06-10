<?php

use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(LogAdminAuditActivity::class);
});

function makeManualTestUser(array $permissionNames = []): User
{
    $warehouse = Warehouse::create([
        'name' => 'Manual HQ',
        'code' => 'HQ-MANUAL',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $user = User::factory()->create([
        'name' => 'Manual Admin',
        'email' => 'manual-admin@example.test',
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::create([
        'name' => 'Manual Test Role',
        'slug' => 'manual-test-role-' . $user->id,
        'description' => 'Can read the system manual in tests',
        'is_system_role' => false,
        'is_warehouse_role' => false,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissionNames as $permissionName) {
        [$module, $action] = array_pad(explode('.', $permissionName, 2), 2, 'view');

        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            [
                'module' => $module,
                'action' => $action,
                'description' => $permissionName,
            ],
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);
    $user->flushPermissionCache();

    return $user->fresh();
}

test('system manual is accessible to a back office user', function (): void {
    $user = makeManualTestUser();

    $response = $this->actingAs($user, 'admin')->get(route('admin.manual'));

    $response->assertOk();
    $response->assertSeeText('System Usage Manual');
    $response->assertSeeText('The Parcel Lifecycle');
    $response->assertSeeText('End-to-End Workflows');
    $response->assertSeeText('No results found');
    $response->assertSeeText('Last updated');
});

test('system manual renders the sidebar help link', function (): void {
    $user = makeManualTestUser();

    $this->actingAs($user, 'admin')
        ->get(route('admin.manual'))
        ->assertOk()
        ->assertSeeText('Help')
        ->assertSeeText('System Manual')
        ->assertSee('href="' . route('admin.manual') . '"', false);
});

test('system manual uses current operations labels and route backed links', function (): void {
    $user = makeManualTestUser();

    $this->actingAs($user, 'admin')
        ->get(route('admin.manual'))
        ->assertOk()
        ->assertSeeText('Incoming Packages')
        ->assertSeeText('Warehouse Packages')
        ->assertSeeText('Package Tracking')
        ->assertSeeText('Transport Manifests')
        ->assertSeeText('Incoming Manifests')
        ->assertSeeText('Operations direct page')
        ->assertSee(route('warehouse.receipts.pending.index'), false)
        ->assertSee(route('warehouse.packages.index'), false)
        ->assertSee(route('admin.package-tracking.index'), false)
        ->assertSee(route('warehouse.manifests.transport.index'), false)
        ->assertSee(route('warehouse.manifests.incoming.index'), false)
        ->assertSee(route('warehouse.collections.index'), false)
        ->assertSee(route('warehouse.recipient-payments.index'), false);
});

test('system manual renders diagrams and navigation aids', function (): void {
    $user = makeManualTestUser();

    $this->actingAs($user, 'admin')
        ->get(route('admin.manual'))
        ->assertOk()
        // Inline SVG diagrams: lifecycle, swimlane, sorting decision, access model.
        ->assertSee('arr-lc', false)
        ->assertSee('arr-sw', false)
        ->assertSee('arr-st', false)
        ->assertSee('arr-am', false)
        ->assertSee('Parcel lifecycle flow', false)
        ->assertSee('Standard shipment swimlane', false)
        // Navigation aids: scrollspy sections, related chips, mobile jump menu.
        ->assertSee('manual-section', false)
        ->assertSeeText('Related')
        ->assertSeeText('Jump to a section…')
        ->assertSee('Copy link to this section', false);
});

test('system manual includes persona reading paths and glossary', function (): void {
    $user = makeManualTestUser();

    $this->actingAs($user, 'admin')
        ->get(route('admin.manual'))
        ->assertOk()
        ->assertSeeText('Where Should I Start?')
        ->assertSeeText('HQ administrator')
        ->assertSeeText('Warehouse manager')
        ->assertSeeText('COD desk collector')
        ->assertSeeText('Glossary')
        ->assertSeeText('Shipment (order)')
        ->assertSeeText('Permission vs capability')
        ->assertSee('href="#personas"', false)
        ->assertSee('href="#glossary"', false);
});

test('system manual redirects guests to login', function (): void {
    $this->get(route('admin.manual'))->assertRedirect(route('admin.login'));
});

test('system manual route is named', function (): void {
    expect(route('admin.manual'))->toContain('/admin/manual');
});
