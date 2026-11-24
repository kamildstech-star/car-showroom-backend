<?php

namespace Database\Seeders;

use App\Models\Car;
use App\Models\Lead;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $corolla = Car::where('model', 'Corolla')->first();
        $civic = Car::where('model', 'Civic')->first();

        $leads = [
            [
                'car_id' => $corolla?->id,
                'customer_name' => 'Muhammad Bilal',
                'phone' => '+923001234567',
                'message' => 'Is this Corolla Grande still available? Please let me know if the price is negotiable.',
                'status' => 'new',
                'source' => 'car_detail',
                'whatsapp_clicked_at' => now()->subHours(2),
                'notes' => 'Customer sounded interested, requested showroom location.',
            ],
            [
                'car_id' => $civic?->id,
                'customer_name' => 'Usman Tariq',
                'phone' => '+923219876543',
                'message' => 'I would like to schedule a physical vehicle inspection for this Civic tomorrow.',
                'status' => 'contacted',
                'source' => 'car_detail',
                'whatsapp_clicked_at' => now()->subDays(1),
                'contacted_at' => now()->subHours(18),
                'notes' => 'Confirmed inspection appointment for Wednesday 3 PM.',
            ],
            [
                'car_id' => null, // General inquiry
                'customer_name' => 'Hamza Saeed',
                'phone' => '+923335557788',
                'message' => 'Do you provide bank financing or installment options for vehicles?',
                'status' => 'new',
                'source' => 'contact_page',
                'whatsapp_clicked_at' => null,
                'notes' => null,
            ],
        ];

        foreach ($leads as $lead) {
            Lead::create($lead);
        }
    }
}
