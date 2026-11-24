<?php

namespace Database\Factories;

use App\Models\Car;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'car_id' => Car::factory(),
            'customer_name' => fake()->name(),
            'phone' => '+92' . fake()->numerify('3#########'),
            'message' => fake()->sentence(),
            'status' => fake()->randomElement(['new', 'contacted', 'converted', 'lost']),
            'source' => fake()->randomElement(['car_detail', 'contact_page', 'whatsapp_direct']),
            'whatsapp_clicked_at' => fake()->boolean(60) ? now() : null,
            'contacted_at' => null,
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }
}
