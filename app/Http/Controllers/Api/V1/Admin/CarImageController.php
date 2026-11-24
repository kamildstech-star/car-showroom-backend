<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderCarImagesRequest;
use App\Http\Requests\Admin\UploadCarImagesRequest;
use App\Http\Resources\CarImageResource;
use App\Models\Car;
use App\Services\CarImageService;
use Illuminate\Http\JsonResponse;

class CarImageController extends Controller
{
    public function __construct(
        protected CarImageService $imageService
    ) {}

    /**
     * Upload and optimize multiple images for a car.
     * POST /api/v1/admin/cars/{carId}/images
     */
    public function store(UploadCarImagesRequest $request, int $carId): JsonResponse
    {
        $car = Car::findOrFail($carId);

        $images = $request->file('images', []);
        $altTexts = $request->input('alt_texts', []);
        $forceFirstPrimary = $request->has('is_primary') ? $request->boolean('is_primary') : null;

        $created = $this->imageService->uploadImages($car, $images, $altTexts, $forceFirstPrimary);

        return response()->json([
            'success' => true,
            'message' => 'Car images uploaded successfully.',
            'data' => CarImageResource::collection($created),
        ], 201);
    }

    /**
     * Delete an image from a car and purge storage.
     * DELETE /api/v1/admin/cars/{carId}/images/{imageId}
     */
    public function destroy(int $carId, int $imageId): JsonResponse
    {
        $car = Car::findOrFail($carId);

        $this->imageService->deleteImage($car, $imageId);

        return response()->json([
            'success' => true,
            'message' => 'Car image deleted successfully.',
        ]);
    }

    /**
     * Set a specific image as the primary image for a car.
     * PATCH /api/v1/admin/cars/{carId}/images/{imageId}/primary
     */
    public function setPrimary(int $carId, int $imageId): JsonResponse
    {
        $car = Car::findOrFail($carId);

        $image = $this->imageService->setPrimary($car, $imageId);

        return response()->json([
            'success' => true,
            'message' => 'Primary car image updated successfully.',
            'data' => new CarImageResource($image),
        ]);
    }

    /**
     * Reorder images for a car.
     * PUT /api/v1/admin/cars/{carId}/images/reorder
     */
    public function reorder(ReorderCarImagesRequest $request, int $carId): JsonResponse
    {
        $car = Car::findOrFail($carId);

        $reordered = $this->imageService->reorder($car, $request->validated('image_ids'));

        return response()->json([
            'success' => true,
            'message' => 'Car images reordered successfully.',
            'data' => CarImageResource::collection($reordered),
        ]);
    }
}
