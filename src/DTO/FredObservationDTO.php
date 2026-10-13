<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient\DTO;

use DateTimeImmutable;

final class FredObservationDTO implements MetricObservation
{
    public function __construct(
        public readonly string $seriesId,
        public readonly DateTimeImmutable $observedAt,
        public readonly ?float $value,
    ) {
    }

    public function asModelAttributes(): array
    {
        return [
            'series_id' => $this->seriesId,
            'observed_at' => $this->observedAt->format('Y-m-d'),
            'value' => $this->value,
        ];
    }
}
