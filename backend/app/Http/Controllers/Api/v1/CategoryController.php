<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        // Caché de 30 minutos para listado de categorías activas
        $categories = Cache::remember('api_categories_list', 1800, function () {
            return Category::where('is_active', true)
                ->withCount('products')
                ->orderBy('display_order', 'asc')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Cache::remember("api_category_{$slug}", 1800, function () use ($slug) {
            return Category::where('slug', $slug)
                ->where('is_active', true)
                ->with(['products'])
                ->firstOrFail();
        });

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }
}
