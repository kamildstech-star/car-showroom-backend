<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarInventoryApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Brand $toyota;
    private Brand $honda;

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

        $this->toyota = Brand::factory()->create([
            'name' => 'Toyota',
            'slug' => 'toyota',
            'is_active' => true,
        ]);

        $this->honda = Brand::factory()->create([
            'name' => 'Honda',
            'slug' => 'honda',
            'is_active' => true,
        ]);
    }

    // ==========================================
    // Public API Tests
    // ==========================================

    /**
     * Public listing returns only published cars.
     */
    public function test_public_cars_returns_only_published_cars(): void
    {
        $published = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'publication_status' => 'published',
            'status' => 'available',
        ]);

        $draft = Car::factory()->draft()->create([
            'brand_id' => $this->toyota->id,
        ]);

        $archived = Car::factory()->archived()->create([
            'brand_id' => $this->toyota->id,
        ]);

        $response = $this->getJson('/api/v1/cars');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id', 'brand', 'model', 'variant', 'title', 'slug',
                        'year', 'price', 'price_formatted', 'status', 'is_featured',
                    ],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($published->id));
        $this->assertFalse($ids->contains($draft->id));
        $this->assertFalse($ids->contains($archived->id));
    }

    /**
     * Public listing filters by brand.
     */
    public function test_public_cars_filters_by_brand(): void
    {
        $toyotaCar = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'publication_status' => 'published',
        ]);

        $hondaCar = Car::factory()->create([
            'brand_id' => $this->honda->id,
            'publication_status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cars?brand=toyota');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($toyotaCar->id));
        $this->assertFalse($ids->contains($hondaCar->id));
    }

    /**
     * Public listing filters by price and year range.
     */
    public function test_public_cars_filters_by_price_and_year(): void
    {
        $cheapOld = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'price' => 3000000.00,
            'year' => 2019,
            'publication_status' => 'published',
        ]);

        $expensiveNew = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'price' => 8000000.00,
            'year' => 2023,
            'publication_status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cars?min_price=5000000&min_year=2021');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($cheapOld->id));
        $this->assertTrue($ids->contains($expensiveNew->id));
    }

    /**
     * Public listing filters by keyword search.
     */
    public function test_public_cars_search_filter(): void
    {
        $corolla = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'model' => 'Corolla',
            'variant' => 'Altis Grande',
            'publication_status' => 'published',
        ]);

        $civic = Car::factory()->create([
            'brand_id' => $this->honda->id,
            'model' => 'Civic',
            'variant' => 'Oriel',
            'publication_status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/cars?search=Grande');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($corolla->id));
        $this->assertFalse($ids->contains($civic->id));
    }

    /**
     * Public featured endpoint returns only featured published cars.
     */
    public function test_public_featured_cars_endpoint(): void
    {
        $featured = Car::factory()->featured()->create([
            'brand_id' => $this->toyota->id,
            'publication_status' => 'published',
            'status' => 'available',
        ]);

        $notFeatured = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'publication_status' => 'published',
            'status' => 'available',
            'is_featured' => false,
        ]);

        $response = $this->getJson('/api/v1/cars/featured');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($featured->id));
        $this->assertFalse($ids->contains($notFeatured->id));
    }

    /**
     * Public latest cars endpoint returns published cars.
     */
    public function test_public_latest_cars_endpoint(): void
    {
        $car = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'publication_status' => 'published',
            'status' => 'available',
        ]);

        $response = $this->getJson('/api/v1/cars/latest');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($car->id));
    }

    /**
     * Public car detail resolves by published slug.
     */
    public function test_public_car_detail_by_slug(): void
    {
        $car = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'slug' => 'toyota-corolla-grande-2022',
            'publication_status' => 'published',
        ]);

        CarImage::factory()->primary()->create([
            'car_id' => $car->id,
            'image_path' => 'cars/front.webp',
        ]);

        $response = $this->getJson('/api/v1/cars/toyota-corolla-grande-2022');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $car->id,
                    'slug' => 'toyota-corolla-grande-2022',
                ],
            ]);
    }

    /**
     * Public car detail returns 404 for draft car.
     */
    public function test_public_car_detail_returns_404_for_draft(): void
    {
        $car = Car::factory()->draft()->create([
            'brand_id' => $this->toyota->id,
            'slug' => 'draft-car-slug',
        ]);

        $response = $this->getJson('/api/v1/cars/draft-car-slug');

        $response->assertStatus(404);
    }

    /**
     * Public brands list returns active brands with count of published cars.
     */
    public function test_public_brands_endpoint(): void
    {
        Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'publication_status' => 'published',
        ]);

        $response = $this->getJson('/api/v1/brands');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'is_active', 'cars_count'],
                ],
            ]);
    }

    // ==========================================
    // Admin API Tests
    // ==========================================

    /**
     * Admin endpoints reject unauthenticated access.
     */
    public function test_unauthenticated_request_to_admin_cars_returns_401(): void
    {
        $this->getJson('/api/v1/admin/cars')->assertStatus(401);
        $this->postJson('/api/v1/admin/cars', [])->assertStatus(401);
    }

    /**
     * Admin can view all cars including drafts and archived.
     */
    public function test_admin_can_view_all_cars(): void
    {
        $draft = Car::factory()->draft()->create(['brand_id' => $this->toyota->id]);
        $published = Car::factory()->create(['brand_id' => $this->toyota->id, 'publication_status' => 'published']);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/admin/cars');

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($draft->id));
        $this->assertTrue($ids->contains($published->id));
    }

    /**
     * Admin can create a new car with auto-generated slug.
     */
    public function test_admin_can_create_car(): void
    {
        $carData = [
            'brand_id' => $this->toyota->id,
            'model' => 'Camry',
            'variant' => 'Hybrid Luxury',
            'year' => 2023,
            'price' => 14500000.00,
            'mileage' => 8000,
            'engine_capacity' => 2500,
            'transmission' => 'automatic',
            'fuel_type' => 'hybrid',
            'exterior_color' => 'Pearl White',
            'registration_city' => 'Islamabad',
            'assembly' => 'imported',
            'description' => 'Top tier executive sedan in showroom condition.',
            'status' => 'available',
            'publication_status' => 'published',
            'is_featured' => true,
        ];

        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/api/v1/admin/cars', $carData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'model' => 'Camry',
                    'variant' => 'Hybrid Luxury',
                    'year' => 2023,
                    'is_featured' => true,
                    'status' => 'available',
                    'publication_status' => 'published',
                ],
            ]);

        $this->assertDatabaseHas('cars', [
            'model' => 'Camry',
            'slug' => 'toyota-camry-hybrid-luxury-2023',
        ]);
    }

    /**
     * Admin create car validates required fields.
     */
    public function test_admin_create_car_validation(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->postJson('/api/v1/admin/cars', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['brand_id', 'model', 'year', 'price', 'transmission', 'fuel_type']);
    }

    /**
     * Admin can view single car by ID.
     */
    public function test_admin_can_view_single_car(): void
    {
        $car = Car::factory()->create(['brand_id' => $this->toyota->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson("/api/v1/admin/cars/{$car->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $car->id,
                    'model' => $car->model,
                ],
            ]);
    }

    /**
     * Admin can update car details.
     */
    public function test_admin_can_update_car(): void
    {
        $car = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'model' => 'Yaris',
            'price' => 4500000.00,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->putJson("/api/v1/admin/cars/{$car->id}", [
                'price' => 4800000.00,
                'description' => 'Updated price description.',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'price' => 4800000.00,
        ]);
    }

    /**
     * Admin can soft delete car.
     */
    public function test_admin_can_soft_delete_car(): void
    {
        $car = Car::factory()->create(['brand_id' => $this->toyota->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/admin/cars/{$car->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('cars', ['id' => $car->id]);
    }

    /**
     * Admin can quick patch status (available -> sold).
     */
    public function test_admin_can_patch_car_status(): void
    {
        $car = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'status' => 'available',
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/cars/{$car->id}/status", [
                'status' => 'sold',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'sold'],
            ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'status' => 'sold',
        ]);
    }

    /**
     * Admin can quick patch publication status (draft -> published).
     */
    public function test_admin_can_patch_publication_status(): void
    {
        $car = Car::factory()->draft()->create(['brand_id' => $this->toyota->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/cars/{$car->id}/publication-status", [
                'publication_status' => 'published',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['publication_status' => 'published'],
            ]);

        $car->refresh();
        $this->assertEquals('published', $car->publication_status);
        $this->assertNotNull($car->published_at);
    }

    /**
     * Admin can quick patch featured flag.
     */
    public function test_admin_can_patch_featured_flag(): void
    {
        $car = Car::factory()->create([
            'brand_id' => $this->toyota->id,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/cars/{$car->id}/featured", [
                'is_featured' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['is_featured' => true],
            ]);

        $this->assertDatabaseHas('cars', [
            'id' => $car->id,
            'is_featured' => true,
        ]);
    }

    /**
     * Admin brand CRUD operations.
     */
    public function test_admin_brand_crud(): void
    {
        // 1. Create Brand
        $createRes = $this->actingAs($this->admin, 'web')
            ->postJson('/api/v1/admin/brands', [
                'name' => 'Nissan Motors',
            ]);

        $createRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Nissan Motors',
                    'slug' => 'nissan-motors',
                ],
            ]);

        $brandId = $createRes->json('data.id');

        // 2. Update Brand
        $updateRes = $this->actingAs($this->admin, 'web')
            ->putJson("/api/v1/admin/brands/{$brandId}", [
                'name' => 'Nissan Global',
            ]);

        $updateRes->assertStatus(200)
            ->assertJson([
                'data' => ['name' => 'Nissan Global'],
            ]);

        // 3. Delete Brand with zero cars
        $deleteRes = $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/admin/brands/{$brandId}");

        $deleteRes->assertStatus(200);
        $this->assertDatabaseMissing('brands', ['id' => $brandId]);
    }

    /**
     * Admin cannot delete brand with active cars.
     */
    public function test_admin_cannot_delete_brand_with_cars(): void
    {
        Car::factory()->create(['brand_id' => $this->toyota->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/admin/brands/{$this->toyota->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('brands', ['id' => $this->toyota->id]);
    }
}
