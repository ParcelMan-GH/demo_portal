<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfilePhotoService;
use App\Services\UserPayoutAccountService;
use App\Services\UserProfileService;
use App\Support\PhoneHelper;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The signed-in user's own profile, whatever app they signed in through.
 *
 * Kept separate from the hub endpoints because a hub agent, a bus handoff agent
 * and a vendor all authenticate as the same `users` row; changing an avatar is
 * not a hub operation and should not require a hub role.
 */
class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $service,
        private UserPayoutAccountService $payoutAccounts,
    ) {}

    /**
     * The signed-in user, with an absolute photo URL.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->service->getProfile($request->user()));
    }

    /**
     * Replace the signed-in user's profile photo.
     *
     * POST rather than PUT: PHP only populates uploaded files on a POST body, so
     * a multipart PUT arrives at the server empty.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate(ProfilePhotoService::rules());

        return response()->json(
            $this->service->updatePhoto($request->user(), $request->file('photo'))
        );
    }

    /**
     * Update the details the agent maintains about themselves.
     *
     * POST rather than PATCH so a client can send this as multipart alongside a
     * photo if it ever needs both in one trip.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        // A plain `unique` rule cannot see across the two shapes the phone column
        // holds, so duplicates are checked through the same normaliser the login
        // uses. Without it, `024...` and `+233...` could be taken by two accounts
        // and the phone login would resolve ambiguously.
        if (! blank($validated['phone'] ?? null)) {
            $taken = PhoneNumber::match(User::query(), (string) $validated['phone'])
                ->whereKeyNot($user->getKey())
                ->exists();

            if ($taken) {
                throw ValidationException::withMessages([
                    'phone' => 'That phone number already belongs to another account.',
                ]);
            }
        }

        return response()->json($this->service->update($user, $validated));
    }

    /**
     * The signed-in user's payout account.
     *
     * The agent app has always had a screen for this and never had an endpoint,
     * so whatever the rider typed went nowhere.
     */
    public function payoutAccount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Payout account retrieved.',
            'data' => ['payout_account' => $this->payoutAccounts->format($request->user())],
        ]);
    }

    /**
     * Save the signed-in user's payout account.
     *
     * Momo and bank are different shapes, so each validates its own fields and
     * the service clears the other's. Without that, switching from a wallet to a
     * bank would leave the old network on the row and the record would claim two
     * accounts at once.
     */
    public function updatePayoutAccount(Request $request): JsonResponse
    {
        $method = $request->input('method');
        $momo = UserPayoutAccountService::METHOD_MOMO;
        $bank = UserPayoutAccountService::METHOD_BANK;

        $validated = $request->validate([
            'method' => ['required', Rule::in([$momo, $bank])],
            'account_name' => ['required', 'string', 'max:255'],
            'momo_network' => [
                'nullable',
                'required_if:method,'.$momo,
                Rule::in(UserPayoutAccountService::MOMO_NETWORKS),
            ],
            'bank_name' => [
                'nullable',
                'required_if:method,'.$bank,
                'string',
                'max:120',
            ],
            'account_number' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) use ($momo, $bank, $method) {
                if ($method === $momo) {
                    $local = PhoneHelper::toLocal((string) $value);

                    if (! $local || ! preg_match('/^0(?:2\d|5\d)\d{7}$/', $local)) {
                        $fail('Enter a valid 10-digit Ghana mobile money number.');
                    }

                    return;
                }

                if ($method === $bank
                    && ! preg_match('/^\d{8,20}$/', (string) preg_replace('/\D/', '', (string) $value))) {
                    $fail('Enter a valid bank account number.');
                }
            }],
        ]);

        return response()->json($this->payoutAccounts->update($request->user(), $validated));
    }
}
