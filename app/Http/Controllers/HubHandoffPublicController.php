<?php

namespace App\Http\Controllers;

use App\Services\HubBusHandoffService;
use Illuminate\View\View;

/**
 * The page a customer opens from the SMS we send when their parcel goes on a bus.
 *
 * It exists to show the handover photo, so it is read-only: no confirming, no
 * forms. The link is a random token, stored hashed, with its own expiry.
 */
class HubHandoffPublicController extends Controller
{
    public function __construct(private HubBusHandoffService $service) {}

    public function show(string $token): View
    {
        $handoff = $this->service->findByToken($token);

        return view('public.hub-handoff-proof', [
            'handoff' => $handoff ? $this->service->payload($handoff, public: true) : null,
            'photoUrl' => $handoff ? $this->service->photoUrl($handoff) : null,
        ]);
    }
}
