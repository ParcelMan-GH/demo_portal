<?php

namespace App\Services\Ocr;

use App\Contracts\LabelTextExtractor;
use App\Exceptions\LabelOcrException;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Label text recognition via Google Cloud Vision.
 *
 * DOCUMENT_TEXT_DETECTION is used rather than TEXT_DETECTION: it is the mode
 * tuned for dense, irregular text, and it is materially better on handwriting —
 * which is the entire reason this exists, since the on-device recogniser is a
 * printed-text model.
 */
class GoogleVisionLabelExtractor implements LabelTextExtractor
{
    public function name(): string
    {
        return 'google_vision';
    }

    /**
     * Resolve the API key, preferring the value the admin settings screen
     * writes so ops can rotate it without touching the server.
     */
    public function apiKey(): ?string
    {
        $key = PlatformSetting::getValue('google_vision_api_key')
            ?: config('services.google_vision.key');

        $key = trim((string) $key);

        return $key === '' ? null : $key;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey() !== null;
    }

    public function extract(string $absolutePath): string
    {
        $key = $this->apiKey();

        if ($key === null) {
            throw LabelOcrException::unavailable();
        }

        if (! is_file($absolutePath)) {
            throw LabelOcrException::unreadable($absolutePath);
        }

        $limit = (int) config('services.google_vision.max_bytes', 6 * 1024 * 1024);
        $bytes = (int) filesize($absolutePath);

        if ($bytes > $limit) {
            throw LabelOcrException::tooLarge($bytes, $limit);
        }

        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            throw LabelOcrException::unreadable($absolutePath);
        }

        $endpoint = (string) config(
            'services.google_vision.endpoint',
            'https://vision.googleapis.com/v1/images:annotate'
        );

        $response = Http::timeout((int) config('services.google_vision.timeout', 25))
            ->acceptJson()
            ->asJson()
            ->post($endpoint.'?key='.urlencode($key), [
                'requests' => [[
                    'image' => ['content' => base64_encode($contents)],
                    'features' => [['type' => 'DOCUMENT_TEXT_DETECTION']],
                ]],
            ]);

        if ($response->failed()) {
            Log::warning('Google Vision label OCR request failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            throw LabelOcrException::requestFailed('HTTP '.$response->status());
        }

        $payload = $response->json();

        // Vision reports per-image problems inside a 200 response, so a failed
        // status alone is not enough to declare success here.
        $message = data_get($payload, 'responses.0.error.message');

        if (is_string($message) && $message !== '') {
            Log::warning('Google Vision returned an error for the image', [
                'message' => $message,
            ]);

            throw LabelOcrException::requestFailed($message);
        }

        $text = data_get($payload, 'responses.0.fullTextAnnotation.text')
            ?? data_get($payload, 'responses.0.textAnnotations.0.description')
            ?? '';

        return trim((string) $text);
    }
}
