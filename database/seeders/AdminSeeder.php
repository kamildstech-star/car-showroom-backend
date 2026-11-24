<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds the single authorized showroom administrator.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@hassanmotors.pk'],
            [
                'name' => 'Hassan Motors Admin',
                'password' => Hash::make(env('INITIAL_ADMIN_PASSWORD', 'Admin@Hassan2026!')),
                'email_verified_at' => now(),
            ]
        );
    }
}
