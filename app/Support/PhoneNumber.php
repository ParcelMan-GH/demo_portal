<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Phone numbers as this database actually stores them.
 *
 * `users.phone` is not uniform. 15 of 16 rows hold the E.164 shape
 * `+233XXXXXXXXX`, but one holds `024XXXXXXXX` with no country code. A rider
 * typing the local form therefore cannot be found by string comparison alone,
 * so lookups go through here and compare on the subscriber number — the last
 * nine digits — which both shapes share.
 */
class PhoneNumber
{
    /**
     * The canonical shape this app writes: `+` followed by country code and
     * subscriber number.
     *
     * A leading zero is a national trunk prefix, so it is replaced by the
     * country code rather than kept: `0244123456` -> `+233244123456`. The same
     * conversion applies to `0441234567`, which is still `+233441234567`.
     */
    public static function normalise(?string $phone): ?string
    {
        $digits = self::digits($phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '233'.substr($digits, 1);
        }

        return '+'.$digits;
    }

    /**
     * Just the digits, with any `+`, spaces, dashes or parentheses removed.
     */
    public static function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    /**
     * The subscriber number, which is what survives the two stored shapes.
     */
    public static function subscriber(?string $phone): string
    {
        $digits = self::digits($phone);

        return strlen($digits) >= 9 ? substr($digits, -9) : $digits;
    }

    /**
     * Constrain a query to rows whose phone matches, whatever shape either side
     * is in. Exact matches are tried first because they are cheaper and
     * unambiguous; the subscriber comparison is the fallback.
     */
    public static function match(Builder $query, string $phone): Builder
    {
        $digits = self::digits($phone);
        $subscriber = self::subscriber($phone);

        $exact = array_values(array_unique(array_filter([
            trim($phone),
            $digits,
            '+'.$digits,
        ])));

        return $query->where(function (Builder $inner) use ($exact, $subscriber) {
            if ($exact !== []) {
                $inner->whereIn('phone', $exact);
            }

            if ($subscriber !== '') {
                $inner->orWhereRaw(
                    "RIGHT(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), 9) = ?",
                    [$subscriber]
                );
            }
        });
    }
}
