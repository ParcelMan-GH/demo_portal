<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when server-side label reading cannot complete.
 */
class LabelOcrException extends RuntimeException
{
    public static function unavailable(): self
    {
        return new self('Server-side label scanning is not configured on this server.');
    }

    public static function unreadable(string $path): self
    {
        return new self("The uploaded image could not be read from disk: {$path}");
    }

    public static function tooLarge(int $bytes, int $limit): self
    {
        return new self("The image is {$bytes} bytes, over the {$limit} byte limit.");
    }

    public static function requestFailed(string $reason): self
    {
        return new self("The text recognition service could not read the image: {$reason}");
    }
}
