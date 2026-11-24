<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_feed_streams_valid_json_with_published_available_cars(): void
    {
        $brand = Brand::factory()->create();

        $car = Car::factory()->create([
            'brand_id' => $brand->id,
            'status' => 'available',
            'publication_status' => 'published',
            'published_at' => now()->subDay(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/api/v1/sitemap/cars');

        $response->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($car->slug, $data[0]['slug']);
        $this->assertEquals('available', $data[0]['status']);
        $this->assertEquals($car->updated_at->format('Y-m-d'), $data[0]['lastmod']);
    }

    public function test_sitemap_feed_excludes_draft_and_archived_cars(): void
    {
        $brand = Brand::factory()->create();

        // Draft car
        Car::factory()->draft()->create([
            'brand_id' => $brand->id,
        ]);

        // Archived car
        Car::factory()->archived()->create([
            'brand_id' => $brand->id,
        ]);

        $response = $this->get('/api/v1/sitemap/cars');
        $response->assertOk();

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(0, $data, 'Draft and archived cars must not appear in sitemap feed');
    }

    public function test_sitemap_feed_includes_quality_complete_sold_car(): void
    {
        $brand = Brand::factory()->create();

        // Complete sold car with image and full specs
        $soldCar = Car::factory()->sold()->create([
            'brand_id' => $brand->id,
            'publication_status' => 'published',
            'price' => 5000000,
            'mileage' => 45000,
            'engine_capacity' => 1800,
            'exterior_color' => 'White',
        ]);

        CarImage::factory()->create([
            'car_id' => $soldCar->id,
            'image_path' => 'cars/sample.webp',
            'is_primary' => true,
        ]);

        $response = $this->get('/api/v1/sitemap/cars');
        $response->assertOk();

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertEquals($soldCar->slug, $data[0]['slug']);
        $this->assertEquals('sold', $data[0]['status']);
    }

    public function test_sitemap_feed_excludes_incomplete_sold_cars(): void
    {
        $brand = Brand::factory()->create();

        // Incomplete Sold Car 1: zero images
        $soldNoImage = Car::factory()->sold()->create([
            'brand_id' => $brand->id,
            'publication_status' => 'published',
            'price' => 4000000,
            'mileage' => 50000,
            'engine_capacity' => 1500,
            'exterior_color' => 'Black',
        ]);

        // Incomplete Sold Car 2: null mileage
        $soldNoMileage = Car::factory()->sold()->create([
            'brand_id' => $brand->id,
            'publication_status' => 'published',
            'price' => 4000000,
            'mileage' => null,
            'engine_capacity' => 1500,
            'exterior_color' => 'Black',
        ]);
        CarImage::factory()->create(['car_id' => $soldNoMileage->id]);

        $response = $this->get('/api/v1/sitemap/cars');
        $response->assertOk();

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(0, $data, 'Incomplete sold cars must be excluded from sitemap');
    }

    public function test_sitemap_feed_omits_lastmod_when_timestamps_null(): void
    {
        $brand = Brand::factory()->create();

        $car = Car::factory()->create([
            'brand_id' => $brand->id,
            'status' => 'available',
            'publication_status' => 'published',
            'published_at' => null,
            'updated_at' => null,
        ]);

        $response = $this->get('/api/v1/sitemap/cars');
        $response->assertOk();

        $data = json_decode($response->streamedContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);
        $this->assertNull($data[0]['lastmod'], 'When both updated_at and published_at are null, lastmod must be null');
    }
}
