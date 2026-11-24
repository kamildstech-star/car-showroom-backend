<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Toyota', 'Honda', 'Suzuki', 'KIA', 'Hyundai', 'Changan', 'MG', 'Haval', 'Nissan', 'Audi'
        ]) . ' ' . fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo_path' => null,
            'is_active' => true,
        ];
    }
}
