<?php

namespace App\Helpers;

use App\Models\OutgoingBatch;
use App\Models\ShipmentItem;

/**
 * One place that knows how a ParcelMan code is written, and how to find it.
 *
 * Codes are user-facing: they are printed, scanned off a label, and read aloud
 * down a phone line. That is why the prefix changed from `PCM-` to `PM-`, and it
 * is also why a scan must never fail because someone typed the old prefix, or
 * pasted a code with the prefix missing entirely.
 *
 * Every new code carries `PM-`. Every lookup goes through this class, so a code
 * written under any previous scheme still resolves:
 *
 *   PM-2026-00038   PCM-2026-00038   2026-00038        shipment number
 *   PM-KQ7XW2MNP    TRKKQ7XW2MNP     KQ7XW2MNP         parcel tracking code
 *   PM-BATCH-IQKWKJ BATCH-IQKWKJ      IQKWKJ           consignment batch
 *
 * Rows already in the database are never rewritten — a code printed on a parcel
 * in the warehouse has to keep working. Both forms resolve instead.
 */
class CodeResolver
{
    /** The prefix every newly generated code carries. */
    public const PREFIX = 'PM';

    /** Prefixes earlier codes were written with. Still resolved, never issued. */
    public const LEGACY_PREFIXES = ['PCM'];

    /** Marks a parcel tracking code: `TRKKQ7XW2MNP`. */
    public const TRACKING_MARKER = 'TRK';

    /** Marks a consignment batch: `BATCH-IQKWKJ`. */
    public const BATCH_MARKER = 'BATCH';

    /** Prefixes peeled off the front of a code before it is identified. */
    private const LEAD_PREFIXES = [self::PREFIX.'-', 'PCM-'];

    /**
     * Uppercase and trim. Codes are stored uppercase, but they arrive from
     * scanners, keyboards and copy-paste in whatever case the source used.
     */
    public static function normalize(?string $code): string
    {
        return strtoupper(trim((string) $code));
    }

    /**
     * The identifying part of a code, with the leading prefix removed.
     *
     * The `BATCH-` marker is deliberately kept: it is what identifies a
     * consignment batch, and it has to survive so the code can be rebuilt under
     * a different leading prefix.
     *
     *   PM-BATCH-IQKWKJ -> BATCH-IQKWKJ
     *   BATCH-IQKWKJ    -> BATCH-IQKWKJ
     *   PCM-2026-00038  -> 2026-00038
     *   TRKKQ7XW2MNP    -> KQ7XW2MNP
     */
    public static function body(?string $code): string
    {
        $code = self::normalize($code);

        foreach (self::LEAD_PREFIXES as $prefix) {
            if (str_starts_with($code, $prefix)) {
                return substr($code, strlen($prefix));
            }
        }

        if (str_starts_with($code, self::TRACKING_MARKER)) {
            return substr($code, strlen(self::TRACKING_MARKER));
        }

        return $code;
    }

    /** Which kind of code this is: shipment, tracking, batch, or unknown. */
    public static function family(?string $code): string
    {
        $body = self::body($code);

        if ($body === '') {
            return 'unknown';
        }

        if (str_starts_with($body, self::BATCH_MARKER.'-')) {
            return 'batch';
        }

        if (preg_match('/^\d{4}-\d+$/', $body) === 1) {
            return 'shipment';
        }

        if (preg_match('/^[A-Z0-9]{4,}$/', $body) === 1) {
            return 'tracking';
        }

        return 'unknown';
    }

