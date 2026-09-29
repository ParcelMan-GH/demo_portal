<?php

namespace App\Services\Ocr;

use App\Contracts\LabelTextExtractor;

/**
 * Wraps an extractor so every read runs against a prepared image.
 *
 * A decorator rather than a change inside the extractor, because the contract
 * states it is "only responsible for *recognising text*". Keeping that true means
 * the improvement applies to whatever engine is bound — swapping Vision for
 * Textract still gets binarisation for nothing — and the engine class stays
 * untouched, which is the part worth not disturbing.
 *
 * Bound in AppServiceProvider. Nothing else needs to know it exists.
 */
class PreprocessedLabelExtractor implements LabelTextExtractor
{
    public function __construct(
        private readonly LabelTextExtractor $inner,
        private readonly LabelImagePreprocessor $preprocessor,
    ) {
    }

    public function extract(string $absolutePath): string
    {
        $prepared = $this->preprocessor->prepare($absolutePath);

        try {
            $text = $this->inner->extract($prepared);

            /*
             * Binarisation helps a badly lit photo and can, on a label with a
             * gradient or a glossy patch, pull text into the background instead.
             * One retry on the untouched original keeps a readable label readable.
             *
             * The cost is bounded and only incurred when the prepared read found
             * nothing at all: a label with no text on it is the one case that pays
             * for two calls, and that is the case where the answer is worth
             * confirming rather than reporting on a single guess.
             */
            if ($text === '' && $prepared !== $absolutePath) {
                $text = $this->inner->extract($absolutePath);
            }

            return $text;
        } finally {
            // Never leaks the temp file, including when the engine throws.
            if ($prepared !== $absolutePath) {
                $this->preprocessor->cleanup($prepared);
            }
        }
    }

    /**
     * The engine's identifier, unchanged.
     *
     * Preprocessing is a transport concern and the engine has not changed, so the
     * value the apps already log stays the same rather than gaining a suffix their
     * diagnostics would not expect.
     */
    public function name(): string
    {
        return $this->inner->name();
    }

    public function isConfigured(): bool
    {
        return $this->inner->isConfigured();
    }
}
