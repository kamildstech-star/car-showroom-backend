<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCarRequest;
use App\Http\Requests\Admin\UpdateCarFeaturedRequest;
use App\Http\Requests\Admin\UpdateCarPublicationStatusRequest;
use App\Http\Requests\Admin\UpdateCarRequest;
use App\Http\Requests\Admin\UpdateCarStatusRequest;
use App\Http\Requests\CarFilterRequest;
use App\Http\Resources\CarAdminResource;
use App\Models\Brand;
use App\Models\Car;
use App\Services\CarInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CarController extends Controller
{
    /**
     * List all cars for admin management (includes drafts, sold, archived).
     */
    public function index(CarFilterRequest $request, CarInventoryService $service): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 12);
        $sort = (string) $request->input('sort', 'newest');

        $query = Car::query()
            ->with(['brand', 'primaryImage'])
            ->withCount('leads');

        $query = $service->applyFilters($query, $request->validated(), isPublic: false);
        $query = $service->applySorting($query, $sort);

        $cars = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Admin car inventory retrieved successfully.',
            'data' => CarAdminResource::collection($cars->items()),
            'meta' => [
                'current_page' => $cars->currentPage(),
                'last_page' => $cars->lastPage(),
                'per_page' => $cars->perPage(),
                'total' => $cars->total(),
            ],
        ]);
    }

    /**
     * Create new car record.
     */
    public function store(StoreCarRequest $request, CarInventoryService $service): JsonResponse
    {
        $validated = $request->validated();
        $brand = Brand::findOrFail($validated['brand_id']);

        // Auto-generate stable unique SEO slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = $service->generateUniqueSlug(
                $brand->name,
                $validated['model'],
                $validated['variant'] ?? null,
                $validated['year']
            );
        }

        // Set published_at timestamp if published
        if (($validated['publication_status'] ?? 'draft') === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $car = DB::transaction(function () use ($validated) {
            return Car::create($validated);
        });

        $car->load(['brand', 'primaryImage', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Car created successfully.',
            'data' => new CarAdminResource($car),
        ], 201);
    }

    /**
     * Get car for admin editing.
     */
    public function show(int $id): JsonResponse
    {
        $car = Car::with(['brand', 'images' => fn ($q) => $q->orderBy('sort_order', 'asc')])
            ->withCount('leads')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new CarAdminResource($car),
        ]);
    }

    /**
     * Update car record.
     */
    public function update(UpdateCarRequest $request, int $id, CarInventoryService $service): JsonResponse
    {
        $car = Car::findOrFail($id);
        $validated = $request->validated();

        $brandId = $validated['brand_id'] ?? $car->brand_id;
        $brand = Brand::findOrFail($brandId);

        // Regenerate slug if model/variant/year changed and slug wasn't manually overridden
        if (empty($validated['slug']) && (
            (isset($validated['model']) && $validated['model'] !== $car->model) ||
            (isset($validated['variant']) && $validated['variant'] !== $car->variant) ||
            (isset($validated['year']) && $validated['year'] !== $car->year) ||
            (isset($validated['brand_id']) && $validated['brand_id'] !== $car->brand_id)
        )) {
            $validated['slug'] = $service->generateUniqueSlug(
                $brand->name,
                $validated['model'] ?? $car->model,
                $validated['variant'] ?? $car->variant,
                $validated['year'] ?? $car->year,
                $car->id
            );
        }

        // Set published_at if transitioning to published
        if (isset($validated['publication_status']) &&
            $validated['publication_status'] === 'published' &&
            !$car->published_at &&
            empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        DB::transaction(function () use ($car, $validated) {
            $car->update($validated);
        });

        $car->load(['brand', 'primaryImage', 'images']);

        return response()->json([
            'success' => true,
            'message' => 'Car updated successfully.',
            'data' => new CarAdminResource($car),
        ]);
    }

    /**
     * Soft delete car.
     */
    public function destroy(int $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $car->delete();

        return response()->json([
            'success' => true,
            'message' => 'Car deleted successfully.',
        ]);
    }

    /**
     * Quick patch: update sale status (available / sold).
     */
    public function updateStatus(UpdateCarStatusRequest $request, int $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $car->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => "Car status updated to {$car->status}.",
            'data' => new CarAdminResource($car->load('brand')),
        ]);
    }

    /**
     * Quick patch: update publication status (draft / published / archived).
     */
    public function updatePublicationStatus(UpdateCarPublicationStatusRequest $request, int $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $status = $request->input('publication_status');

        $data = ['publication_status' => $status];
        if ($status === 'published' && !$car->published_at) {
            $data['published_at'] = now();
        }

        $car->update($data);

        return response()->json([
            'success' => true,
            'message' => "Car publication status updated to {$car->publication_status}.",
            'data' => new CarAdminResource($car->load('brand')),
        ]);
    }

    /**
     * Quick patch: update featured flag.
     */
    public function updateFeatured(UpdateCarFeaturedRequest $request, int $id): JsonResponse
    {
        $car = Car::findOrFail($id);
        $car->update($request->validated());

        $statusLabel = $car->is_featured ? 'featured' : 'standard';

        return response()->json([
            'success' => true,
            'message' => "Car marked as {$statusLabel}.",
            'data' => new CarAdminResource($car->load('brand')),
        ]);
    }
}
