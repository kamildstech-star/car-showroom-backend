<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    /**
     * List all brands with total car counts.
     */
    public function index(): JsonResponse
    {
        $brands = Brand::withCount('cars')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => BrandResource::collection($brands),
        ]);
    }

    /**
     * Create new brand.
     */
    public function store(StoreBrandRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = Str::slug($validated['name']);

        $brand = Brand::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand created successfully.',
            'data' => new BrandResource($brand),
        ], 201);
    }

    /**
     * Show brand details.
     */
    public function show(int $id): JsonResponse
    {
        $brand = Brand::withCount('cars')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BrandResource($brand),
        ]);
    }

    /**
     * Update brand.
     */
    public function update(UpdateBrandRequest $request, int $id): JsonResponse
    {
        $brand = Brand::findOrFail($id);
        $validated = $request->validated();

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $brand->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Brand updated successfully.',
            'data' => new BrandResource($brand),
        ]);
    }

    /**
     * Delete brand (safe check: rejects deletion if cars exist).
     */
    public function destroy(int $id): JsonResponse
    {
        $brand = Brand::withCount('cars')->findOrFail($id);

        if ($brand->cars_count > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete brand '{$brand->name}' because {$brand->cars_count} vehicle(s) are linked to it. Reassign or remove vehicles first.",
            ], 422);
        }

        $brand->delete();

        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully.',
        ]);
    }
}
