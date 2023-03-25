<?php

namespace Tests\Feature;

use Tests\TestCase;

class TagsControllerTest extends TestCase
{
    /**
     * @test
     */
    public function itListsTags(): void
    {
        $response = $this->get('/api/tags');

        $response->assertStatus(200);

        $this->assertNotNull($response->json('data')[0]['id']);
    }
}
