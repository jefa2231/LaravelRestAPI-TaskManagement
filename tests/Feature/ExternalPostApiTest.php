<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExternalPostApiTest extends TestCase
{
    private const TOKEN = 'rahasia_tugas_api_2026';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.demo_api.token' => self::TOKEN,
            'services.external_api.url' => 'https://contoh.test/posts',
            'services.external_api.timeout' => 1,
            'services.external_api.retry' => 1,
        ]);
    }

    private function api(): static
    {
        return $this
            ->withHeader('Authorization', 'Bearer '.self::TOKEN)
            ->withHeader('Accept', 'application/json');
    }

    public function test_external_endpoint_requires_token(): void
    {
        $this->getJson('/api/v1/external/posts')
            ->assertUnauthorized();
    }

    public function test_external_posts_are_proxied(): void
    {
        Http::fake([
            'contoh.test/*' => Http::response([
                ['id' => 1, 'title' => 'Judul contoh'],
            ], 200),
        ]);

        $this->api()->getJson('/api/v1/external/posts')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Judul contoh');

        Http::assertSent(fn (Request $request): bool =>
            $request->url() === 'https://contoh.test/posts'
        );
    }

    public function test_external_api_failure_returns_502(): void
    {
        Http::fake([
            'contoh.test/*' => Http::response('', 500),
        ]);

        $this->api()->getJson('/api/v1/external/posts')
            ->assertStatus(502)
            ->assertJson(['message' => 'API eksternal tidak dapat dihubungi.']);
    }

    public function test_connection_failure_returns_502(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->api()->getJson('/api/v1/external/posts')
            ->assertStatus(502)
            ->assertJson(['message' => 'API eksternal tidak dapat dihubungi.']);
    }

    public function test_non_json_response_returns_502(): void
    {
        Http::fake([
            'contoh.test/*' => Http::response('bukan json', 200, ['Content-Type' => 'text/plain']),
        ]);

        $this->api()->getJson('/api/v1/external/posts')
            ->assertStatus(502)
            ->assertJson(['message' => 'Respons API eksternal bukan JSON yang valid.']);
    }
}
