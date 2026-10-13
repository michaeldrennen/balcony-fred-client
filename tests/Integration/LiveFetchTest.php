<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient\Tests\Integration;

use DateTimeImmutable;
use MichaelDrennen\Balcony\FredClient\Client;
use MichaelDrennen\Balcony\FredClient\Config;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Hits the real remote source. Excluded from the default run; use `vendor/bin/phpunit --group integration`.
 */
#[Group('integration')]
final class LiveFetchTest extends TestCase
{
    private const MAX_AGE_DAYS = 14;

    public function testFetchesRecentDataFromLiveSource(): void
    {
        $key = getenv('FRED_API_KEY');

        if (!is_string($key) || trim($key) === '') {
            $this->markTestSkipped('Set FRED_API_KEY to run this live test.');
        }

        $dtos = (new Client(new Config($key)))->fetchObservations('SOFR', limit: 10);

        $this->assertNotEmpty($dtos, 'Expected the latest SOFR observations from FRED.');

        $newest = max(array_map(static fn ($dto): DateTimeImmutable => $dto->observedAt, $dtos));
        $ageDays = (int) $newest->diff(new DateTimeImmutable())->format('%a');

        $this->assertLessThanOrEqual(
            self::MAX_AGE_DAYS,
            $ageDays,
            sprintf('Newest record is from %s; the source may have stopped updating.', $newest->format('Y-m-d')),
        );
    }
}
