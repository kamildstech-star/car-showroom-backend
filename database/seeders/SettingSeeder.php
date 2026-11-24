<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'showroom_name',
                'value' => 'Hassan Motors',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'showroom_tagline',
                'value' => 'Your Trusted Luxury & Verified Family Car Showroom in Faisalabad',
                'group' => 'general',
                'is_public' => true,
            ],
            [
                'key' => 'showroom_phone',
                'value' => '+923041142642',
                'group' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'showroom_whatsapp',
                'value' => '+923041142642',
                'group' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'showroom_email',
                'value' => 'info@hassanmotors.pk',
                'group' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'showroom_address',
                'value' => 'Main West Canal Road, Faisalabad, Punjab, Pakistan',
                'group' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'showroom_city',
                'value' => 'Faisalabad',
                'group' => 'contact',
                'is_public' => true,
            ],
            [
                'key' => 'opening_hours',
                'value' => 'Monday - Saturday: 10:00 AM - 08:00 PM (Sunday Closed)',
                'group' => 'general',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
