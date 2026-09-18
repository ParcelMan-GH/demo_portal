<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Events\PhotoUploaded;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MobileCameraController extends Controller
{
    // Shows the camera UI on the mobile phone
    public function show($sessionId)
    {
        return view('warehouse.walkin.mobile-camera', compact('sessionId'));
    }

    // Handles the photo upload from the phone
    public function upload(Request $request, $sessionId)
    {
        $request->validate([
            'photo' => 'required|image|max:12288', // Max 12MB
        ]);

        // Save to a temporary public folder
        $path = $request->file('photo')->store('temp_walkin_photos', 'public');

        // Track the photo for this session (polling + broadcast fallback)
        $photos = Cache::get("walkin_photos_{$sessionId}", []);
        $photos[] = $path;
        Cache::put("walkin_photos_{$sessionId}", $photos, now()->addHours(4));

        // Broadcast the event via Reverb to the Desktop (works when Reverb is running)
        broadcast(new PhotoUploaded($sessionId, $path));

        return response()->json([
            'success' => true,
            'path' => $path,
        ]);
    }

    // Polled by the desktop page while the QR modal is open (works without WebSockets)
    public function photos($sessionId)
    {
        $photos = Cache::get("walkin_photos_{$sessionId}", []);

        return response()->json([
            'success' => true,
            'photos' => $photos,
        ]);
    }

    // Location lookup for the phone form (same data the desktop search uses)
    public function locations(Request $request, $sessionId)
    {
        $q = trim((string) $request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['locations' => []]);
        }

        $locations = Location::where('is_active', true)
            ->with(['district:id,name', 'region:id,name'])
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', $q.'%')
                    ->orWhere('name', 'like', '% '.$q.'%');
            })
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$q.'%'])
            ->orderBy('name')
            ->limit(12)
            ->get();

        return response()->json([
            'locations' => $locations->map(fn ($location) => [
                'id' => $location->id,
                'name' => $location->name,
                'region_id' => $location->region?->id,
                'region' => $location->region?->name,
                'district_id' => $location->district?->id,
                'district' => $location->district?->name,
                'display' => trim(($location->name ?? '').', '.($location->district?->name ?? '').', '.($location->region?->name ?? ''), ', '),
            ]),
        ]);
    }

    /**
     * The phone sends its finished packages (photo + details) in one go.
     */
    public function storePackages(Request $request, $sessionId)
    {
        $validated = $request->validate([
            'packages' => 'required|array|min:1',
            'packages.*.path' => 'required|string|max:255',
            'packages.*.description' => 'nullable|string|max:255',
            'packages.*.quantity' => 'nullable|integer|min:1|max:10000',
            'packages.*.delivery_fee' => 'nullable|numeric|min:0|max:9999999.99',
            'packages.*.delivery_method' => 'nullable|in:direct,bus_handoff',
            'packages.*.recipient_name' => 'nullable|string|max:255',
            'packages.*.recipient_phone' => 'nullable|string|max:20',
            'packages.*.region_id' => 'nullable|integer',
            'packages.*.district_id' => 'nullable|integer',
            'packages.*.town' => 'nullable|string|max:255',
            'packages.*.landmark' => 'nullable|string|max:255',
            'packages.*.instructions' => 'nullable|string|max:1000',
        ]);

        // Only accept photos that really belong to this session.
        $uploaded = Cache::get("walkin_photos_{$sessionId}", []);

        $packages = collect($validated['packages'])
            ->filter(fn ($package) => in_array($package['path'] ?? null, $uploaded, true))
            ->map(function ($package) {
                $package['quantity'] = max(1, (int) ($package['quantity'] ?? 1));
                $package['received_at'] = now()->toIso8601String();

                return $package;
            })
            ->values()
            ->all();

        Cache::put("walkin_packages_{$sessionId}", $packages, now()->addHours(4));

        return response()->json([
            'success' => true,
            'received' => count($packages),
            'skipped' => count($validated['packages']) - count($packages),
        ]);
    }

    // Polled by the desktop: the packages the phone has finished
    public function packages($sessionId)
    {
        $packages = Cache::get("walkin_packages_{$sessionId}", []);

        return response()->json([
            'success' => true,
            'packages' => $packages,
        ]);
    }
}