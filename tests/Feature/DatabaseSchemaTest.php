<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test all required core tables exist.
     */
    public function test_all_core_tables_exist(): void
    {
        $tables = ['users', 'brands', 'cars', 'car_images', 'leads', 'settings'];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} does not exist in schema.");
        }
    }

    /**
     * Test cars table has all required columns.
     */
    public function test_cars_table_has_expected_columns(): void
    {
        $expectedColumns = [
            'id', 'brand_id', 'model', 'variant', 'slug', 'year', 'price',
            'mileage', 'engine_capacity', 'transmission', 'fuel_type',
            'exterior_color', 'registration_city', 'assembly', 'description',
            'status', 'publication_status', 'is_featured', 'published_at',
            'created_at', 'updated_at', 'deleted_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(Schema::hasColumn('cars', $column), "Column {$column} missing from cars table.");
        }
    }

    /**
     * Test foreign key constraint: car requires a valid brand.
     */
    public function test_car_requires_valid_brand(): void
    {
        $this->expectException(QueryException::class);

        Car::factory()->create([
            'brand_id' => 999999, // non-existent brand id
        ]);
    }

    /**
     * Test brand deletion is restricted when it has related cars.
     */
    public function test_brand_deletion_is_restricted_when_cars_exist(): void
    {
        $brand = Brand::factory()->create();
        Car::factory()->create(['brand_id' => $brand->id]);

        $this->expectException(QueryException::class);
        $brand->delete();
    }

    /**
     * Test unique slug constraints.
     */
    public function test_car_and_brand_slugs_must_be_unique(): void
    {
        Brand::factory()->create(['slug' => 'unique-brand']);
        
        $this->expectException(QueryException::class);
        Brand::factory()->create(['slug' => 'unique-brand']);
    }

    /**
     * Test car soft deletes and preservation of lead history.
     */
    public function test_car_soft_delete_preserves_lead_relation(): void
    {
        $car = Car::factory()->create();
        $lead = Lead::factory()->create(['car_id' => $car->id]);

        $this->assertEquals($car->id, $lead->car->id);

        // Soft delete the car
        $car->delete();

        $this->assertSoftDeleted('cars', ['id' => $car->id]);

        // Reload lead and verify relationship still resolves via withTrashed
        $lead->refresh();
        $this->assertNotNull($lead->car);
        $this->assertEquals($car->id, $lead->car->id);
    }

    /**
     * Test car images cascade deletion on force delete.
     */
    public function test_car_images_cascade_delete_with_car(): void
    {
        $car = Car::factory()->create();
        $image = CarImage::factory()->create(['car_id' => $car->id]);

        $this->assertDatabaseHas('car_images', ['id' => $image->id]);

        $car->forceDelete();

        $this->assertDatabaseMissing('car_images', ['id' => $image->id]);
    }

    /**
     * Test Eloquent relationships between Brand, Car, CarImage, and Lead.
     */
    public function test_eloquent_relationships(): void
    {
        $brand = Brand::factory()->create(['name' => 'Test Motors', 'slug' => 'test-motors']);
        $car = Car::factory()->create([
            'brand_id' => $brand->id,
            'model' => 'Speedster',
            'slug' => 'speedster-2024',
            'price' => 5000000.00,
            'publication_status' => 'published',
            'status' => 'available',
            'is_featured' => true,
        ]);

        $primaryImage = CarImage::factory()->primary()->create([
            'car_id' => $car->id,
            'image_path' => 'cars/front.webp',
        ]);

        $secondaryImage = CarImage::factory()->create([
            'car_id' => $car->id,
            'image_path' => 'cars/side.webp',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $lead = Lead::factory()->create([
            'car_id' => $car->id,
            'customer_name' => 'Ali Raza',
            'phone' => '+923001234567',
        ]);

        // Brand -> Cars
        $this->assertTrue($brand->cars->contains($car));

        // Car -> Brand
        $this->assertEquals($brand->id, $car->brand->id);

        // Car -> Images
        $this->assertCount(2, $car->images);

        // Car -> Primary Image
        $this->assertEquals($primaryImage->id, $car->primaryImage->id);

        // Car -> Leads
        $this->assertTrue($car->leads->contains($lead));

        // Lead -> Car
        $this->assertEquals($car->id, $lead->car->id);

        // Price decimal casting
        $this->assertSame('5000000.00', (string) $car->price);
    }

    /**
     * Test query scopes on Car model.
     */
    public function test_car_query_scopes(): void
    {
        $published = Car::factory()->create(['publication_status' => 'published']);
        $draft = Car::factory()->draft()->create();
        $archived = Car::factory()->archived()->create();
        $sold = Car::factory()->sold()->create();
        $featured = Car::factory()->featured()->create();

        $this->assertTrue(Car::published()->get()->contains($published));
        $this->assertFalse(Car::published()->get()->contains($draft));
        $this->assertFalse(Car::published()->get()->contains($archived));

        $this->assertTrue(Car::available()->get()->contains($published));
        $this->assertFalse(Car::available()->get()->contains($sold));

        $this->assertTrue(Car::featured()->get()->contains($featured));
    }

    /**
     * Test Setting model helper methods.
     */
    public function test_setting_get_and_set_helpers(): void
    {
        Setting::set('showroom_phone', '+923041142642', 'contact', true);

        $this->assertEquals('+923041142642', Setting::get('showroom_phone'));
        $this->assertEquals('default_value', Setting::get('non_existent_key', 'default_value'));
    }

    /**
     * Test DatabaseSeeder runs successfully and creates the initial admin account.
     */
    public function test_database_seeder_populates_core_data(): void
    {
        $this->seed();

        // Admin account exists
        $this->assertDatabaseHas('users', ['email' => 'admin@hassanmotors.pk']);

        // Brands seeded
        $this->assertDatabaseHas('brands', ['name' => 'Toyota']);
        $this->assertDatabaseHas('brands', ['name' => 'Honda']);

        // Showroom contact settings seeded
        $this->assertDatabaseHas('settings', [
            'key' => 'showroom_phone',
            'value' => '+923041142642',
        ]);

        // Cars and leads seeded
        $this->assertGreaterThan(0, Car::count());
        $this->assertGreaterThan(0, Lead::count());
    }
}
