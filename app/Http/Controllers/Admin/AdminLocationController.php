<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Location;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminLocationController extends Controller
{
    public function index()
    {
        return view('admin.locations.index');
    }

    // ─── Regions ──────────────────────────────────────────────────────────────

    public function regionsData(Request $request): JsonResponse
    {
        $query = Region::withCount([
            'districts',
            'locations',
            'districts as active_districts_count' => fn ($q) => $q->where('is_active', true),
            'locations as active_locations_count' => fn ($q) => $q->where('is_active', true),
        ])
            ->orderBy('name');

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->get('active') !== null) {
            $query->where('is_active', (bool) $request->get('active'));
        }

        if ($code = trim($request->get('code', ''))) {
            $query->where('code', 'like', "%{$code}%");
        }

        match ($request->get('coverage')) {
            'has_districts' => $query->has('districts'),
            'no_districts' => $query->doesntHave('districts'),
            'has_towns' => $query->has('locations'),
            'no_towns' => $query->doesntHave('locations'),
            'has_active_towns' => $query->whereHas('locations', fn ($q) => $q->where('is_active', true)),
            'no_active_towns' => $query->whereDoesntHave('locations', fn ($q) => $q->where('is_active', true)),
            default => null,
        };

        $regions = $query->get()->map(fn ($r) => [
            'id'                     => $r->id,
            'name'                   => $r->name,
            'code'                   => $r->code,
            'is_active'              => $r->is_active,
            'districts_count'        => $r->districts_count,
            'locations_count'        => $r->locations_count,
            'active_districts_count' => $r->active_districts_count,
            'active_locations_count' => $r->active_locations_count,
        ]);

        return response()->json(['success' => true, 'data' => ['regions' => $regions, 'total' => $regions->count()]]);
    }

    public function storeRegion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:regions,name'],
            'code' => ['required', 'string', 'max:20', 'unique:regions,code'],
        ]);

        $region = Region::create([
            'name'      => $validated['name'],
            'code'      => strtoupper($validated['code']),
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Region created.', 'data' => ['region' => $region]], 201);
    }

    public function updateRegion(Request $request, Region $region): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('regions', 'name')->ignore($region->id)],
            'code' => ['required', 'string', 'max:20', Rule::unique('regions', 'code')->ignore($region->id)],
        ]);

        $region->update([
            'name' => $validated['name'],
            'code' => strtoupper($validated['code']),
        ]);

        return response()->json(['success' => true, 'message' => 'Region updated.', 'data' => ['region' => $region->fresh()]]);
    }

    public function toggleRegion(Region $region): JsonResponse
    {
        $region->update(['is_active' => !$region->is_active]);

        return response()->json(['success' => true, 'message' => 'Region ' . ($region->is_active ? 'activated' : 'deactivated') . '.', 'data' => ['is_active' => $region->is_active]]);
    }

    public function destroyRegion(Region $region): JsonResponse
    {
        if ($region->districts()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete region that has districts. Remove all districts first.'], 422);
        }

        $region->delete();

        return response()->json(['success' => true, 'message' => 'Region deleted.']);
    }

    // ─── Districts ────────────────────────────────────────────────────────────

    public function districtsData(Request $request): JsonResponse
    {
        $query = District::with('region:id,name,code,is_active')
            ->withCount([
                'locations',
                'locations as active_locations_count' => fn ($q) => $q->where('is_active', true),
            ])
            ->orderBy('name');

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('districts.name', 'like', "%{$search}%")
                  ->orWhere('districts.code', 'like', "%{$search}%");
            });
        }

        if ($regionId = $request->get('region_id')) {
            $query->where('region_id', $regionId);
        }

        if ($request->get('active') !== null) {
            $query->where('is_active', (bool) $request->get('active'));
        }

        if ($code = trim($request->get('code', ''))) {
            $query->where('districts.code', 'like', "%{$code}%");
        }

        if ($request->get('region_active') !== null) {
            $query->whereHas('region', fn ($q) => $q->where('is_active', (bool) $request->get('region_active')));
        }

        match ($request->get('coverage')) {
            'has_towns' => $query->has('locations'),
            'no_towns' => $query->doesntHave('locations'),
            'has_active_towns' => $query->whereHas('locations', fn ($q) => $q->where('is_active', true)),
            'no_active_towns' => $query->whereDoesntHave('locations', fn ($q) => $q->where('is_active', true)),
            default => null,
        };

        $districts = $query->get()->map(fn ($d) => [
            'id'                     => $d->id,
            'name'                   => $d->name,
            'code'                   => $d->code,
            'is_active'              => $d->is_active,
            'region_id'              => $d->region_id,
            'region_name'            => $d->region->name,
            'region_is_active'       => $d->region->is_active,
            'locations_count'        => $d->locations_count,
            'active_locations_count' => $d->active_locations_count,
        ]);

        return response()->json(['success' => true, 'data' => ['districts' => $districts, 'total' => $districts->count()]]);
    }

    public function storeDistrict(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['required', 'string', 'max:30', 'unique:districts,code'],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ]);

        $district = District::create([
            'name'      => $validated['name'],
            'code'      => strtoupper($validated['code']),
            'region_id' => $validated['region_id'],
            'is_active' => true,
        ]);

        return response()->json(['success' => true, 'message' => 'District created.', 'data' => ['district' => $district->load('region:id,name')]], 201);
    }

    public function updateDistrict(Request $request, District $district): JsonResponse
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'code'      => ['required', 'string', 'max:30', Rule::unique('districts', 'code')->ignore($district->id)],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
        ]);

        $district->update([
            'name'      => $validated['name'],
            'code'      => strtoupper($validated['code']),
            'region_id' => $validated['region_id'],
        ]);

        return response()->json(['success' => true, 'message' => 'District updated.', 'data' => ['district' => $district->fresh()->load('region:id,name')]]);
    }

    public function toggleDistrict(District $district): JsonResponse
    {
        $district->update(['is_active' => !$district->is_active]);

        return response()->json(['success' => true, 'message' => 'District ' . ($district->is_active ? 'activated' : 'deactivated') . '.', 'data' => ['is_active' => $district->is_active]]);
    }

    public function destroyDistrict(District $district): JsonResponse
    {
        if ($district->locations()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete district that has towns. Remove all towns first.'], 422);
        }

        $district->delete();

        return response()->json(['success' => true, 'message' => 'District deleted.']);
    }

    // ─── Towns (Locations) ────────────────────────────────────────────────────

    public function townsData(Request $request): JsonResponse
    {
        $query = Location::with(['district:id,name,is_active', 'region:id,name,is_active'])
            ->orderBy('name');

        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('district', fn ($districtQuery) => $districtQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('region', fn ($regionQuery) => $regionQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($regionId = $request->get('region_id')) {
            $query->where('region_id', $regionId);
        }

        if ($districtId = $request->get('district_id')) {
            $query->where('district_id', $districtId);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($request->get('active') !== null) {
            $query->where('is_active', (bool) $request->get('active'));
        }

        if ($request->get('region_active') !== null) {
            $query->whereHas('region', fn ($q) => $q->where('is_active', (bool) $request->get('region_active')));
        }

        if ($request->get('district_active') !== null) {
            $query->whereHas('district', fn ($q) => $q->where('is_active', (bool) $request->get('district_active')));
        }

        match ($request->get('parent_state')) {
            'ready' => $query->whereHas('region', fn ($q) => $q->where('is_active', true))
                ->whereHas('district', fn ($q) => $q->where('is_active', true)),
            'inactive_region' => $query->whereHas('region', fn ($q) => $q->where('is_active', false)),
            'inactive_district' => $query->whereHas('district', fn ($q) => $q->where('is_active', false)),
            default => null,
        };

        $limit  = min((int) $request->get('limit', 100), 500);
        $offset = (int) $request->get('offset', 0);

        $total = $query->count();
        $towns = $query->offset($offset)->limit($limit)->get()->map(fn ($t) => [
            'id'           => $t->id,
            'name'         => $t->name,
            'type'         => $t->type,
            'is_active'    => $t->is_active,
            'district_id'  => $t->district_id,
            'district_name'=> $t->district->name,
            'district_is_active' => $t->district->is_active,
            'region_id'    => $t->region_id,
            'region_name'  => $t->region->name,
            'region_is_active' => $t->region->is_active,
        ]);

        return response()->json(['success' => true, 'data' => ['towns' => $towns, 'total' => $total, 'limit' => $limit, 'offset' => $offset]]);
    }

    public function storeTown(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', 'string', Rule::in(['city', 'town', 'suburb', 'village'])],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $district = District::find($validated['district_id']);

        $exists = Location::where('name', $validated['name'])
            ->where('district_id', $validated['district_id'])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A town with this name already exists in that district.'], 422);
        }

        $town = Location::create([
            'name'        => $validated['name'],
            'type'        => $validated['type'],
            'district_id' => $district->id,
            'region_id'   => $district->region_id,
            'is_active'   => true,
        ]);

        return response()->json(['success' => true, 'message' => 'Town created.', 'data' => ['town' => $town->load(['district:id,name', 'region:id,name'])]], 201);
    }

    public function updateTown(Request $request, Location $town): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:150'],
            'type'        => ['required', 'string', Rule::in(['city', 'town', 'suburb', 'village'])],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $district = District::find($validated['district_id']);

        $exists = Location::where('name', $validated['name'])
            ->where('district_id', $validated['district_id'])
            ->where('id', '!=', $town->id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'A town with this name already exists in that district.'], 422);
        }

        $town->update([
            'name'        => $validated['name'],
            'type'        => $validated['type'],
            'district_id' => $district->id,
            'region_id'   => $district->region_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Town updated.', 'data' => ['town' => $town->fresh()->load(['district:id,name', 'region:id,name'])]]);
    }

    public function toggleTown(Location $town): JsonResponse
    {
        $town->update(['is_active' => !$town->is_active]);

        return response()->json(['success' => true, 'message' => 'Town ' . ($town->is_active ? 'activated' : 'deactivated') . '.', 'data' => ['is_active' => $town->is_active]]);
    }

    public function destroyTown(Location $town): JsonResponse
    {
        $town->delete();

        return response()->json(['success' => true, 'message' => 'Town deleted.']);
    }
}
