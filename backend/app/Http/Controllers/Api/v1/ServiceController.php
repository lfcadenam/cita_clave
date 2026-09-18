<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * List all active beauty services grouped by category.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->where('is_active', true)->orderBy('sort_order');

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $services = $query->get();

        return response()->json([
            'success' => true,
            'data' => $services,
            'meta' => [
                'total' => $services->count(),
                'categories' => Service::query()->where('is_active', true)->pluck('category')->unique()->values(),
            ],
        ]);
    }

    /**
     * Show detailed information of a single service.
     */
    public function show(int $id): JsonResponse
    {
        $service = Service::query()->where('is_active', true)->find($id);

        if (! $service) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado o inactivo.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }
}