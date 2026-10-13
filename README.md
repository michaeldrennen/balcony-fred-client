# balcony-fred-client

PHP 8.1+ client for the St. Louis Fed FRED `series/observations` endpoint, part of The Balcony macro flow system.

## Usage (framework-agnostic)

```php
use MichaelDrennen\Balcony\FredClient\Client;
use MichaelDrennen\Balcony\FredClient\Config;

$client = new Client(new Config(apiKey: 'your32characterhexkeyhere00000000'));

foreach ($client->fetchObservations('SOFR', limit: 100) as $dto) {
    // $dto->seriesId, $dto->observedAt (DateTimeImmutable), $dto->value (?float)
}
```

Missing observations (FRED returns `"."`) are returned with `value = null`.

## Laravel persistence

```php
use MichaelDrennen\Balcony\FredClient\Models\FredObservation;

foreach ($client->fetchObservations('DGS10') as $dto) {
    FredObservation::updateOrCreate(
        ['series_id' => $dto->seriesId, 'observed_at' => $dto->observedAt->format('Y-m-d')],
        $dto->asModelAttributes(),
    );
}
```

Run `php artisan migrate` after the package is installed (the migration is in `database/migrations`), or apply `database/schema.sql` directly.

## Errors

All failures throw `MichaelDrennen\Balcony\FredClient\Exceptions\BalconyClientException`. HTTP 429 responses set `isRateLimited()` to true and expose `retryAfterSeconds` when FRED sends a `Retry-After` header.

## Tests

```
composer install
vendor/bin/phpunit
```
