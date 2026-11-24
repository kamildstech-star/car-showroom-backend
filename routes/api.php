<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Api\V1\Admin\CarController as AdminCarController;
use App\Http\Controllers\Api\V1\Admin\CarImageController as AdminCarImageController;
use App\Http\Controllers\Api\V1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Api\V1\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CarController;
use App\Http\Controllers\Api\V1\LeadController;
use App\Http\Controllers\Api\V1\PublicSettingController;
use App\Http\Controllers\Api\V1\SitemapController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Prefix: /api/v1)
|--------------------------------------------------------------------------
*/

// Public Health Check
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Throwable $e) {
        $dbStatus = 'disconnected: ' . $e->getMessage();
    }

    return response()->json([
        'status' => 'ok',
        'service' => 'Hassan Motors API',
        'version' => '1.0.0',
        'environment' => config('app.env'),
        'timestamp' => now()->toIso8601String(),
        'database' => $dbStatus,
    ]);
});

// Public Inventory Routes
Route::prefix('cars')->group(function () {
    Route::get('/', [CarController::class, 'index']);
    Route::get('/featured', [CarController::class, 'featured']);
    Route::get('/latest', [CarController::class, 'latest']);
    Route::get('/{slug}', [CarController::class, 'show']);
});

Route::get('/brands', [BrandController::class, 'index']);

// Public Showroom Settings (Allowlist Filtered)
Route::get('/settings', [PublicSettingController::class, 'index']);

// Public Sitemap Feed (Chunked Stream)
Route::get('/sitemap/cars', [SitemapController::class, 'cars']);

// Public Inquiry & Lead Routes
Route::prefix('leads')->group(function () {
    Route::post('/', [LeadController::class, 'store'])
        ->middleware('throttle:public-leads');
    Route::post('/{id}/whatsapp-clicked', [LeadController::class, 'recordClick'])
        ->middleware('throttle:60,1');
});

// Admin Protected & Auth Routes
Route::prefix('admin')->group(function () {
    // Rate-limited Admin Login (5 attempts per minute)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:admin-login');

    // Protected Admin Routes (Sanctum SPA Session Auth)
    Route::middleware('auth:sanctum')->group(function () {
        // Account & Session
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);

        // Dashboard Analytics
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Showroom Settings
        Route::get('/settings', [AdminSettingController::class, 'index']);
        Route::put('/settings', [AdminSettingController::class, 'update']);

        // Car Management
        Route::prefix('cars')->group(function () {
            Route::get('/', [AdminCarController::class, 'index']);
            Route::post('/', [AdminCarController::class, 'store']);
            Route::get('/{id}', [AdminCarController::class, 'show']);
            Route::put('/{id}', [AdminCarController::class, 'update']);
            Route::delete('/{id}', [AdminCarController::class, 'destroy']);
            Route::patch('/{id}/status', [AdminCarController::class, 'updateStatus']);
            Route::patch('/{id}/publication-status', [AdminCarController::class, 'updatePublicationStatus']);
            Route::patch('/{id}/featured', [AdminCarController::class, 'updateFeatured']);

            // Car Image Management
            Route::post('/{carId}/images', [AdminCarImageController::class, 'store']);
            Route::delete('/{carId}/images/{imageId}', [AdminCarImageController::class, 'destroy']);
            Route::patch('/{carId}/images/{imageId}/primary', [AdminCarImageController::class, 'setPrimary']);
            Route::put('/{carId}/images/reorder', [AdminCarImageController::class, 'reorder']);
        });

        // Brand Management
        Route::prefix('brands')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index']);
            Route::post('/', [AdminBrandController::class, 'store']);
            Route::get('/{id}', [AdminBrandController::class, 'show']);
            Route::put('/{id}', [AdminBrandController::class, 'update']);
            Route::delete('/{id}', [AdminBrandController::class, 'destroy']);
        });

        // Lead Management
        Route::prefix('leads')->group(function () {
            Route::get('/', [AdminLeadController::class, 'index']);
            Route::get('/{id}', [AdminLeadController::class, 'show']);
            Route::patch('/{id}/status', [AdminLeadController::class, 'updateStatus']);
            Route::patch('/{id}/notes', [AdminLeadController::class, 'updateNotes']);
        });
    });
});
