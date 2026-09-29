<?php

namespace App\Services\Ocr;

use Imagick;
use Throwable;

/**
 * Prepares a label photograph for text recognition.
 *
 * A phone photo of a waybill is a hard input: the phone was held however it
 * suited the driver, the lighting was whatever the room had, and the label may be
 * faded or glossy. Three fixes address most of it, in this order:
 *
 *  1. **Orientation.** A capture taken sideways stores the pixels unrotated and
 *     an orientation tag beside them; a recogniser reads pixels, not tags, so the
 *     text arrives on its side and nothing matches. `autoOrient()` bakes the tag
 *     into the pixels.
 *  2. **Contrast.** `normalizeImage()` stretches the histogram, so a faded or
 *     under-exposed label uses the full range instead of a compressed band of it.
 *  3. **Thresholding.** `adaptiveThresholdImage()` binarises against a *local*
 *     mean rather than one global value, which is the only kind that survives the
 *     uneven lighting of a phone flash or a single ceiling bulb. A plain
 *     `thresholdImage()` would black out whichever half of the label was darker.
 *
 * This lives outside the extractors deliberately. The `LabelTextExtractor`
 * contract states it is "only responsible for *recognising text*", and keeping
 * that boundary means swapping Vision for Textract still gets these fixes for
 * free — see `PreprocessedLabelExtractor`.
 *
 * It is an optimisation, never a precondition. Anything that fails here returns
 * the caller's original path so recognition proceeds exactly as it did before
 * this existed; a server without the Imagick extension is simply unbinated.
 */
class LabelImagePreprocessor
{
    /** Shared prefix so cleanup() can tell our temp files from a real upload path. */
    private const TEMP_PREFIX = 'label_ocr_';

    /**
     * @return string Path to the prepared image, or the original path if the
     *                image could not be improved.
     */
    public function prepare(string $absolutePath): string
    {
        if (! extension_loaded('imagick') || ! is_file($absolutePath)) {
            return $absolutePath;
        }

        $prepared = null;

        try {
            $image = new Imagick($absolutePath);

            $image->autoOrient();

            // Grayscale first: the recogniser works on intensity, and the colour
            // is only noise once the label is being binarised.
            $image->transformImageColorspace(Imagick::COLORSPACE_GRAY);
            $image->normalizeImage();

            $geometry = $image->getImageGeometry();
            $width = $geometry['width'] ?? 0;
            $height = $geometry['height'] ?? 0;

            if ($width < 1 || $height < 1) {
                $image->clear();
                $image->destroy();

                return $absolutePath;
            }

            /*
             * Neighbourhood of roughly an eighth of the image, capped so a huge
             * photo does not wash out individual characters. The offset is the
             * constant taken off the local mean before comparing — 15% of the
             * quantum range is the usual starting point and keeps faint strokes
             * that a heavier offset would drop.
             */
            $radiusX = max(3, (int) round($width / 8));
            $radiusY = max(3, (int) round($height / 8));
            $quantumRange = $image->getQuantumRange();
            $offset = (int) round(0.15 * ($quantumRange['quantumRangeLong'] ?? 65535));

            $image->adaptiveThresholdImage($radiusX, $radiusY, $offset);
            $image->setImageFormat('png');

            // An explicit .png name: the path is handed to the recogniser, and
            // some decoders sniff the extension rather than the magic bytes.
            $prepared = sys_get_temp_dir() . '/' . self::TEMP_PREFIX . bin2hex(random_bytes(8)) . '.png';

            if (! $image->writeImage($prepared)) {
                $image->clear();
                $image->destroy();

                return $absolutePath;
            }

            $image->clear();
            $image->destroy();

            return $prepared;
        } catch (Throwable $e) {
            if (is_string($prepared)) {
                $this->cleanup($prepared);
            }

            return $absolutePath;
        }
    }

    /**
     * Delete a file this class created.
     *
     * Guarded on the temp prefix so a caller can safely pass any path — passing
     * the original upload must never delete it.
     */
    public function cleanup(string $path): void
    {
        if (str_contains(basename($path), self::TEMP_PREFIX) && is_file($path)) {
            @unlink($path);
        }
    }
}
