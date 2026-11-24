<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CarAdminResource;
use App\Http\Resources\LeadAdminResource;
use App\Models\Car;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Retrieve aggregated showroom metrics and recent activity for admin.
     * GET /api/v1/admin/dashboard
     */
    public function index(): JsonResponse
    {
        $metrics = [
            'total_cars' => Car::count(),
            'available_cars' => Car::where('status', 'available')->count(),
            'sold_cars' => Car::where('status', 'sold')->count(),
            'draft_cars' => Car::where('publication_status', 'draft')->count(),
            'featured_cars' => Car::where('is_featured', true)->count(),
            'total_leads' => Lead::count(),
            'new_leads' => Lead::where('status', 'new')->count(),
            'contacted_leads' => Lead::where('status', 'contacted')->count(),
            'converted_leads' => Lead::where('status', 'converted')->count(),
        ];

        $recentCars = Car::with(['brand', 'primaryImage'])
            ->latest('created_at')
            ->take(5)
            ->get();

        $recentLeads = Lead::with('car.brand')
            ->latest('created_at')
            ->take(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard metrics retrieved successfully.',
            'data' => [
                'metrics' => $metrics,
                'recent_cars' => CarAdminResource::collection($recentCars),
                'recent_leads' => LeadAdminResource::collection($recentLeads),
            ],
        ]);
    }
}
