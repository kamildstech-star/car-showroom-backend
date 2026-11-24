<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    /**
     * Stream memory-efficient sitemap feed of published vehicles.
     * Enforces quality-gating on sold vehicles and accurate lastmod timestamps.
     */
    public function cars(): StreamedResponse
    {
        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "[\n");
            $first = true;

            Car::query()
                ->published()
                ->select([
                    'id',
                    'slug',
                    'status',
                    'price',
                    'mileage',
                    'engine_capacity',
                    'exterior_color',
                    'published_at',
                    'updated_at',
                ])
                ->withCount('images')
                ->orderBy('id', 'asc')
                ->chunk(100, function ($cars) use ($handle, &$first) {
                    foreach ($cars as $car) {
                        // Quality Gate for Sold Vehicles:
                        // Sold cars must have at least 1 image, recorded mileage, engine capacity, color, and price > 0
                        if ($car->status === 'sold') {
                            $isQualityComplete = $car->images_count >= 1
                                && $car->mileage !== null
                                && $car->engine_capacity !== null
                                && !empty($car->exterior_color)
                                && (float) $car->price > 0;

                            if (!$isQualityComplete) {
                                continue;
                            }
                        }

                        $lastmod = $car->updated_at?->format('Y-m-d')
                            ?? $car->published_at?->format('Y-m-d');

                        $entry = [
                            'slug' => $car->slug,
                            'status' => $car->status,
                            'lastmod' => $lastmod,
                        ];

                        if (!$first) {
                            fwrite($handle, ",\n");
                        }
                        $first = false;

                        fwrite($handle, json_encode($entry, JSON_UNESCAPED_SLASHES));
                    }
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                });

            fwrite($handle, "\n]");
            fclose($handle);
        }, 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
