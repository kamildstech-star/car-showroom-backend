<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class BrandController extends Controller
{
    /**
     * List active brands with published car counts for filter panels and navigation.
     */
    public function index(): JsonResponse
    {
        $brands = Cache::remember('public_active_brands', 600, function () {
            return Brand::where('is_active', true)
                ->withCount(['cars' => fn ($q) => $q->published()])
                ->orderBy('name', 'asc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => BrandResource::collection($brands),
        ]);
    }
}
