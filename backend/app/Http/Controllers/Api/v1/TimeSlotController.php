<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TimeSlot::where('is_active', true);

        if ($request->filled('product_type')) {
            $type = $request->query('product_type');
            $query->where(function ($q) use ($type) {
                $q->where('allowed_product_types', 'ALL')
                  ->orWhere('allowed_product_types', $type);
            });
        }

        $timeSlots = $query->orderBy('start_time', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $timeSlots,
        ]);
    }
}
