<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PublicSettingController extends Controller
{
    /**
     * Get verified public showroom settings with double-barrier allowlist and caching.
     */
    public function index(): JsonResponse
    {
        $settings = Cache::remember('public_showroom_settings', 3600, function () {
            return Setting::query()
                ->whereIn('key', Setting::ALLOWED_PUBLIC_KEYS)
                ->where('is_public', true)
                ->pluck('value', 'key')
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }
}