    /**
     * Every database value the scanned code could have been stored as, most
     * likely first: exactly what was scanned, then the canonical spelling, then
     * the scheme it was probably written under.
     *
     * @return array<int, string>
     */
    public static function candidates(?string $raw): array
    {
        $raw = self::normalize($raw);

        if ($raw === '') {
            return [];
        }

        $candidates = [$raw];
        $body = self::body($raw);

        if ($body === '') {
            return $candidates;
        }

        switch (self::family($body)) {
            case 'batch':
                $candidates[] = self::PREFIX.'-'.$body;
                $candidates[] = $body;
                break;

            case 'tracking':
                $candidates[] = self::PREFIX.'-'.$body;
                $candidates[] = self::TRACKING_MARKER.$body;
                $candidates[] = $body;
                break;

            case 'shipment':
                $candidates[] = self::PREFIX.'-'.$body;
                foreach (self::LEGACY_PREFIXES as $legacy) {
                    $candidates[] = $legacy.'-'.$body;
                }
                $candidates[] = $body;
                break;

            default:
                // Unrecognised shape: try it verbatim, then under the canonical
                // prefix, so a hand-written code still has a chance.
                $candidates[] = self::PREFIX.'-'.$body;
                break;
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    /**
     * The distinctive part of a search term, for a `LIKE` comparison.
     *
     * Stripping the prefix is what makes search prefix-agnostic: `PCM-2026-00038`
     * becomes `2026-00038`, which matches a row stored as either `PCM-2026-00038`
     * or `PM-2026-00038`, because both contain it.
     *
     * A term with no prefix is returned untouched, so ordinary searching behaves
     * exactly as it did before.
     */
    public static function searchTerm(string $term): string
    {
        $trimmed = trim($term);

        if ($trimmed === '') {
            return $trimmed;
        }

        // Match the prefix case-insensitively but strip it from the original
        // string, so a search for "Kofi Mensah" is not silently upper-cased.
        $upper = self::normalize($trimmed);
        $stripLength = 0;

        foreach (self::LEAD_PREFIXES as $prefix) {
            if (str_starts_with($upper, $prefix)) {
                $stripLength = strlen($prefix);
                break;
            }
        }

        if ($stripLength === 0) {
            if (str_starts_with($upper, self::BATCH_MARKER.'-')) {
                $stripLength = strlen(self::BATCH_MARKER) + 1;
            } elseif (str_starts_with($upper, self::TRACKING_MARKER)) {
                $stripLength = strlen(self::TRACKING_MARKER);
            }
        }

        $body = $stripLength > 0 ? substr($trimmed, $stripLength) : $trimmed;

        // Too short to be distinctive: searching for it would match everything,
        // so fall back to what the user actually typed.
        if (strlen($body) < 3) {
            return $trimmed;
        }

        return $body;
    }

    /**
     * A `LIKE` operand that matches the term under any prefix scheme.
     *
     * Note this does not escape `%` or `_` — callers that need escaping already
     * do it themselves, and did so before this helper existed.
     */
    public static function likeTerm(?string $term): string
    {
        return '%'.self::searchTerm((string) $term).'%';
    }

    /**
     * The parcel a scanned code refers to, or null.
     *
     * Tries the literal code first and the numeric primary key second: a barcode
     * reading "123" is far more likely to be a tracking code than a request for
     * row 123, and the old order had it the other way round.
     */
    public static function resolveShipmentItem(?string $code): ?ShipmentItem
    {
        $code = self::normalize($code);

        if ($code === '') {
            return null;
        }

        $item = ShipmentItem::query()->whereIn('tracking_code', self::candidates($code))->first();

        if ($item) {
            return $item;
        }

        if (ctype_digit($code)) {
            return ShipmentItem::query()->whereKey((int) $code)->first();
        }

        return null;
    }

    /** The consignment batch a scanned code refers to, or null. */
    public static function resolveOutgoingBatch(?string $code): ?OutgoingBatch
    {
        $code = self::normalize($code);

        if ($code === '') {
            return null;
        }

        $candidates = self::candidates($code);
        $body = self::body($code);

        // A batch number read aloud may come back without its BATCH marker, or a
        // label may have been printed before the marker existed. Try the marked
        // forms too — after the exact ones, so they cannot shadow a real match.
        if ($body !== '' && ! str_starts_with($body, self::BATCH_MARKER.'-')) {
            $candidates[] = self::PREFIX.'-'.self::BATCH_MARKER.'-'.$body;
            $candidates[] = self::BATCH_MARKER.'-'.$body;
        }

        return OutgoingBatch::query()
            ->whereIn('batch_number', array_values(array_unique($candidates)))
            ->first();
    }

    /** `2026-00038` -> `PM-2026-00038`. */
    public static function formatShipmentNumber(string $prefix, string $body): string
    {
        return rtrim($prefix, '-').'-'.ltrim($body, '-');
    }

    /** The canonical prefix to write new codes with, from settings. */
    public static function issuePrefix(): string
    {
        return rtrim(self::PREFIX, '-');
    }
}
