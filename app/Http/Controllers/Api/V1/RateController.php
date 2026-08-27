<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RateCard;
use App\Services\RateCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('rates.read'), 403);

        return response()->json(
            RateCard::query()->with('versions.items')->orderBy('id')->get()
        );
    }

    public function quote(Request $request, RateCalculator $calculator): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['sometimes', 'string'],
            'port' => ['nullable', 'string'],
            'type' => ['nullable', 'string'],
            'ev' => ['nullable', 'boolean'],
        ]);

        return response()->json($calculator->quote($data));
    }
}
