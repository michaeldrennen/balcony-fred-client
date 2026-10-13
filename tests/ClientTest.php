<?php

declare(strict_types=1);

namespace MichaelDrennen\Balcony\FredClient\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use MichaelDrennen\Balcony\FredClient\Client;
use MichaelDrennen\Balcony\FredClient\Config;
use MichaelDrennen\Balcony\FredClient\Exceptions\BalconyClientException;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private const API_KEY = '0123456789abcdef0123456789abcdef';

    private function fixture(): string
    {
        return (string) file_get_contents(__DIR__ . '/Fixtures/sample_response.json');
    }

    private function clientWith(MockHandler $mock): Client
    {
        $http = new GuzzleClient(['handler' => HandlerStack::create($mock), 'http_errors' => false]);

        return new Client(new Config(self::API_KEY), $http);
    }

    public function testParsesFixtureIntoDtos(): void
    {
        $client = $this->clientWith(new MockHandler([new Response(200, [], $this->fixture())]));

        $dtos = $client->fetchObservations('SOFR', limit: 100);

        $this->assertCount(2, $dtos);
        $this->assertSame('SOFR', $dtos[0]->seriesId);
        $this->assertSame('2026-10-09', $dtos[0]->observedAt->format('Y-m-d'));
        $this->assertSame(4.87, $dtos[0]->value);
    }

    public function testMissingValueDotBecomesNull(): void
    {
        $client = new Client(new Config(self::API_KEY));
        $dtos = $client->parse($this->fixture(), 'DGS10');

        $this->assertNull($dtos[1]->value);
        $this->assertSame('DGS10', $dtos[1]->seriesId);
    }

    public function testRateLimitThrowsWithRetryAfter(): void
    {
        $client = $this->clientWith(new MockHandler([
            new Response(429, ['Retry-After' => '30'], '{}'),
        ]));

        try {
            $client->fetchObservations('DGS2');
            $this->fail('Expected BalconyClientException.');
        } catch (BalconyClientException $e) {
            $this->assertTrue($e->isRateLimited());
            $this->assertSame(30, $e->retryAfterSeconds);
        }
    }

    public function testServerErrorThrows(): void
    {
        $client = $this->clientWith(new MockHandler([new Response(500, [], 'boom')]));

        $this->expectException(BalconyClientException::class);
        $client->fetchObservations('DGS2');
    }

    public function testMalformedPayloadThrows(): void
    {
        $client = new Client(new Config(self::API_KEY));

        $this->expectException(BalconyClientException::class);
        $client->parse('{"nope":true}', 'SOFR');
    }

    public function testInvalidApiKeyRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Config('not-a-key');
    }

    public function testParseValueCoercions(): void
    {
        $this->assertNull(Client::parseValue(null));
        $this->assertNull(Client::parseValue('.'));
        $this->assertNull(Client::parseValue(''));
        $this->assertSame(1.5, Client::parseValue('1.5'));
        $this->assertSame(2.0, Client::parseValue(2));
    }
}
