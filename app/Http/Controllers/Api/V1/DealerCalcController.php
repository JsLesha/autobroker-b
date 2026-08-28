<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DealerCalcSsoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DealerCalcController extends Controller
{
    public function sso(Request $request, DealerCalcSsoService $sso): JsonResponse
    {
        try {
            return response()->json([
                'redirect_url' => $sso->createRedirectUrl($request->user()),
            ]);
        } catch (RuntimeException $e) {
            abort(403, $e->getMessage());
        }
    }
}
