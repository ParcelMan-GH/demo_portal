<?php

namespace App\Http\Controllers;

use App\Services\BusHandoffConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BusHandoffPublicController extends Controller
{
    public function __construct(private BusHandoffConfirmationService $service)
    {
    }

    public function show(string $token): View
    {
        $handoff = $this->service->publicPayload($token);

        return view('public.bus-handoff-confirmation', [
            'token' => $token,
            'handoff' => $handoff,
            'reasons' => $this->service->activeReasons(),
        ]);
    }

    public function confirm(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->service->publicConfirm($token, $validated['notes'] ?? null);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function issue(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'reason_id' => ['required', 'integer', 'exists:delivery_failure_reasons,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->service->publicIssue($token, (int) $validated['reason_id'], $validated['notes'] ?? null);

        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }
}
