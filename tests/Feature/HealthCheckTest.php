<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    /**
     * Test the /api/v1/health endpoint returns successful status and database connectivity.
     */
    public function test_health_check_returns_ok_and_connected_database(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'service',
                'version',
                'environment',
                'timestamp',
                'database',
            ])
            ->assertJson([
                'status' => 'ok',
                'database' => 'connected',
            ]);
    }

    /**
     * Test route is reachable with correct JSON headers.
     */
    public function test_health_check_has_json_content_type(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200);
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }
}
