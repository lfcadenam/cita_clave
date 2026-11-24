<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceAdminController extends Controller
{
    /**
     * Listado de servicios del salón disponibles para administración.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'all' => 'nullable|boolean',
            'active_only' => 'nullable|boolean',
        ]);

        $query = Service::query()->orderBy('sort_order', 'asc')->orderBy('name', 'asc');

        if ($request->has('active_only') || $request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if (! empty($validated['q'])) {
            $term = '%' . trim($validated['q']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'LIKE', $term)
                  ->orWhere('category', 'LIKE', $term)
                  ->orWhere('description', 'LIKE', $term);
            });
        }

        if ($request->boolean('all')) {
            $services = $query->get();
            return response()->json([
                'success' => true,
                'data' => $services->map(fn ($service) => $this->formatService($service)),
                'meta' => [
                    'total' => $services->count(),
                ],
            ]);
        }

        $perPage = (int) ($validated['per_page'] ?? 10);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginated->items())->map(fn ($service) => $this->formatService($service)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Detalle de un servicio específico.
     */
    public function show(int $id): JsonResponse
    {
        $service = Service::query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatService($service, true),
        ]);
    }

    /**
     * Crear un nuevo tratamiento o servicio.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'duration_minutes' => 'required|integer|min:5|max:480',
            'base_price' => 'required|numeric|min:0',
            'deposit_amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
        ]);

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $imageUrl = $request->file('image')->store('services', 'public');
        } elseif (! empty($validated['image_url'])) {
            $imageUrl = trim($validated['image_url']);
        }

        $service = Service::create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'duration_minutes' => (int) $validated['duration_minutes'],
            'base_price' => (float) $validated['base_price'],
            'deposit_amount' => (float) $validated['deposit_amount'],
            'image_url' => $imageUrl,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento creado exitosamente.',
            'data' => $this->formatService($service, true),
        ], 201);
    }

    /**
     * Actualizar datos de un servicio existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $service = Service::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:5|max:480',
            'base_price' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'image_url' => 'nullable|string|max:500',
            'remove_image' => 'nullable|boolean',
        ]);

        if (isset($validated['name'])) {
            $service->name = trim($validated['name']);
            $service->slug = Str::slug($validated['name']);
        }
        if (array_key_exists('category', $validated)) {
            $service->category = $validated['category'];
        }
        if (array_key_exists('description', $validated)) {
            $service->description = $validated['description'];
        }
        if (isset($validated['duration_minutes'])) {
            $service->duration_minutes = (int) $validated['duration_minutes'];
        }
        if (isset($validated['base_price'])) {
            $service->base_price = (float) $validated['base_price'];
        }
        if (isset($validated['deposit_amount'])) {
            $service->deposit_amount = (float) $validated['deposit_amount'];
        }
        if ($request->has('is_active')) {
            $service->is_active = $request->boolean('is_active');
        }
        if (isset($validated['sort_order'])) {
            $service->sort_order = (int) $validated['sort_order'];
        }

        if ($request->boolean('remove_image')) {
            $service->image_url = null;
        } elseif ($request->hasFile('image')) {
            $service->image_url = $request->file('image')->store('services', 'public');
        } elseif (array_key_exists('image_url', $validated)) {
            $service->image_url = ! empty($validated['image_url']) ? trim($validated['image_url']) : null;
        }

        $service->save();

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento actualizado correctamente.',
            'data' => $this->formatService($service, true),
        ]);
    }

    /**
     * Eliminar o desactivar un servicio.
     */
    public function destroy(int $id): JsonResponse
    {
        $service = Service::query()->findOrFail($id);
        $service->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tratamiento eliminado correctamente.',
        ]);
    }

    protected function formatService(Service $service, bool $detailed = false): array
    {
        $data = [
            'id' => $service->id,
            'name' => $service->name,
            'slug' => $service->slug,
            'category' => $service->category?->value ?? (string)$service->category,
            'duration_minutes' => (int) $service->duration_minutes,
            'formatted_duration' => $service->formatted_duration,
            'base_price' => (float) $service->base_price,
            'deposit_amount' => (float) $service->deposit_amount,
            'is_active' => (bool) $service->is_active,
            'sort_order' => (int) $service->sort_order,
            'image_url' => $service->image_url,
        ];

        if ($detailed) {
            $data['description'] = $service->description;
            $data['created_at'] = $service->created_at?->toIso8601String();
        }

        return $data;
    }
}
