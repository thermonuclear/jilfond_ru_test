<?php

namespace Tests\Feature;

use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_report_request(): void
    {
        $response = $this->postJson('/api/reports', [
            'user_id' => 1,
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.date_from', '2024-01-01T00:00:00+00:00');

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_show_returns_report_status(): void
    {
        $report = Report::create([
            'user_id' => 1,
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/reports/{$report->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.status', 'pending');
    }

    public function test_download_fails_when_not_ready(): void
    {
        $report = Report::create([
            'user_id' => 1,
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/reports/{$report->id}/download");

        $response->assertStatus(400);
    }

    public function test_download_succeeds_when_ready(): void
    {
        Storage::fake('local');

        $report = Report::create([
            'user_id' => 1,
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
            'status' => 'ready',
            'file_path' => 'reports/test.csv',
        ]);

        Storage::disk('local')->put('reports/test.csv', 'test,content');

        $response = $this->getJson("/api/reports/{$report->id}/download");

        $response->assertStatus(200);
    }

    public function test_validation_requires_user_id(): void
    {
        $response = $this->postJson('/api/reports', [
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_validation_requires_dates(): void
    {
        $response = $this->postJson('/api/reports', [
            'user_id' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_from', 'date_to']);
    }

    public function test_validation_date_to_after_date_from(): void
    {
        $response = $this->postJson('/api/reports', [
            'user_id' => 1,
            'date_from' => '2024-12-31',
            'date_to' => '2024-01-01',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date_to']);
    }
}
