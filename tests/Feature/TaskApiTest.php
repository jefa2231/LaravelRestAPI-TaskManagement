<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    private const TOKEN = 'rahasia_tugas_api_2026';

    private string $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = storage_path('framework/testing/tasks.json');

        File::delete([$this->store, $this->store.'.lock', $this->store.'.tmp']);

        config([
            'tasks.path' => $this->store,
            'services.demo_api.token' => self::TOKEN,
        ]);
    }

    protected function tearDown(): void
    {
        File::delete([$this->store, $this->store.'.lock', $this->store.'.tmp']);

        parent::tearDown();
    }

    private function api(): static
    {
        return $this
            ->withHeader('Authorization', 'Bearer '.self::TOKEN)
            ->withHeader('Accept', 'application/json');
    }

    /** @param array<string, mixed> $overrides */
    private function createTask(array $overrides = []): array
    {
        $response = $this->api()->postJson('/api/v1/tasks', array_merge([
            'title' => 'Belajar Laravel',
            'description' => 'Membuat REST API',
            'status' => 'pending',
            'due_date' => '2026-11-15',
        ], $overrides));

        $response->assertCreated();

        return $response->json('data');
    }

    public function test_index_requires_bearer_token(): void
    {
        $this->getJson('/api/v1/tasks')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_index_returns_paginated_tasks(): void
    {
        $this->createTask(['title' => 'Tugas satu']);
        $this->createTask(['title' => 'Tugas dua']);
        $this->createTask(['title' => 'Tugas tiga']);

        $response = $this->api()->getJson('/api/v1/tasks?per_page=2');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'description', 'status', 'status_label', 'due_date', 'created_at', 'updated_at'],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total', 'from', 'to'],
            ])
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_index_filters_by_status_and_keyword(): void
    {
        $this->createTask(['title' => 'Belajar Laravel', 'status' => 'pending']);
        $this->createTask(['title' => 'Menulis laporan', 'status' => 'completed']);
        $this->createTask(['title' => 'Rapat tim', 'status' => 'in_progress']);

        $completed = $this->api()->getJson('/api/v1/tasks?status=completed');

        $completed->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Menulis laporan')
            ->assertJsonPath('data.0.status_label', 'Selesai');

        $this->api()->getJson('/api/v1/tasks?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Belajar Laravel');
    }

    public function test_index_rejects_invalid_query(): void
    {
        $this->api()->getJson('/api/v1/tasks?per_page=100')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page']);

        $this->api()->getJson('/api/v1/tasks?status=selesai')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_store_creates_task_with_defaults(): void
    {
        $task = $this->createTask(['title' => 'Tugas baru']);

        $this->assertSame('pending', $task['status']);
        $this->assertSame('Menunggu', $task['status_label']);
        $this->assertStringContainsString('T00:00:00', $task['due_date']);

        $this->assertFileExists($this->store);
    }

    public function test_store_returns_location_and_201(): void
    {
        $response = $this->api()->postJson('/api/v1/tasks', [
            'title' => 'Tugas dengan lokasi',
        ]);

        $response->assertCreated();

        $id = $response->json('data.id');

        $response->assertHeader('Location', url('/api/v1/tasks/'.$id));
    }

    public function test_store_validates_payload(): void
    {
        $this->api()->postJson('/api/v1/tasks', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title'])
            ->assertJsonPath('errors.title.0', 'Judul tugas wajib diisi.');

        $this->api()->postJson('/api/v1/tasks', [
            'title' => 'Judul valid',
            'status' => 'selesai',
        ])->assertUnprocessable()->assertJsonValidationErrors(['status']);
    }

    public function test_show_returns_task_and_404_for_unknown_id(): void
    {
        $task = $this->createTask();

        $this->api()->getJson('/api/v1/tasks/'.$task['id'])
            ->assertOk()
            ->assertJsonPath('data.id', $task['id'])
            ->assertJsonPath('data.status_label', 'Menunggu');

        $this->api()->getJson('/api/v1/tasks/tidak-ada')
            ->assertNotFound()
            ->assertJson(['message' => 'Tugas tidak ditemukan.'])
            ->assertJsonMissingPath('exception');
    }

    public function test_update_changes_fields_and_timestamp(): void
    {
        $task = $this->createTask(['title' => 'Judul lama']);

        $this->travel(1)->seconds();

        $updated = $this->api()->patchJson('/api/v1/tasks/'.$task['id'], [
            'title' => 'Judul baru',
            'status' => 'in_progress',
        ]);

        $updated->assertOk()
            ->assertJsonPath('data.title', 'Judul baru')
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.status_label', 'Dikerjakan');

        $this->assertNotSame($task['updated_at'], $updated->json('data.updated_at'));
    }

    public function test_update_returns_404_for_unknown_id(): void
    {
        $this->api()->patchJson('/api/v1/tasks/tidak-ada', ['title' => 'X'])
            ->assertNotFound()
            ->assertJson(['message' => 'Tugas tidak ditemukan.']);
    }

    public function test_destroy_removes_task(): void
    {
        $task = $this->createTask();

        $this->api()->deleteJson('/api/v1/tasks/'.$task['id'])
            ->assertNoContent();

        $this->api()->getJson('/api/v1/tasks/'.$task['id'])
            ->assertNotFound();
    }

    public function test_unknown_endpoint_returns_json_404(): void
    {
        $this->api()->getJson('/api/v1/tidak-ada')
            ->assertNotFound()
            ->assertJson(['message' => 'Endpoint tidak ditemukan.']);

        $this->api()->postJson('/api/v1/tidak-ada', ['title' => 'X'])
            ->assertNotFound()
            ->assertJson(['message' => 'Endpoint tidak ditemukan.']);
    }

    public function test_corrupted_store_returns_500_without_leaking_path(): void
    {
        File::ensureDirectoryExists(dirname($this->store));
        File::put($this->store, '{bukan json');

        $this->api()->getJson('/api/v1/tasks')
            ->assertStatus(500)
            ->assertJson(['message' => 'Penyimpanan data tugas tidak dapat dibaca.'])
            ->assertJsonMissingPath('exception');
    }
}
