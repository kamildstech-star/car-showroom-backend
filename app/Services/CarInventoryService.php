<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Car;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class CarInventoryService
{
    /**
     * Generate unique, stable SEO slug for car.
     */
    public function generateUniqueSlug(string $brandName, string $model, ?string $variant, int $year, ?int $excludeId = null): string
    {
        $base = trim("{$brandName} {$model} {$variant} {$year}");
        $slug = Str::slug($base);

        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = "{$original}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $query = Car::where('slug', $slug);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query->exists();
    }

    /**
     * Apply allowed listing filters to query.
     */
    public function applyFilters(Builder $query, array $filters, bool $isPublic = true): Builder
    {
        // Enforce published scope for public queries
        if ($isPublic) {
            $query->published();
        } elseif (!empty($filters['publication_status'])) {
            $query->where('publication_status', $filters['publication_status']);
        }

        // Sale Status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Brand filter by ID or slug
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        } elseif (!empty($filters['brand'])) {
            $query->whereHas('brand', function ($q) use ($filters) {
                $q->where('slug', $filters['brand'])
                  ->orWhere('name', $filters['brand']);
            });
        }

        // Keyword search (model, variant, exterior_color, description)
        if (!empty($filters['search'])) {
            $keyword = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('model', 'LIKE', $keyword)
                  ->orWhere('variant', 'LIKE', $keyword)
                  ->orWhere('exterior_color', 'LIKE', $keyword)
                  ->orWhere('description', 'LIKE', $keyword)
                  ->orWhereHas('brand', function ($bq) use ($keyword) {
                      $bq->where('name', 'LIKE', $keyword);
                  });
            });
        }

        // Price range
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        // Year range
        if (isset($filters['min_year'])) {
            $query->where('year', '>=', (int) $filters['min_year']);
        }
        if (isset($filters['max_year'])) {
            $query->where('year', '<=', (int) $filters['max_year']);
        }

        // Transmission
        if (!empty($filters['transmission'])) {
            $query->where('transmission', $filters['transmission']);
        }

        // Fuel type
        if (!empty($filters['fuel_type'])) {
            $query->where('fuel_type', $filters['fuel_type']);
        }

        // Mileage range
        if (isset($filters['min_mileage'])) {
            $query->where('mileage', '>=', (int) $filters['min_mileage']);
        }
        if (isset($filters['max_mileage'])) {
            $query->where('mileage', '<=', (int) $filters['max_mileage']);
        }

        // Featured filter
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query;
    }

    /**
     * Apply allowed sorting order to query.
     */
    public function applySorting(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'year_desc' => $query->orderBy('year', 'desc')->orderBy('id', 'desc'),
            'mileage_asc' => $query->whereNotNull('mileage')->orderBy('mileage', 'asc'),
            'oldest' => $query->orderBy('published_at', 'asc')->orderBy('id', 'asc'),
            default => $query->orderBy('published_at', 'desc')->orderBy('id', 'desc'), // 'newest'
        };
    }
}
