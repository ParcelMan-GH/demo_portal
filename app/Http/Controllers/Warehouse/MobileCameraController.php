<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Events\PhotoUploaded;
use Illuminate\Http\Request;
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

        // Broadcast the event via Reverb to the Desktop!
        broadcast(new PhotoUploaded($sessionId, $path));

        return response()->json([
            'success' => true,
            'path' => $path
        ]);
    }
}