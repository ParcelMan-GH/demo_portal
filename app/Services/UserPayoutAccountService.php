<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\User;

/**
 * Where a non-vendor user gets paid.
 *
 * Vendors have had this since `VendorProfileService`; hub agents and riders did
 * not, because the columns only ever existed on `vendors`. This is the same
 * shape, plus the method and bank name that a wallet-only schema could not
 * express.
 *
 * The account number is returned in full rather than masked: the only reader is
 * the account holder editing their own details, and a masked number cannot be
 * prefilled into a form. Masking belongs on an audit trail, not here.
 */
class UserPayoutAccountService
{
    public const METHOD_MOMO = 'momo';
    public const METHOD_BANK = 'bank';

    /** The wallet networks the apps offer. Slugs, not display names. */
    public const MOMO_NETWORKS = ['mtn', 'telecel', 'airteltigo'];

    /**
     * @return array<string, mixed>
     */
    public function format(User $user): array
    {
        $method = $user->payout_method ?: null;

        return [
            // `is_set` means "the rider can be paid": a method plus a name and a
            // number. A half-filled row is not usable and is not reported as set.
            'is_set' => filled($method)
                && filled($user->payout_account_name)
                && filled($user->payout_account_number),
            'method' => $method,
            'network' => $user->payout_momo_network,
            'bank_name' => $user->payout_bank_name,
            'account_name' => $user->payout_account_name,
            'account_number' => $user->payout_account_number,
            'updated_at' => $user->payout_account_updated_at?->toISOString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(User $user, array $data): array
    {
        $method = $data['method'];

        $user->payout_method = $method;
        $user->payout_account_name = $data['account_name'];
        $user->payout_account_updated_at = now();

        if ($method === self::METHOD_MOMO) {
            $user->payout_momo_network = $data['momo_network'];
            $user->payout_bank_name = null;
            // Stored in the local 0XXXXXXXXX form, as the vendor flow does, so
            // one column never holds two shapes.
            $user->payout_account_number = PhoneHelper::format($data['account_number']);
        } else {
            $user->payout_bank_name = $data['bank_name'];
            $user->payout_momo_network = null;
            $user->payout_account_number = preg_replace('/\D/', '', (string) $data['account_number']);
        }

        $user->save();

        return [
            'success' => true,
            'message' => 'Payout account updated.',
            'data' => ['payout_account' => $this->format($user->fresh())],
        ];
    }
}
