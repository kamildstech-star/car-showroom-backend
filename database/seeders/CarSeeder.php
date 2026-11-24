<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $toyota = Brand::where('name', 'Toyota')->first();
        $honda = Brand::where('name', 'Honda')->first();
        $suzuki = Brand::where('name', 'Suzuki')->first();
        $kia = Brand::where('name', 'KIA')->first();
        $hyundai = Brand::where('name', 'Hyundai')->first();
        $changan = Brand::where('name', 'Changan')->first();
        $mg = Brand::where('name', 'MG')->first();
        $haval = Brand::where('name', 'Haval')->first();

        $cars = [
            [
                'brand_id' => $toyota?->id,
                'model' => 'Corolla',
                'variant' => 'Altis Grande 1.8 CVT',
                'year' => 2022,
                'price' => 6250000.00,
                'mileage' => 24000,
                'engine_capacity' => 1800,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Super White',
                'registration_city' => 'Lahore',
                'assembly' => 'local',
                'description' => 'Bumper to bumper original genuine condition. Maintained by Toyota Authorized 3S Dealership. Sunroof, paddle shifters, push start.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => true,
                'published_at' => now(),
            ],
            [
                'brand_id' => $honda?->id,
                'model' => 'Civic',
                'variant' => 'Oriel 1.5 VTEC Turbo',
                'year' => 2023,
                'price' => 8650000.00,
                'mileage' => 14000,
                'engine_capacity' => 1500,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Crystal Black Pearl',
                'registration_city' => 'Islamabad',
                'assembly' => 'local',
                'description' => 'First owner, showroom invoice delivery. Complete service history, pristine interior, zero scratches.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => true,
                'published_at' => now(),
            ],
            [
                'brand_id' => $kia?->id,
                'model' => 'Sportage',
                'variant' => 'AWD 2.0',
                'year' => 2021,
                'price' => 7400000.00,
                'mileage' => 38000,
                'engine_capacity' => 2000,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Panthera Metal',
                'registration_city' => 'Faisalabad',
                'assembly' => 'local',
                'description' => 'Panoramic sunroof, all-wheel drive, Michelin tires recently installed, scratchless showroom condition.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => true,
                'published_at' => now(),
            ],
            [
                'brand_id' => $hyundai?->id,
                'model' => 'Tucson',
                'variant' => 'FWD A/T GLS Sport',
                'year' => 2022,
                'price' => 7800000.00,
                'mileage' => 29000,
                'engine_capacity' => 2000,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Polar White',
                'registration_city' => 'Lahore',
                'assembly' => 'local',
                'description' => 'Very sparingly driven family SUV. 100% genuine guaranteed. Wireless charger and premium audio.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => false,
                'published_at' => now(),
            ],
            [
                'brand_id' => $suzuki?->id,
                'model' => 'Swift',
                'variant' => 'GLX CVT',
                'year' => 2023,
                'price' => 4750000.00,
                'mileage' => 11000,
                'engine_capacity' => 1200,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Phoenix Red',
                'registration_city' => 'Islamabad',
                'assembly' => 'local',
                'description' => 'Top of the line GLX variant with 6 airbags, cruise control, push start and reverse camera.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => false,
                'published_at' => now(),
            ],
            [
                'brand_id' => $toyota?->id,
                'model' => 'Yaris',
                'variant' => 'ATIV X 1.5 CVT',
                'year' => 2022,
                'price' => 5300000.00,
                'mileage' => 31000,
                'engine_capacity' => 1500,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Silver Metallic',
                'registration_city' => 'Faisalabad',
                'assembly' => 'local',
                'description' => 'Neat and clean interior, original smart key, climate control, excellent fuel average.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => false,
                'published_at' => now(),
            ],
            [
                'brand_id' => $changan?->id,
                'model' => 'Alsvin',
                'variant' => '1.5 DCT Lumiere',
                'year' => 2021,
                'price' => 3850000.00,
                'mileage' => 42000,
                'engine_capacity' => 1500,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Galaxy Black',
                'registration_city' => 'Lahore',
                'assembly' => 'local',
                'description' => 'Sunroof edition. Maintained on schedule. Certified inspection report available.',
                'status' => 'sold', // Sold vehicle test
                'publication_status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subMonths(2),
            ],
            [
                'brand_id' => $mg?->id,
                'model' => 'HS',
                'variant' => '1.5 Turbo Trophy',
                'year' => 2022,
                'price' => 6900000.00,
                'mileage' => 26000,
                'engine_capacity' => 1500,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Pearl White',
                'registration_city' => 'Islamabad',
                'assembly' => 'imported',
                'description' => 'Red wine leather interior, ambient lighting, panoramic glass roof, adaptive cruise control.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => false,
                'published_at' => now(),
            ],
            [
                'brand_id' => $haval?->id,
                'model' => 'H6',
                'variant' => '1.5T 2WD',
                'year' => 2023,
                'price' => 9200000.00,
                'mileage' => 8500,
                'engine_capacity' => 1500,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Grey',
                'registration_city' => 'Lahore',
                'assembly' => 'local',
                'description' => 'Virtually brand new. Full ADAS safety features, 360 degree cameras, head-up display.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => true,
                'published_at' => now(),
            ],
            [
                'brand_id' => $toyota?->id,
                'model' => 'Fortuner',
                'variant' => 'Legender 2.8 4x4',
                'year' => 2023,
                'price' => 18900000.00,
                'mileage' => 12000,
                'engine_capacity' => 2800,
                'transmission' => 'automatic',
                'fuel_type' => 'diesel',
                'exterior_color' => 'Attitude Black',
                'registration_city' => 'Islamabad',
                'assembly' => 'local',
                'description' => 'Legender top-tier luxury edition. Bi-beam LED headlamps, JBL premium sound system, dual AC.',
                'status' => 'available',
                'publication_status' => 'published',
                'is_featured' => true,
                'published_at' => now(),
            ],
            [
                'brand_id' => $suzuki?->id,
                'model' => 'Alto',
                'variant' => 'VXL AGS',
                'year' => 2023,
                'price' => 2950000.00,
                'mileage' => 15000,
                'engine_capacity' => 660,
                'transmission' => 'automatic',
                'fuel_type' => 'petrol',
                'exterior_color' => 'Solid White',
                'registration_city' => 'Faisalabad',
                'assembly' => 'local',
                'description' => 'Draft test vehicle — not published yet.',
                'status' => 'available',
                'publication_status' => 'draft', // Draft test
                'is_featured' => false,
                'published_at' => null,
            ],
        ];

        foreach ($cars as $carData) {
            if (!$carData['brand_id']) {
                continue;
            }

            $slug = Str::slug("{$carData['model']} {$carData['variant']} {$carData['year']}");
            // Ensure unique slug
            $existing = Car::where('slug', $slug)->exists();
            if ($existing) {
                $slug .= '-' . Str::random(5);
            }

            $carData['slug'] = $slug;

            $car = Car::create($carData);

            // Add sample images
            CarImage::create([
                'car_id' => $car->id,
                'image_path' => "cars/{$car->slug}-front.webp",
                'alt_text' => "{$car->model} {$car->variant} Front View",
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            CarImage::create([
                'car_id' => $car->id,
                'image_path' => "cars/{$car->slug}-side.webp",
                'alt_text' => "{$car->model} {$car->variant} Side Angle",
                'is_primary' => false,
                'sort_order' => 1,
            ]);

            CarImage::create([
                'car_id' => $car->id,
                'image_path' => "cars/{$car->slug}-interior.webp",
                'alt_text' => "{$car->model} {$car->variant} Dashboard and Interior",
                'is_primary' => false,
                'sort_order' => 2,
            ]);
        }
    }
}
