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

        return response()->json(
            ShippingRecord::query()->with('lot')->orderByDesc('id')->paginate(40)
        );
    }

    public function updateShipping(Request $request, ShippingRecord $shipping): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.update'), 403);

        $shipping->update($request->validate([
            'status' => ['required', 'string', 'max:64'],
            'ready_to_load_at' => ['nullable', 'date'],
            'loaded_at' => ['nullable', 'date'],
            'arrived_at' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
        ]));

        return response()->json($shipping);
    }

    public function containers(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('containers.read'), 403);

        return response()->json(
            Container::query()->with('lots')->orderByDesc('id')->paginate(40)
        );
    }

    public function storeContainer(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('containers.create'), 403);

        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:64'],
            'lot_ids' => ['array'],
            'lot_ids.*' => ['exists:lots,id'],
        ]);

        $container = Container::query()->create([
            'number' => $data['number'] ?? null,
            'status' => 'application',
            'created_by' => $request->user()->id,
        ]);

        if (! empty($data['lot_ids'])) {
            $container->lots()->sync($data['lot_ids']);
        }

        return response()->json($container->load('lots'), 201);
    }

    public function hauls(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('logistics.read'), 403);

        return response()->json(
            LocalHaul::query()->with('lot')->orderByDesc('id')->paginate(40)
        );
    }
}
