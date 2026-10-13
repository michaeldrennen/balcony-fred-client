<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient;

use InvalidArgumentException;

final class Config
{
    public const DEFAULT_BASE_URL = 'https://api.stlouisfed.org/fred/series/observations';

    public function __construct(
        public readonly string $apiKey,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly float $timeoutSeconds = 15.0,
    ) {
        if (preg_match('/^[a-f0-9]{32}$/i', $apiKey) !== 1) {
            throw new InvalidArgumentException('FRED API key must be a 32-character hexadecimal string.');
        }
    }
}
