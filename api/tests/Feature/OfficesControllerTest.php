<?php

namespace Tests\Feature;

use App\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        Office::factory()->count(3)->create();

        $response = $this->get('/api/offices');

        $response->assertOk();

        $this->assertNotNull($response->json('data')[0]['id']);
    }
}
