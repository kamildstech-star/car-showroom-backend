<?php

namespace App\Services;

use App\Models\Car;
use App\Models\Lead;
use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LeadService
{
    /**
     * Create and persist a new customer inquiry, then generate WhatsApp deep link.
     *
     * @param  array<string, mixed>  $data
     * @return array{lead: Lead, whatsapp_url: string}
     */
    public function createLead(array $data): array
    {
        $normalizedPhone = $this->normalizePhone((string) ($data['phone'] ?? ''));

        $carId = ! empty($data['car_id']) ? (int) $data['car_id'] : null;
        $car = $carId ? Car::with('brand')->find($carId) : null;

        $source = $data['source'] ?? ($car ? 'car_detail' : 'contact_page');

        $lead = Lead::create([
            'car_id' => $car?->id,
            'customer_name' => trim((string) ($data['customer_name'] ?? '')),
            'phone' => $normalizedPhone,
            'message' => ! empty($data['message']) ? trim((string) $data['message']) : null,
            'status' => 'new',
            'source' => $source,
        ]);

        $whatsappUrl = $this->generateWhatsAppUrl($lead, $car);

        return [
            'lead' => $lead,
            'whatsapp_url' => $whatsappUrl,
        ];
    }

    /**
     * Build WhatsApp deep link with prefilled inquiry message.
     */
    public function generateWhatsAppUrl(Lead $lead, ?Car $car = null): string
    {
        $rawNumber = Setting::get('showroom_whatsapp', '+923041142642');
        $cleanNumber = preg_replace('/[^0-9]/', '', (string) $rawNumber);

        if (! $car && $lead->car_id) {
            $car = Car::with('brand')->find($lead->car_id);
        }

        $lines = ["Hello Hassan Motors,"];

        if ($car) {
            $brandName = $car->brand?->name ? "{$car->brand->name} " : '';
            $carTitle = trim("{$car->year} {$brandName}{$car->model} {$car->variant}");
            $lines[] = "I would like to inquire about the vehicle listed on your website:";
            $lines[] = "• Vehicle: {$carTitle}";
            $lines[] = "• Price: PKR " . number_format((float) $car->price, 0);
            $lines[] = "• Vehicle Link: " . url("/cars/{$car->slug}");
        } else {
            $lines[] = "I would like to make an inquiry via your showroom website:";
        }

        $lines[] = "";
        $lines[] = "Customer Details:";
        $lines[] = "• Name: {$lead->customer_name}";
        $lines[] = "• Phone: {$lead->phone}";

        if (! empty($lead->message)) {
            $lines[] = "• Message: {$lead->message}";
        }

        $body = implode("\n", $lines);

        return "https://wa.me/{$cleanNumber}?text=" . rawurlencode($body);
    }

    /**
     * Record customer click on WhatsApp button.
     */
    public function recordWhatsAppClick(Lead $lead): Lead
    {
        if ($lead->whatsapp_clicked_at === null) {
            $lead->update(['whatsapp_clicked_at' => now()]);
        }

        return $lead;
    }

    /**
     * Update lead CRM status with automatic contacted_at tracking.
     */
    public function updateStatus(Lead $lead, string $status): Lead
    {
        $updates = ['status' => $status];

        if ($status === 'contacted' && $lead->contacted_at === null) {
            $updates['contacted_at'] = now();
        }

        $lead->update($updates);

        return $lead->load('car.brand');
    }

    /**
     * Update internal admin notes.
     */
    public function updateNotes(Lead $lead, ?string $notes): Lead
    {
        $lead->update(['notes' => $notes]);

        return $lead->load('car.brand');
    }

    /**
     * List and filter leads for admin management.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getAdminLeads(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::with('car.brand');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['car_id'])) {
            $query->where('car_id', $filters['car_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $allowedSorts = ['created_at', 'customer_name', 'status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSorts, true)
            ? $filters['sort_by']
            : 'created_at';

        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        $limit = max(1, min((int) ($filters['per_page'] ?? $perPage), 50));

        return $query->paginate($limit);
    }

    /**
     * Normalize customer phone number.
     */
    public function normalizePhone(string $phone): string
    {
        $clean = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        // Domestic Pakistan mobile format: 03001234567 -> +923001234567
        if (str_starts_with($clean, '03') && strlen($clean) === 11) {
            return '+92' . substr($clean, 1);
        }

        // Without plus prefix: 923001234567 -> +923001234567
        if (str_starts_with($clean, '92') && ! str_starts_with($clean, '+92')) {
            return '+' . $clean;
        }

        // International 00 prefix: 00923001234567 -> +923001234567
        if (str_starts_with($clean, '0092')) {
            return '+92' . substr($clean, 4);
        }

        return $clean;
    }
}
