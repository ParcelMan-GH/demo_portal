<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\LabelTextExtractor;
use App\Exceptions\LabelOcrException;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Reads the text off a photograph of a parcel label.
 *
 * Used by the apps only when on-device recognition cannot cope with a label —
 * ML Kit's Latin recogniser is a printed-text model and returns fragments from
 * handwriting. This returns plain text rather than parsed fields, because the
 * app already has a tested parser and duplicating it in PHP would guarantee the
 * two drift apart.
 */
class LabelOcrController extends Controller
{
    public function __construct(
        private readonly LabelTextExtractor $extractor,
    ) {
    }

    /**
     * POST /api/v1/ocr/label
     */
    public function extract(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ]);

        // Checked before the call so an unconfigured server says so plainly
        // instead of the app guessing why nothing came back.
        if (! $this->extractor->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Server-side label scanning is not configured on this server.',
            ], 503);
        }

        try {
            // Read straight from the upload's temporary path and never stored:
            // label photos carry recipient names and phone numbers, and there is
            // no reason for them to accumulate on the server.
            $text = $this->extractor->extract($request->file('photo')->getRealPath());
        } catch (LabelOcrException $e) {
            Log::warning('Label OCR failed', ['message' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Could not read that image. Please enter the details manually.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $text === '' ? 'No text found in that image.' : 'Label read.',
            'data' => [
                'text' => $text,
                'provider' => $this->extractor->name(),
                'has_text' => $text !== '',
            ],
        ]);
    }
}
