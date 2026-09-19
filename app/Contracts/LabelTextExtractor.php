<?php

namespace App\Contracts;

/**
 * Reads the text off a photograph of a parcel label.
 *
 * Deliberately only responsible for *recognising text*. Parsing a name, a phone
 * number and a town out of the result stays in one place — the app's
 * labelOcr.ts, which already has a tested suite — so this never needs a second
 * implementation of that logic in PHP.
 *
 * Swapping Google Vision for Textract or a vision model means writing one more
 * class against this interface and rebinding it.
 */
interface LabelTextExtractor
{
    /**
     * Identifier reported back to the app, for diagnostics.
     */
    public function name(): string;

    /**
     * Whether credentials are present. Callers should check this rather than
     * relying on an exception, so an unconfigured server can say so plainly.
     */
    public function isConfigured(): bool;

    /**
     * Recognise the text in an image on the local filesystem.
     *
     * Returns an empty string when the image simply contains nothing readable,
     * which is not an error worth failing the request over.
     *
     * @throws \App\Exceptions\LabelOcrException when the call itself fails.
     */
    public function extract(string $absolutePath): string;
}
