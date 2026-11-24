<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    /**
     * Get all showroom configuration settings.
     * GET /api/v1/admin/settings
     */
    public function index(): JsonResponse
    {
        $settings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'message' => 'Settings retrieved successfully.',
            'data' => $settings,
        ]);
    }

    /**
     * Update showroom configuration settings safely.
     * PUT /api/v1/admin/settings
     */
    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            $group = in_array($key, [
                'showroom_phone',
                'showroom_whatsapp',
                'showroom_email',
                'showroom_address',
                'showroom_city',
            ], true) ? 'contact' : 'general';

            Setting::set($key, $value, $group, true);
        }

        $allSettings = Setting::all()->pluck('value', 'key');

        return response()->json([
            'success' => true,
            'message' => 'Showroom settings updated successfully.',
            'data' => $allSettings,
        ]);
    }
}
