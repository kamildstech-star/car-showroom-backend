<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicCachingTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_cars_endpoint_populates_cache(): void
    {
        Cache::flush();
        $this->assertFalse(Cache::has('public_featured_cars'));

        $brand = Brand::factory()->create();
        Car::factory()->featured()->create(['brand_id' => $brand->id]);

        $response = $this->getJson('/api/v1/cars/featured');
        $response->assertOk();

        $this->assertTrue(Cache::has('public_featured_cars'));
    }

    public function test_latest_cars_endpoint_populates_cache(): void
    {
        Cache::flush();
        $this->assertFalse(Cache::has('public_latest_cars'));

        $brand = Brand::factory()->create();
        Car::factory()->create(['brand_id' => $brand->id]);

        $response = $this->getJson('/api/v1/cars/latest');
        $response->assertOk();

        $this->assertTrue(Cache::has('public_latest_cars'));
    }

    public function test_brands_endpoint_populates_cache(): void
    {
        Cache::flush();
        $this->assertFalse(Cache::has('public_active_brands'));

        Brand::factory()->create(['is_active' => true]);

        $response = $this->getJson('/api/v1/brands');
        $response->assertOk();

        $this->assertTrue(Cache::has('public_active_brands'));
    }

    public function test_car_mutation_invalidates_public_cache(): void
    {
        Cache::put('public_featured_cars', ['cached_data'], 300);
        Cache::put('public_latest_cars', ['cached_data'], 300);

        $brand = Brand::factory()->create();
        $car = Car::factory()->create(['brand_id' => $brand->id]);

        // Car creation (saved) should have invalidated the cache
        $this->assertFalse(Cache::has('public_featured_cars'));
        $this->assertFalse(Cache::has('public_latest_cars'));

        // Repopulate and test deletion
        Cache::put('public_featured_cars', ['cached_data'], 300);
        $car->delete();

        $this->assertFalse(Cache::has('public_featured_cars'));
    }

    public function test_brand_mutation_invalidates_public_cache(): void
    {
        Cache::put('public_active_brands', ['cached_data'], 600);

        $brand = Brand::factory()->create();

        $this->assertFalse(Cache::has('public_active_brands'));
    }
}
