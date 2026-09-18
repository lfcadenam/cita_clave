<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Waitlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    /**
     * Join waitlist for a specific date and preferred time range.
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email|max:255',
            'requested_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'preferred_time_range' => 'required|in:morning,afternoon,anytime',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = Service::findOrFail($validated['service_id']);

        $waitlist = Waitlist::create([
            'service_id' => $service->id,
            'client_name' => $validated['client_name'],
            'client_phone' => $validated['client_phone'],
            'client_email' => $validated['client_email'] ?? null,
            'requested_date' => $validated['requested_date'],
            'preferred_time_range' => $validated['preferred_time_range'],
            'status' => 'waiting',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Te has registrado exitosamente en la lista de espera! Te escribiremos por WhatsApp en cuanto se libere un cupo.',
            'data' => [
                'id' => $waitlist->id,
                'service_name' => $service->name,
                'requested_date' => $waitlist->requested_date->toDateString(),
                'preferred_time_range' => $waitlist->preferred_time_range,
                'status' => $waitlist->status,
            ],
        ], 201);
    }
}