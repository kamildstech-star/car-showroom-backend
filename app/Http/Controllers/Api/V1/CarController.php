<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CarFilterRequest;
use App\Http\Resources\CarDetailResource;
use App\Http\Resources\CarListResource;
use App\Models\Car;
use App\Services\CarInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CarController extends Controller
{
    /**
     * Public inventory listing with filters, sorting, and pagination.
     */
    public function index(CarFilterRequest $request, CarInventoryService $service): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 12);
        $sort = (string) $request->input('sort', 'newest');

        $query = Car::query()
            ->with(['brand', 'primaryImage']);

        $query = $service->applyFilters($query, $request->validated(), isPublic: true);
        $query = $service->applySorting($query, $sort);

        $cars = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Cars retrieved successfully.',
            'data' => CarListResource::collection($cars->items()),
            'meta' => [
                'current_page' => $cars->currentPage(),
                'last_page' => $cars->lastPage(),
                'per_page' => $cars->perPage(),
                'total' => $cars->total(),
            ],
        ]);
    }

    /**
     * Get featured published cars.
     */
    public function featured(CarInventoryService $service): JsonResponse
    {
        $cars = Cache::remember('public_featured_cars', 300, function () {
            return Car::published()
                ->available()
                ->featured()
                ->with(['brand', 'primaryImage'])
                ->orderBy('published_at', 'desc')
                ->take(6)
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => CarListResource::collection($cars),
        ]);
    }

    /**
     * Get latest published cars.
     */
    public function latest(CarInventoryService $service): JsonResponse
    {
        $cars = Cache::remember('public_latest_cars', 300, function () {
            return Car::published()
                ->available()
                ->with(['brand', 'primaryImage'])
                ->orderBy('published_at', 'desc')
                ->take(6)
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => CarListResource::collection($cars),
        ]);
    }

    /**
     * Get published car details by unique slug.
     */
    public function show(string $slug): JsonResponse
    {
        $car = Car::published()
            ->where('slug', $slug)
            ->with([
                'brand',
                'images' => fn ($q) => $q->orderBy('sort_order', 'asc'),
            ])
            ->first();

        if (!$car) {
            return response()->json([
                'success' => false,
                'message' => 'Car not found or is no longer publicly available.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new CarDetailResource($car),
        ]);
    }
}
