<?php

declare(strict_types=1);

namespace ShortURL\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class ShortLink
{
    public function __construct(
        public string $code,
        public string $destinationUrl,
        public DateTimeImmutable $createdAt,
    ) {
        if (! self::hasValidCode($code)) {
            throw new InvalidArgumentException('Invalid short code.');
        }

        $parts = parse_url($destinationUrl);

        $isValidDestinationUrl = $parts !== false
            && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
            && isset($parts['host'])
            && filter_var($destinationUrl, FILTER_VALIDATE_URL) !== false;

        if (! $isValidDestinationUrl) {
            throw new InvalidArgumentException('Invalid URL string.');
        }
    }

    public static function hasValidCode(string $code): bool
    {
        return preg_match('~^[A-Za-z0-9_-]{8}$~', $code) === 1;
    }
}
