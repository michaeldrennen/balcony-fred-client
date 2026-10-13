<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient;

use DateTimeImmutable;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MichaelDrennen\Balcony\FredClient\DTO\FredObservationDTO;
use MichaelDrennen\Balcony\FredClient\Exceptions\BalconyClientException;

final class Client
{
    private readonly ClientInterface $http;

    public function __construct(
        private readonly Config $config,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new GuzzleClient([
            'timeout' => $config->timeoutSeconds,
            'http_errors' => false,
        ]);
    }

    /**
     * @return FredObservationDTO[]
     */
    public function fetchObservations(
        string $seriesId,
        ?int $limit = null,
        ?string $observationStart = null,
    ): array {
        $query = [
            'series_id' => $seriesId,
            'api_key' => $this->config->apiKey,
            'file_type' => 'json',
            'sort_order' => 'desc',
        ];

        if ($limit !== null) {
            $query['limit'] = $limit;
        }

        if ($observationStart !== null) {
            $query['observation_start'] = $observationStart;
        }

        try {
            $response = $this->http->request('GET', $this->config->baseUrl, ['query' => $query]);
        } catch (GuzzleException $e) {
            throw BalconyClientException::transport($e->getMessage(), $e);
        }

        $status = $response->getStatusCode();

        if ($status === 429) {
            $retryHeader = $response->getHeaderLine('Retry-After');
            throw BalconyClientException::rateLimited(
                is_numeric($retryHeader) ? (int) $retryHeader : null,
            );
        }

        if ($status >= 400) {
            throw BalconyClientException::httpError($status, (string) $response->getBody());
        }

        return $this->parse((string) $response->getBody(), $seriesId);
    }

    /**
     * Parse a raw FRED JSON payload into DTOs. Missing values ("." or blank) yield a null value.
     *
     * @return FredObservationDTO[]
     */
    public function parse(string $json, string $seriesId): array
    {
        $decoded = json_decode($json, true);

        if (!is_array($decoded) || !isset($decoded['observations']) || !is_array($decoded['observations'])) {
            throw BalconyClientException::malformed('FRED payload is missing the observations array.');
        }

        $rows = [];

        foreach ($decoded['observations'] as $row) {
            if (!is_array($row) || !isset($row['date'])) {
                continue;
            }

            $observedAt = self::parseDate($row['date']);

            if ($observedAt === null) {
                continue;
            }

            $rows[] = new FredObservationDTO(
                seriesId: $seriesId,
                observedAt: $observedAt,
                value: self::parseValue($row['value'] ?? null),
            );
        }

        return $rows;
    }

    public static function parseValue(mixed $raw): ?float
    {
        if ($raw === null) {
            return null;
        }

        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        if (!is_string($raw)) {
            return null;
        }

        $trimmed = trim($raw);

        if ($trimmed === '' || $trimmed === '.') {
            return null;
        }

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }

    public static function parseDate(mixed $raw): ?DateTimeImmutable
    {
        if (!is_string($raw)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);

        return ($date !== false && $date->format('Y-m-d') === $raw) ? $date : null;
    }
}
