# ParcelMan Express — Receipt/Label Redesign

## v2 — revisions applied (feedback from colleague)
Changes in `receipt-v2.html` / `receipt-v2.png`:
1. **Customer phone numbers removed** from the FROM/TO blocks — the label travels with the parcel, so personal contact numbers are dropped for privacy (names + addresses remain). The footer support number was also removed (was a placeholder) — footer is now brand text only.
2. **Black & white printer safe.** The printer is monochrome/thermal, so:
   - Header is a **solid dark band** (`#111827`) — this is exactly what the brand orange renders as in grayscale, so the printed output is intentional and crisp (orange variant kept as a reference for colour printers).
   - All contrast is grayscale-safe: white-on-dark header text, black-on-white body, outlined/high-contrast chips. No element depends on colour to be legible.
3. Structure, hierarchy, QR tile, barcode zone, tear-off divider unchanged — same layout the colleague already approved.

## Why the current one falls flat
The current label is a monochrome, text-only print with zero hierarchy:
- No brand presence (plain black "PARCELMAN" text, no color, no logo lockup)
- No visual flow — FROM/TO/PACKAGE/SHIPMENT all read at the same weight, so the eye doesn't know where to start
- Functional codes (QR, barcode) are presented as noise rather than as useful scannable elements
- No status, no contact/support info, no polish — it feels like a draft, not a product

## Design direction — "Clean logistics, brand-forward"

**1. Brand color as the anchor.** The admin portal is already orange (#E2762B). The label uses a deep-orange header band with the ParcelMan wordmark + "EXPRESS" lockup and a subtle truck glyph. One strong brand moment at the top makes the whole piece feel like a company product rather than a system printout.

**2. Clear reading order (F-pattern).**
- Header band → brand + receipt type + scannable QR
- FROM → TO as two clearly separated sender/recipient blocks with icons (the two things a courier checks first)
- Package + shipment meta in a compact 2-column strip
- Barcode zone at the bottom (the thing a scanner reads last)
This mirrors how the label is actually used in the field: glance → verify sender/recipient → scan.

**3. Typography.** Single modern sans stack (system Inter-like), strong weight contrast: small uppercase eyebrow labels (9px, tracked) vs bold names vs light detail text. Numbers and codes in tabular/mono style so scanning and reading codes is easy.

**4. Scannable elements treated as features.** QR chip sits in a white rounded tile inside the orange header; barcode sits on a dedicated white band with the tracking number as the caption — high contrast for scanners, tidy for humans.

**5. Receipt cues.** A dashed tear-off divider and a compact summary line ("1 parcel · PCM-2026-00016") give it the feel of a receipt, not just a shipping sticker. Footer carries support contact + hotline so any handler can reach ops.

**6. Print-safety.** A6 (105×148mm) portrait, 300+ DPI-ready, high-contrast barcode zone on white, no content within the outer 5mm margin. Colors are print-friendly (orange is rich enough to survive grayscale).

## The two ideas presented to the colleague
1. **Primary (recommended):** the branded A6 label above — brand moment + field-optimized hierarchy.
2. **Compact variant:** the same system as a wallet/thermal-receipt width strip for optional use at pickup counters (orange header, condensed rows, same barcode zone).

---

*Mockup file: `receipt-redesign/receipt.html` — rendered with real sample data from the current system (sender Big Bryan, recipient Adjo, PCM-2026-00016 / TRK88RNPGQZ-001). QR/barcode visuals are placeholders; production generates the real codes.*
