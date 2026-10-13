<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient\Tests;

use DateTimeImmutable;
use MichaelDrennen\Balcony\FredClient\DTO\FredObservationDTO;
use PHPUnit\Framework\TestCase;

final class ModelTest extends TestCase
{
    public function testDtoMapsToModelAttributes(): void
    {
        $dto = new FredObservationDTO('SOFR', new DateTimeImmutable('2026-10-09'), 4.87);

        $this->assertSame([
            'series_id' => 'SOFR',
            'observed_at' => '2026-10-09',
            'value' => 4.87,
        ], $dto->asModelAttributes());
    }

    public function testEloquentModelAcceptsDtoAttributes(): void
    {
        if (!class_exists('Illuminate\Database\Eloquent\Model')) {
            $this->markTestSkipped('illuminate/database is not installed.');
        }

        // Eloquent's guarded-attribute check reads the column listing, so the model needs a connection.
        $capsule = new \Illuminate\Database\Capsule\Manager();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $dto = new FredObservationDTO('DGS10', new DateTimeImmutable('2026-10-08'), null);
        $model = new \MichaelDrennen\Balcony\FredClient\Models\FredObservation($dto->asModelAttributes());

        $this->assertSame('DGS10', $model->series_id);
        $this->assertSame('balcony_fred_observations', $model->getTable());
        $this->assertSame(['id'], $model->getGuarded());
    }
}
