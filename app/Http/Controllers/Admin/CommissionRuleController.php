<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommissionTier;
use App\Services\Hub\HubAgentCommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin CRUD for the commission bands.
 *
 * These bands are the entire payout ladder — `CommissionTier::findTierForAmount()`
 * reads them on every earnings calculation — so a mistake here pays agents the
 * wrong money rather than merely looking wrong. The validation is therefore the
 * substance of this controller, not a formality.
 */
class CommissionRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.commissions.rules');
    }

    /**
     * The table's data source.
     *
     * `activeOnly` is deliberately NOT applied: an admin has to be able to see a
     * band they switched off, or it becomes invisible and unrecoverable from the
     * UI while still sitting in the table.
     */
    public function data(HubAgentCommissionService $hubCommissions): JsonResponse
    {
        $bands = CommissionTier::orderBy('min_collection')->get();

        return response()->json([
            'success' => true,
            'data' => [
                /*
                 * The hub agent's switch and per-parcel rates, alongside the agent
                 * ladder. They are different shapes of money — a band function of a
                 * day's collection versus a flat amount per parcel — but an admin
                 * manages both from this one screen, so one payload carries both.
                 */
                'hub_agent' => $hubCommissions->settings(),
                'bands' => $bands->map(fn (CommissionTier $t) => [
                    'id' => $t->id,
                    'min' => (float) $t->min_collection,
                    'max' => $t->max_collection === null ? null : (float) $t->max_collection,
                    'amount' => (float) $t->payout_amount,
                    'is_active' => (bool) $t->is_active,
                    'label' => $this->label($t),
                ])->all(),
                'summary' => [
                    'total' => $bands->count(),
                    'active' => $bands->where('is_active', true)->count(),
                    'lowest' => $bands->min('min_collection'),
                    'highest' => $bands->max('min_collection'),
                    // Surfaced so an admin can see at a glance whether the ladder
                    // is continuous — the failure that matters here is a gap,
                    // where a whole amount range silently pays nothing.
                    'gaps' => $this->gaps($bands),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validated($request, null);

        $tier = CommissionTier::create($payload);

        return response()->json([
            'success' => true,
            'message' => "Band {$this->label($tier)} created.",
            'data' => ['id' => $tier->id],
        ], 201);
    }

    public function update(Request $request, int $band): JsonResponse
    {
        $tier = CommissionTier::findOrFail($band);
        $payload = $this->validated($request, $tier->id);

        $tier->update($payload);

        return response()->json([
            'success' => true,
            'message' => "Band {$this->label($tier->fresh())} updated.",
        ]);
    }

    public function destroy(int $band): JsonResponse
    {
        $tier = CommissionTier::findOrFail($band);

        /*
         * Refuse to delete the only band. With none left,
         * `findTierForAmount()` returns null for every amount and commission
         * silently becomes 0 for every agent — a total payout outage caused by a
         * single click, recoverable only from a backup.
         */
        if (CommissionTier::count() <= 1) {
            throw ValidationException::withMessages([
                'band' => 'The last band cannot be deleted — agents would earn nothing.',
            ]);
        }

        $label = $this->label($tier);
        $tier->delete();

        return response()->json([
            'success' => true,
            'message' => "Band {$label} deleted.",
        ]);
    }

    /**
     * Switch the hub agent's per-parcel commission on or off, and set its rates.
     *
     * Persisted through the same `platform_settings` store every other admin
     * setting uses, so it round-trips exactly like the ladder above does. The
     * two rates are validated as money — non-negative numbers — because a credit
     * is written from them directly; an unvalidated free-text rate would fail at
     * the first intake rather than at the point it was typed.
     */
    public function saveHubAgent(Request $request, HubAgentCommissionService $hubCommissions): JsonResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'inbound_rate' => ['required', 'numeric', 'min:0'],
            'outbound_rate' => ['required', 'numeric', 'min:0'],
        ]);

        $hubCommissions->saveSettings(
            (bool) $validated['enabled'],
            (float) $validated['inbound_rate'],
            (float) $validated['outbound_rate'],
        );

        return response()->json([
            'success' => true,
            'message' => 'Hub agent commission updated.',
            'data' => ['hub_agent' => $hubCommissions->settings()],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?int $ignoreId): array
    {
        $validated = $request->validate([
            'min_collection' => ['required', 'numeric', 'min:0'],
            // Null is meaningful: no upper bound, i.e. the top band. That is what
            // makes the ladder a ceiling rather than stopping dead at some number.
            'max_collection' => ['nullable', 'numeric'],
            'payout_amount' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $min = (float) $validated['min_collection'];
        $max = isset($validated['max_collection']) && $validated['max_collection'] !== null
            ? (float) $validated['max_collection']
            : null;

        if ($max !== null && $max < $min) {
            throw ValidationException::withMessages([
                'max_collection' => 'The upper threshold must not be below the lower threshold.',
            ]);
        }

        if ($overlap = $this->overlappingBand($min, $max, $ignoreId)) {
            /*
             * Overlap is rejected rather than resolved. `findTierForAmount()`
             * picks the highest `min_collection` that brackets the amount, so two
             * bands covering the same figure do not error at calculation time —
             * one quietly wins. That would make the payout depend on which band
             * happened to be created later, which is exactly the kind of bug that
             * surfaces as "the commission is wrong for some agents" months on.
             */
            throw ValidationException::withMessages([
                'min_collection' => "That range overlaps the band {$this->label($overlap)}. Bands must not overlap.",
            ]);
        }

        return [
            'min_collection' => $min,
            'max_collection' => $max,
            'payout_amount' => (float) $validated['payout_amount'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ];
    }

    /**
     * The first existing band that shares any amount with [$min, $max].
     *
     * Two ranges overlap when the existing band reaches the new lower bound
     * (`max >= min`, or unbounded) AND the existing band starts at or below the
     * new upper bound (`min <= max`, or the new band is unbounded).
     */
    private function overlappingBand(float $min, ?float $max, ?int $ignoreId): ?CommissionTier
    {
        return CommissionTier::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($min, $max) {
                $query->where(function ($lower) use ($min) {
                    $lower->whereNull('max_collection')
                        ->orWhere('max_collection', '>=', $min);
                });

                if ($max !== null) {
                    $query->where('min_collection', '<=', $max);
                }
            })
            ->orderBy('min_collection')
            ->first();
    }

    private function label(?CommissionTier $tier): string
    {
        if (! $tier) {
            return '—';
        }

        $min = number_format((float) $tier->min_collection, 2);
        $max = $tier->max_collection === null
            ? 'above'
            : number_format((float) $tier->max_collection, 2);

        return "{$min} – {$max}";
    }

    /**
     * Amount ranges that fall between two bands and therefore pay nothing.
     *
     * Reporting only. A gap is not invalid — the ladder is allowed to have
     * unprofitable ranges — but it should never be a surprise, so it is shown
     * rather than enforced.
     *
     * @param  \Illuminate\Support\Collection<int, CommissionTier>  $bands
     * @return array<int, array{after: string, before: string}>
     */
    private function gaps($bands): array
    {
        $gaps = [];
        $rows = $bands->sortBy('min_collection')->values();

        foreach ($rows as $i => $band) {
            $next = $rows[$i + 1] ?? null;

            if (! $next) {
                continue;
            }

            // An unbounded band swallows everything above it, so nothing after it
            // can be reachable — itself worth flagging, but not a gap.
            if ($band->max_collection === null) {
                continue;
            }

            if ((float) $band->max_collection < (float) $next->min_collection - 0.01) {
                $gaps[] = [
                    'after' => number_format((float) $band->max_collection, 2),
                    'before' => number_format((float) $next->min_collection, 2),
                ];
            }
        }

        return $gaps;
    }
}
