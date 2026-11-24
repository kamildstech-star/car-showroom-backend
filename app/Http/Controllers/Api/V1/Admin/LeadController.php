<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLeadNotesRequest;
use App\Http\Requests\Admin\UpdateLeadStatusRequest;
use App\Http\Requests\LeadFilterRequest;
use App\Http\Resources\LeadAdminResource;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * List and filter leads for admin management.
     * GET /api/v1/admin/leads
     */
    public function index(LeadFilterRequest $request): JsonResponse
    {
        $leads = $this->leadService->getAdminLeads($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Leads retrieved successfully.',
            'data' => LeadAdminResource::collection($leads),
            'meta' => [
                'current_page' => $leads->currentPage(),
                'last_page' => $leads->lastPage(),
                'per_page' => $leads->perPage(),
                'total' => $leads->total(),
            ],
        ]);
    }

    /**
     * View detailed inquiry information.
     * GET /api/v1/admin/leads/{id}
     */
    public function show(int $id): JsonResponse
    {
        $lead = Lead::with('car.brand')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Lead retrieved successfully.',
            'data' => new LeadAdminResource($lead),
        ]);
    }

    /**
     * Update lead CRM status.
     * PATCH /api/v1/admin/leads/{id}/status
     */
    public function updateStatus(UpdateLeadStatusRequest $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $updated = $this->leadService->updateStatus($lead, $request->validated('status'));

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated successfully.',
            'data' => new LeadAdminResource($updated),
        ]);
    }

    /**
     * Update internal notes on lead.
     * PATCH /api/v1/admin/leads/{id}/notes
     */
    public function updateNotes(UpdateLeadNotesRequest $request, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $updated = $this->leadService->updateNotes($lead, $request->validated('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Lead notes updated successfully.',
            'data' => new LeadAdminResource($updated),
        ]);
    }
}
