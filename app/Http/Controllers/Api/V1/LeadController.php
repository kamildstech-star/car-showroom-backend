<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Submit customer inquiry and receive prefilled WhatsApp URL.
     * POST /api/v1/leads
     */
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $result = $this->leadService->createLead($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Inquiry submitted successfully.',
            'data' => [
                'lead_id' => $result['lead']->id,
                'whatsapp_url' => $result['whatsapp_url'],
            ],
        ], 201);
    }

    /**
     * Record customer click on WhatsApp action.
     * POST /api/v1/leads/{id}/whatsapp-clicked
     */
    public function recordClick(int $id): JsonResponse
    {
        $lead = Lead::findOrFail($id);
        $this->leadService->recordWhatsAppClick($lead);

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp click recorded.',
        ]);
    }
}
