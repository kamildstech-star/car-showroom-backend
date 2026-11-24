<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CarImageService
{
    protected string $disk = 'public';

    /**
     * Upload and optimize multiple images for a car.
     *
     * @param  Car  $car
     * @param  array<UploadedFile>  $files
     * @param  array<int, string|null>  $altTexts
     * @param  bool|null  $forceFirstPrimary
     * @return Collection<int, CarImage>
     *
     * @throws \Throwable
     */
    public function uploadImages(Car $car, array $files, array $altTexts = [], ?bool $forceFirstPrimary = null): Collection
    {
        $maxOrder = $car->images()->max('sort_order');
        $startOrder = $maxOrder !== null ? $maxOrder + 1 : 0;
        $hasExistingPrimary = $car->images()->where('is_primary', true)->exists();

        $savedImages = [];
        $writtenFiles = [];

        DB::beginTransaction();

        try {
            // If requested to make the first uploaded image primary, reset other images
            if ($forceFirstPrimary) {
                $car->images()->update(['is_primary' => false]);
                $hasExistingPrimary = false;
            }

            foreach ($files as $index => $file) {
                // If the car has no primary image, make the very first uploaded image primary
                $isPrimary = false;
                if (! $hasExistingPrimary && $index === 0) {
                    $isPrimary = true;
                    $hasExistingPrimary = true;
                }

                $altText = $altTexts[$index] ?? null;

                // Process image optimization & generate WebP variants
                $paths = $this->optimizeAndStore($file, $car->id);
                $writtenFiles[] = $paths['image_path'];
                if (! empty($paths['thumbnail_path'])) {
                    $writtenFiles[] = $paths['thumbnail_path'];
                }

                $image = $car->images()->create([
                    'image_path' => $paths['image_path'],
                    'thumbnail_path' => $paths['thumbnail_path'],
                    'alt_text' => $altText,
                    'is_primary' => $isPrimary,
                    'sort_order' => $startOrder + $index,
                ]);

                $savedImages[] = $image;
            }

            DB::commit();

            return collect($savedImages);
        } catch (\Throwable $e) {
            DB::rollBack();

            // Safe rollback cleanup: remove any newly written files from storage
            foreach ($writtenFiles as $filePath) {
                Storage::disk($this->disk)->delete($filePath);
            }

            throw $e;
        }
    }

    /**
     * Delete an image safely: commits DB deletion first, then purges files.
     */
    public function deleteImage(Car $car, int|CarImage $image): bool
    {
        if (is_int($image)) {
            $image = $car->images()->findOrFail($image);
        } elseif ($image->car_id !== $car->id) {
            abort(404, 'Image does not belong to this car.');
        }

        $pathsToDelete = array_filter([$image->image_path, $image->thumbnail_path]);
        $wasPrimary = (bool) $image->is_primary;

        DB::transaction(function () use ($car, $image, $wasPrimary) {
            $image->delete();

            // If the deleted image was primary, promote the next image in sort order
            if ($wasPrimary) {
                $nextImage = $car->images()->orderBy('sort_order', 'asc')->first();
                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }
        });

        // Delete physical files only after the DB transaction has committed
        foreach ($pathsToDelete as $path) {
            Storage::disk($this->disk)->delete($path);
        }

        return true;
    }

    /**
     * Atomically set an image as the primary image for a car.
     */
    public function setPrimary(Car $car, int|CarImage $image): CarImage
    {
        if (is_int($image)) {
            $image = $car->images()->findOrFail($image);
        } elseif ($image->car_id !== $car->id) {
            abort(404, 'Image does not belong to this car.');
        }

        DB::transaction(function () use ($car, $image) {
            $car->images()->where('id', '!=', $image->id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });

        $image->refresh();

        return $image;
    }

    /**
     * Reorder images for a car based on submitted IDs array.
     */
    public function reorder(Car $car, array $imageIds): Collection
    {
        $existingIds = $car->images()->pluck('id')->all();

        // Enforce that all submitted IDs belong to this car
        $unmatchedIds = array_diff($imageIds, $existingIds);
        if (! empty($unmatchedIds)) {
            throw ValidationException::withMessages([
                'image_ids' => 'One or more image IDs do not belong to this car.',
            ]);
        }

        DB::transaction(function () use ($car, $imageIds) {
            foreach ($imageIds as $order => $id) {
                $car->images()->where('id', $id)->update(['sort_order' => $order]);
            }
        });

        return $car->images()->orderBy('sort_order', 'asc')->get();
    }

    /**
     * Optimize an uploaded image into full and thumbnail WebP variants.
     *
     * @return array{image_path: string, thumbnail_path: string}
     */
    protected function optimizeAndStore(UploadedFile $file, int $carId): array
    {
        $realPath = $file->getRealPath();
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $uuid = Str::uuid()->toString();

        $mainPath = "cars/{$carId}/{$uuid}.webp";
        $thumbPath = "cars/{$carId}/thumbs/{uuid}.webp";

        $imageResource = $this->decodeImage($realPath, $mime);

        if ($imageResource === false) {
            // Fallback: store original file content if GD decoding is unavailable
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $mainPath = "cars/{$carId}/{$uuid}.{$extension}";
            Storage::disk($this->disk)->put($mainPath, file_get_contents($realPath));

            return [
                'image_path' => $mainPath,
                'thumbnail_path' => null,
            ];
        }

        // Auto-orient based on EXIF if present
        $imageResource = $this->autoOrient($imageResource, $realPath, $mime);

        $origW = imagesx($imageResource);
        $origH = imagesy($imageResource);

        // Generate full-size optimized WebP (max width 1920, max height 1440, quality 85)
        $mainContent = $this->encodeWebpVariant($imageResource, $origW, $origH, 1920, 1440, 85);
        Storage::disk($this->disk)->put($mainPath, $mainContent);

        // Generate thumbnail optimized WebP (max width 600, max height 450, quality 80)
        $thumbContent = $this->encodeWebpVariant($imageResource, $origW, $origH, 600, 450, 80);
        Storage::disk($this->disk)->put($thumbPath, $thumbContent);

        // Release GD resource memory
        imagedestroy($imageResource);

        return [
            'image_path' => $mainPath,
            'thumbnail_path' => $thumbPath,
        ];
    }

    /**
     * Decode an image into a GD image resource.
     */
    protected function decodeImage(string $filePath, string $mime): \GdImage|false
    {
        if (! extension_loaded('gd')) {
            return false;
        }

        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($filePath),
            'image/png' => @imagecreatefrompng($filePath),
            'image/webp' => @imagecreatefromwebp($filePath),
            default => false,
        };
    }

    /**
     * Auto-orient JPEG images based on EXIF orientation data.
     */
    protected function autoOrient(\GdImage $image, string $filePath, string $mime): \GdImage
    {
        if (in_array($mime, ['image/jpeg', 'image/jpg']) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($filePath);
            if (! empty($exif['Orientation'])) {
                $angle = match ((int) $exif['Orientation']) {
                    3 => 180,
                    6 => -90,
                    8 => 90,
                    default => 0,
                };

                if ($angle !== 0) {
                    $rotated = @imagerotate($image, $angle, 0);
                    if ($rotated instanceof \GdImage) {
                        imagedestroy($image);

                        return $rotated;
                    }
                }
            }
        }

        return $image;
    }

    /**
     * Resize and encode GD image to WebP with aspect ratio preservation and transparency.
     */
    protected function encodeWebpVariant(\GdImage $src, int $origW, int $origH, int $maxWidth, int $maxHeight, int $quality): string
    {
        if ($origW <= $maxWidth && $origH <= $maxHeight) {
            $targetW = $origW;
            $targetH = $origH;
        } else {
            $ratio = min($maxWidth / $origW, $maxHeight / $origH);
            $targetW = max(1, (int) round($origW * $ratio));
            $targetH = max(1, (int) round($origH * $ratio));
        }

        $dst = imagecreatetruecolor($targetW, $targetH);

        // Preserve transparency for PNG and WebP sources
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $transparent);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

        ob_start();
        imagewebp($dst, null, $quality);
        $content = (string) ob_get_clean();

        imagedestroy($dst);

        return $content;
    }
}
