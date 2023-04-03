<?php

namespace Tests\Feature;

use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfficeImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function itUploadsAnOfficeImageAndStoresIt(): void
    {
        Storage::fake();

        $user = User::factory()->create();
        $office = Office::factory()->for($user)->create();

        $this->actingAs($user);

        $response = $this->post('/api/offices/'.$office->id.'/images', [
            'image' => UploadedFile::fake()->image('image.jpg')
        ]);

        Storage::assertExists($response->json('data.path'));

        $response->assertCreated();
    }

//    /**
//     * @test
//     */
//    public function itDeletesAnImage()
//    {
//        Storage::put('/office_image.jpg', 'empty');
//
//        $user = User::factory()->create();
//        $office = Office::factory()->for($user)->create();
//
//        $office->images()->create([
//            'path' => 'image.jpg'
//        ]);
//
//        $image = $office->images()->create([
//            'path' => 'office_image.jpg'
//        ]);
//
//        $this->actingAs($user);
//
//        $response = $this->deleteJson("/offices/{$office->id}/images/{$image->id}");
//
//        $response->assertOk();
//
//        $this->assertModelMissing($image);
//
//        Storage::assertMissing('office_image.jpg');
//    }

}
