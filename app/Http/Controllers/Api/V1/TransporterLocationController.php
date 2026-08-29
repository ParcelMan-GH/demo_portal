namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransporterLocationController extends Controller
{
    public function updateLocation(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'heading' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
        ]);

        $user = $request->user();

        // Update driver's last known position
        $user->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_location_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}