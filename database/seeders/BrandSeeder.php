<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Toyota', 'logo_path' => 'brands/toyota.png'],
            ['name' => 'Honda', 'logo_path' => 'brands/honda.png'],
            ['name' => 'Suzuki', 'logo_path' => 'brands/suzuki.png'],
            ['name' => 'KIA', 'logo_path' => 'brands/kia.png'],
            ['name' => 'Hyundai', 'logo_path' => 'brands/hyundai.png'],
            ['name' => 'Changan', 'logo_path' => 'brands/changan.png'],
            ['name' => 'MG', 'logo_path' => 'brands/mg.png'],
            ['name' => 'Haval', 'logo_path' => 'brands/haval.png'],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brand['name'])],
                [
                    'name' => $brand['name'],
                    'logo_path' => $brand['logo_path'],
                    'is_active' => true,
                ]
            );
        }
    }
}
