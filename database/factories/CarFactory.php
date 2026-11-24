<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Car>
 */
class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition(): array
    {
        $model = fake()->randomElement(['Corolla', 'Civic', 'Sportage', 'Tucson', 'Yaris', 'City', 'Swift', 'Alsvin', 'HS', 'H6']);
        $variant = fake()->randomElement(['Altis Grande 1.8', 'Oriel 1.5 Turbo', 'AWD 2.0', 'GLI 1.3', 'VXL 1.5', 'Lumiere']);
        $year = fake()->numberBetween(2018, 2024);
        $name = "{$model} {$variant} {$year} " . fake()->unique()->numberBetween(1, 99999);

        return [
            'brand_id' => Brand::factory(),
            'model' => $model,
            'variant' => $variant,
            'slug' => Str::slug($name),
            'year' => $year,
            'price' => fake()->randomFloat(2, 2500000, 15000000),
            'mileage' => fake()->numberBetween(5000, 120000),
            'engine_capacity' => fake()->randomElement([660, 1000, 1200, 1300, 1500, 1800, 2000]),
            'transmission' => fake()->randomElement(['automatic', 'manual']),
            'fuel_type' => fake()->randomElement(['petrol', 'diesel', 'hybrid']),
            'exterior_color' => fake()->randomElement(['White', 'Black', 'Silver', 'Grey', 'Blue', 'Red']),
            'registration_city' => fake()->randomElement(['Lahore', 'Islamabad', 'Faisalabad', 'Karachi', 'Unregistered']),
            'assembly' => fake()->randomElement(['local', 'imported']),
            'description' => fake()->paragraph(),
            'status' => 'available',
            'publication_status' => 'published',
            'is_featured' => fake()->boolean(25),
            'published_at' => now(),
        ];
    }

    public function sold(): static
    {
        return $this->state(fn () => [
            'status' => 'sold',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'publication_status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn () => [
            'publication_status' => 'archived',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn () => [
            'is_featured' => true,
        ]);
    }
}
