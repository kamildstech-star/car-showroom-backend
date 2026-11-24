<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'key' => 'setting_' . fake()->unique()->word() . '_' . fake()->unique()->numberBetween(1, 9999),
            'value' => fake()->sentence(),
            'group' => 'general',
            'is_public' => true,
        ];
    }
}
