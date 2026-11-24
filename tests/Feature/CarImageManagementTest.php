<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CarImageManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders([
            'referer' => 'http://localhost:3000',
            'origin' => 'http://localhost:3000',
            'accept' => 'application/json',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@hassanmotors.pk',
        ]);

        $brand = Brand::factory()->create();
        $this->car = Car::factory()->create([
            'brand_id' => $brand->id,
            'publication_status' => 'published',
        ]);
    }

    /**
     * Unauthenticated requests to image management endpoints return 401.
     */
    public function test_unauthenticated_request_to_image_endpoints_returns_unauthorized(): void
    {
        $this->postJson("/api/v1/admin/cars/{$this->car->id}/images", [])
            ->assertStatus(401);

        $this->deleteJson("/api/v1/admin/cars/{$this->car->id}/images/1")
            ->assertStatus(401);

        $this->patchJson("/api/v1/admin/cars/{$this->car->id}/images/1/primary")
            ->assertStatus(401);

        $this->putJson("/api/v1/admin/cars/{$this->car->id}/images/reorder", ['image_ids' => [1]])
            ->assertStatus(401);
    }

    /**
     * Admin can upload multiple images for a car.
     */
    public function test_admin_can_upload_multiple_images_for_a_car(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('front.jpg', 800, 600);
        $file2 = UploadedFile::fake()->image('back.png', 800, 600);

        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/api/v1/admin/cars/{$this->car->id}/images", [
                'images' => [$file1, $file2],
                'alt_texts' => ['Front view', 'Rear view'],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');

        $data = $response->json('data');

        // First image should be auto-designated as primary
        $this->assertTrue($data[0]['is_primary']);
        $this->assertSame(0, $data[0]['sort_order']);
        $this->assertSame('Front view', $data[0]['alt_text']);
        $this->assertNotNull($data[0]['url']);
        $this->assertNotNull($data[0]['thumbnail_url']);

        // Second image is non-primary with incremented sort_order
        $this->assertFalse($data[1]['is_primary']);
        $this->assertSame(1, $data[1]['sort_order']);
        $this->assertSame('Rear view', $data[1]['alt_text']);

        // Physical files exist in storage (WebP variants)
        Storage::disk('public')->assertExists($data[0]['image_path']);
        Storage::disk('public')->assertExists($data[0]['thumbnail_path']);
        Storage::disk('public')->assertExists($data[1]['image_path']);
        Storage::disk('public')->assertExists($data[1]['thumbnail_path']);

        $this->assertDatabaseCount('car_images', 2);
    }

    /**
     * Upload rejects non-image files and disallowed MIME types.
     */
    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');

        $textDoc = UploadedFile::fake()->create('contract.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/api/v1/admin/cars/{$this->car->id}/images", [
                'images' => [$textDoc],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);

        $this->assertDatabaseCount('car_images', 0);
    }

    /**
     * Upload rejects files exceeding maximum size (5MB).
     */
    public function test_upload_rejects_oversized_images(): void
    {
        Storage::fake('public');

        $hugeImage = UploadedFile::fake()->create('huge.jpg', 6000, 'image/jpeg');

        $response = $this->actingAs($this->admin, 'web')
            ->postJson("/api/v1/admin/cars/{$this->car->id}/images", [
                'images' => [$hugeImage],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['images.0']);

        $this->assertDatabaseCount('car_images', 0);
    }

    /**
     * Admin can set an image as primary.
     */
    public function test_admin_can_set_primary_image(): void
    {
        $img1 = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $img2 = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/cars/{$this->car->id}/images/{$img2->id}/primary");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $img2->id)
            ->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('car_images', [
            'id' => $img1->id,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('car_images', [
            'id' => $img2->id,
            'is_primary' => true,
        ]);
    }

    /**
     * Setting primary fails if the image belongs to a different car.
     */
    public function test_cannot_set_primary_image_belonging_to_another_car(): void
    {
        $otherCar = Car::factory()->create();
        $otherImage = CarImage::factory()->create([
            'car_id' => $otherCar->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/cars/{$this->car->id}/images/{$otherImage->id}/primary");

        $response->assertStatus(404);
    }

    /**
     * Admin can reorder car images.
     */
    public function test_admin_can_reorder_car_images(): void
    {
        $img1 = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 0]);
        $img2 = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 1]);
        $img3 = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 2]);

        $response = $this->actingAs($this->admin, 'web')
            ->putJson("/api/v1/admin/cars/{$this->car->id}/images/reorder", [
                'image_ids' => [$img3->id, $img1->id, $img2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');

        $this->assertEquals(0, $img3->fresh()->sort_order);
        $this->assertEquals(1, $img1->fresh()->sort_order);
        $this->assertEquals(2, $img2->fresh()->sort_order);
    }

    /**
     * Reordering rejects image IDs that belong to another car.
     */
    public function test_reorder_rejects_foreign_image_ids(): void
    {
        $img1 = CarImage::factory()->create(['car_id' => $this->car->id, 'sort_order' => 0]);
        $otherCar = Car::factory()->create();
        $foreignImage = CarImage::factory()->create(['car_id' => $otherCar->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->putJson("/api/v1/admin/cars/{$this->car->id}/images/reorder", [
                'image_ids' => [$img1->id, $foreignImage->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image_ids']);
    }

    /**
     * Admin can delete a car image and physical files are purged after DB commit.
     */
    public function test_admin_can_delete_car_image_and_files_are_purged(): void
    {
        Storage::fake('public');

        $mainPath = "cars/{$this->car->id}/test-image.webp";
        $thumbPath = "cars/{$this->car->id}/thumbs/test-image.webp";
        Storage::disk('public')->put($mainPath, 'fake-main-data');
        Storage::disk('public')->put($thumbPath, 'fake-thumb-data');

        $image = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'image_path' => $mainPath,
            'thumbnail_path' => $thumbPath,
            'is_primary' => false,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/admin/cars/{$this->car->id}/images/{$image->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Database record deleted
        $this->assertDatabaseMissing('car_images', ['id' => $image->id]);

        // Physical files deleted from storage
        Storage::disk('public')->assertMissing($mainPath);
        Storage::disk('public')->assertMissing($thumbPath);
    }

    /**
     * Deleting the primary image automatically promotes the next image to primary.
     */
    public function test_deleting_primary_image_promotes_next_image(): void
    {
        Storage::fake('public');

        $img1 = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $img2 = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/admin/cars/{$this->car->id}/images/{$img1->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('car_images', ['id' => $img1->id]);
        $this->assertTrue($img2->fresh()->is_primary);
    }

    /**
     * Deleting fails if the image belongs to another car.
     */
    public function test_cannot_delete_image_belonging_to_another_car(): void
    {
        $otherCar = Car::factory()->create();
        $foreignImage = CarImage::factory()->create(['car_id' => $otherCar->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/admin/cars/{$this->car->id}/images/{$foreignImage->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('car_images', ['id' => $foreignImage->id]);
    }

    /**
     * Public car endpoint returns primary image and ordered gallery with URLs.
     */
    public function test_public_car_endpoint_returns_images_with_urls(): void
    {
        $img1 = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'image_path' => "cars/{$this->car->id}/photo1.webp",
            'thumbnail_path' => "cars/{$this->car->id}/thumbs/photo1.webp",
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $img2 = CarImage::factory()->create([
            'car_id' => $this->car->id,
            'image_path' => "cars/{$this->car->id}/photo2.webp",
            'thumbnail_path' => "cars/{$this->car->id}/thumbs/photo2.webp",
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $response = $this->getJson("/api/v1/cars/{$this->car->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.primary_image.id', $img1->id)
            ->assertJsonPath('data.primary_image.thumbnail_path', "cars/{$this->car->id}/thumbs/photo1.webp")
            ->assertJsonCount(2, 'data.images');
    }

    /**
     * Upload failure rolls back database and cleans up written storage files.
     */
    public function test_upload_failure_rolls_back_and_cleans_up_files(): void
    {
        Storage::fake('public');

        $file1 = UploadedFile::fake()->image('front.jpg', 800, 600);

        // Force a DB failure by mocking or injecting an invalid database state during creation
        $this->mock(\App\Services\CarImageService::class, function ($mock) {
            $mock->makePartial();
        });

        // Or directly call service with an invalid car ID that throws or mock DB exception
        $service = app(\App\Services\CarImageService::class);

        // Let's create a car with an invalid ID after transaction starts by deleting the car mid-way
        // or test directly with an invalid state
        $tempCar = Car::factory()->create();
        
        try {
            // Emulate an exception after file is saved
            \Illuminate\Support\Facades\Event::listen('eloquent.creating: App\Models\CarImage', function () {
                throw new \RuntimeException('Database crash simulation.');
            });

            $service->uploadImages($tempCar, [$file1]);
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Database crash simulation.', $e->getMessage());
        }

        // Database should be empty for this car
        $this->assertDatabaseMissing('car_images', ['car_id' => $tempCar->id]);

        // Storage files must have been cleaned up
        $allFiles = Storage::disk('public')->allFiles();
        $this->assertEmpty($allFiles, 'Stored files were not cleaned up during rollback.');
    }
}
