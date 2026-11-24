<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CarImage>
 */
class CarImageFactory extends Factory
{
    protected $model = CarImage::class;

    public function definition(): array
    {
        $uuid = fake()->uuid();

        return [
            'car_id' => Car::factory(),
            'image_path' => 'cars/sample-' . $uuid . '.webp',
            'thumbnail_path' => 'cars/thumbs/sample-' . $uuid . '.webp',
            'alt_text' => fake()->sentence(4),
            'is_primary' => false,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => [
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }
}
