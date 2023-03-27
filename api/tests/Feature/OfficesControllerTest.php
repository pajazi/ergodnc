<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tags;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficesControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function itListsAllOfficesInPaginatedWay(): void
    {
        Office::factory()->count(3)->create();

        $response = $this->get('/api/offices');

        $response->assertOk();

        $this->assertNotNull($response->json('data')[0]['id']);
        $this->assertNotNull($response->json('meta'));
        $this->assertNotNull($response->json('links'));
    }

    /**
     * @test
     */
    public function itOnlyListsOfficesThatAreApprovedAndVisible(): void
    {
        Office::factory(3)->create();

        Office::factory()->create(['hidden' => true]);
        Office::factory()->create(['approval_status' => Office::APPROVAL_PENDING]);

        $response = $this->get('/api/offices');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    /**
     * @test
     */
    public function itFiltersByUserId(): void
    {
        Office::factory(3)->create();

        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->get("/api/offices?user_id={$host->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    /**
     * @test
     */
    public function itFiltersByVisitorId(): void
    {
        Office::factory(3)->create();

        $user = User::factory()->create();
        $office = Office::factory()->create();

        Reservation::factory()->for(Office::factory())->create();
        Reservation::factory()->for($office)->for($user)->create();

        $response = $this->get("/api/offices?visitor_id={$user->id}");
        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $this->assertEquals($office->id, $response->json('data')[0]['id']);
    }

    /**
     * @test
     */
    public function itIncludesImagesTagsUser(): void
    {
        $user = User::factory()->create();
        $tag = Tags::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tag);
        $office->images()->create(['path' => 'image.jpg']);

        $response = $this->get("/api/offices");
        $response->assertOk();
        $this->assertIsArray($response->json('data')[0]['tags']);
        $this->assertIsArray($response->json('data')[0]['images']);
        $this->assertEquals($response->json('data')[0]['user']['id'], $user->id);
    }

    /**
     * @test
     */
    public function itReturnsNumberOfActiveReservations(): void
    {
        $office = Office::factory()->create();

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get("/api/offices");
        $response->assertOk();
        $this->assertEquals(1, $response->json('data')[0]['reservations_count']);
    }

    /**
     * @test
     */
    public function itOrdersByDistanceWhenCoordinatesAreProvided(): void
    {
        $office1 = Office::factory()->create([
            'lat' => '39.74051727562952',
            'lng' => '-8.770375324893696',
            'title' => 'Leiria'
        ]);

        $office2 = Office::factory()->create([
            'lat' => '39.07753883078113',
            'lng' => '-9.281266331143293',
            'title' => 'Torres Vedras'
        ]);

        $response = $this->get('/api/offices?lat=38.720661384644046&lng=-9.16044783453807');

        $response->assertOk();
        $this->assertEquals('Torres Vedras', $response->json('data')[0]['title']);
        $this->assertEquals('Leiria', $response->json('data')[1]['title']);

        $response = $this->get('/api/offices');

        $response->assertOk();
        $this->assertEquals('Leiria', $response->json('data')[0]['title']);
        $this->assertEquals('Torres Vedras', $response->json('data')[1]['title']);
    }

    /**
     * @test
     */
    public function itShowsTheOffice(): void
    {
        $user = User::factory()->create();
        $tag = Tags::factory()->create();
        $office = Office::factory()->for($user)->create();

        $office->tags()->attach($tag);
        $office->images()->create(['path' => 'image.jpg']);

        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_ACTIVE]);
        Reservation::factory()->for($office)->create(['status' => Reservation::STATUS_CANCELLED]);

        $response = $this->get("/api/offices/{$office->id}");
        $response->assertOk();
        $this->assertEquals($office->title, $response->json('data')['title']);
    }

    /**
     * @test
     */
    public function itCreatesAnOffice(): void
    {
        $user = User::factory()->createQuietly();
        $tag = Tags::factory()->create();
        $tag2 = Tags::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/api/offices', [
            'title' => 'Office in Arkansas',
            'description' => 'Description',
            'lat' => '39.74051727562952',
            'lng' => '-8.770375324893696',
            'address_line1' => 'Address Line 1',
            'price_per_day' => 10_000,
            'monthly_discount' => 5,
            'tags' => [$tag->id, $tag2->id],
            'INVALID' => 'WRONG'
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'Office in Arkansas')
            ->assertJsonPath('data.approval_status', Office::APPROVAL_PENDING)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('offices', [
            'title' => 'Office in Arkansas'
        ]);
    }

    /**
     * @test
     */
    public function itDoesntAllowCreatingIfScopeIsNotProvided(): void
    {
        $user = User::factory()->createQuietly();

        $token = $user->createToken('test', []);

        $response = $this->postJson('/api/offices', [], [
            'Authorization' => 'Bearer '.$token->plainTextToken
        ]);

        $response->assertStatus(403);
    }
}
