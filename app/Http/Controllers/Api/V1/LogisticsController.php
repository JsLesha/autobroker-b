<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Models\LocalHaul;
use App\Models\ShippingRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticsController extends Controller
{
    public function shipping(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.read'), 403);

        $query = ShippingRecord::query()
            ->with(['lot.brand', 'lot.route'])
            ->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($number = $request->string('container_number')->toString()) {
            $query->where('container_number', 'like', "%{$number}%");
        }
        if ($q = $request->string('q')->toString()) {
            $query->whereHas('lot', function ($lot) use ($q) {
                $lot->where('vin', 'like', "%{$q}%")
                    ->orWhere('lot_number', 'like', "%{$q}%")
                    ->orWhere('transport_name', 'like', "%{$q}%");
            });
        }

        return response()->json($query->paginate($request->integer('limit') ?: 40));
    }

    public function updateShipping(Request $request, ShippingRecord $shipping): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.update'), 403);

        $shipping->update($request->validate([
            'status' => ['sometimes', 'string', 'max:64'],
            'container_number' => ['nullable', 'string', 'max:16'],
            'documents_received' => ['sometimes', 'boolean'],
            'lot_accepted_by_client' => ['sometimes', 'boolean'],
            'ready_to_load_at' => ['nullable', 'date'],
            'loaded_at' => ['nullable', 'date'],
            'sailed_at' => ['nullable', 'date'],
            'arrived_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]));

        return response()->json($shipping->load('lot'));
    }

    public function containers(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('containers.read'), 403);

        $query = Container::query()->with('lots')->orderByDesc('id');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($number = $request->string('number')->toString() ?: $request->string('q')->toString()) {
            $query->where('number', 'like', "%{$number}%");
        }
        if ($request->boolean('archived')) {
            $query->whereNotNull('archived_at');
        }

        return response()->json($query->paginate($request->integer('limit') ?: 40));
    }

    public function showContainer(Request $request, Container $container): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('containers.read'), 403);

        return response()->json($container->load('lots'));
    }

    public function storeContainer(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('containers.create'), 403);

        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'string', 'max:32'],
            'consolidation' => ['sometimes', 'boolean'],
            'is_full' => ['sometimes', 'boolean'],
            'l_date' => ['nullable', 'date'],
            'pod' => ['nullable', 'date'],
            'lot_ids' => ['array'],
            'lot_ids.*' => ['exists:lots,id'],
        ]);

        $container = Container::query()->create([
            'number' => $data['number'] ?? null,
            'status' => $data['status'] ?? 'application',
            'consolidation' => $data['consolidation'] ?? false,
            'is_full' => $data['is_full'] ?? false,
            'l_date' => $data['l_date'] ?? null,
            'pod' => $data['pod'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        if (! empty($data['lot_ids'])) {
            $container->lots()->sync($data['lot_ids']);
        }

        return response()->json($container->load('lots'), 201);
    }

    public function updateContainer(Request $request, Container $container): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('containers.update'), 403);

        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:64'],
            'status' => ['sometimes', 'string', 'max:32'],
            'consolidation' => ['sometimes', 'boolean'],
            'is_full' => ['sometimes', 'boolean'],
            'l_date' => ['nullable', 'date'],
            'pod' => ['nullable', 'date'],
            'archived_at' => ['nullable', 'date'],
            'lot_ids' => ['array'],
            'lot_ids.*' => ['exists:lots,id'],
        ]);

        $container->update(collect($data)->except('lot_ids')->all());
        if (array_key_exists('lot_ids', $data)) {
            $container->lots()->sync($data['lot_ids']);
        }

        return response()->json($container->load('lots'));
    }

    public function hauls(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.read'), 403);

        $query = LocalHaul::query()->with('lot')->orderByDesc('id');
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($q = $request->string('q')->toString()) {
            $query->where(function ($inner) use ($q) {
                $inner->where('from_place', 'like', "%{$q}%")
                    ->orWhere('to_place', 'like', "%{$q}%")
                    ->orWhereHas('lot', fn ($lot) => $lot->where('vin', 'like', "%{$q}%")->orWhere('lot_number', 'like', "%{$q}%"));
            });
        }

        return response()->json($query->paginate($request->integer('limit') ?: 40));
    }

    public function storeHaul(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.update'), 403);

        $data = $request->validate([
            'lot_id' => ['required', 'exists:lots,id'],
            'from_place' => ['nullable', 'string'],
            'to_place' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:32'],
            'transit_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]);
        $data['status'] = $data['status'] ?? 'application';

        return response()->json(LocalHaul::query()->create($data)->load('lot'), 201);
    }

    public function updateHaul(Request $request, LocalHaul $haul): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.update'), 403);

        $haul->update($request->validate([
            'from_place' => ['nullable', 'string'],
            'to_place' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:32'],
            'transit_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]));

        return response()->json($haul->load('lot'));
    }
}
