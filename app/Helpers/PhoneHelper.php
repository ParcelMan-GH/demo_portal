<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Format a Ghana phone number to international format (+233xxxxxxxxx).
     *
     * Accepts:
     * - 0241234567 (local format)
     * - 241234567 (without leading zero)
     * - +233241234567 (international format)
     * - 233241234567 (international without +)
     *
     * @param string $phone
     * @return string|null Returns formatted number or null if invalid
     */
    public static function format(string $phone): ?string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If already in +233 format
        if (preg_match('/^\+233[0-9]{9}$/', $phone)) {
            return $phone;
        }

        // If in 233xxxxxxxxx format (without +)
        if (preg_match('/^233[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }

        // If in 0xxxxxxxxx format (local with leading 0)
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return '+233' . substr($phone, 1);
        }

        // If in xxxxxxxxx format (9 digits, no leading 0)
        if (preg_match('/^[2-9][0-9]{8}$/', $phone)) {
            return '+233' . $phone;
        }

        return null; // Invalid format
    }

    /**
     * Validate if a phone number is a valid Ghana number.
     *
     * @param string $phone
     * @return bool
     */
    public static function isValid(string $phone): bool
    {
        return self::format($phone) !== null;
    }

    /**
     * Get the local format (0xxxxxxxxx) from any valid Ghana number.
     *
     * @param string $phone
     * @return string|null
     */
    public static function toLocal(string $phone): ?string
    {
        $formatted = self::format($phone);
        if (!$formatted) {
            return null;
        }

        // +233xxxxxxxxx -> 0xxxxxxxxx
        return '0' . substr($formatted, 4);
    }

    /**
     * Common Ghana mobile prefixes for validation.
     * MTN: 024, 054, 055, 059
     * Vodafone: 020, 050
     * AirtelTigo: 027, 057, 026, 056
     */
    public static function getValidPrefixes(): array
    {
        return ['20', '24', '25', '26', '27', '50', '54', '55', '56', '57', '59'];
    }

    /**
     * Check if the number has a valid Ghana mobile prefix.
     *
     * @param string $phone
     * @return bool
     */
    public static function hasValidPrefix(string $phone): bool
    {
        $formatted = self::format($phone);
        if (!$formatted) {
            return false;
        }

        // Extract the 2 digits after +233
        $prefix = substr($formatted, 4, 2);
        return in_array($prefix, self::getValidPrefixes());
    }
}
