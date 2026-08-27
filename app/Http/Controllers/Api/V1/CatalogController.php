<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\City;
use App\Models\Country;
use App\Models\Counterparty;
use App\Models\Credential;
use App\Models\Port;
use App\Models\TransportBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function countries(): JsonResponse
    {
        return response()->json(Country::query()->where('active', true)->orderBy('name')->get());
    }

    public function cities(Request $request): JsonResponse
    {
        $query = City::query()->with('country');
        if ($id = $request->integer('country_id')) {
            $query->where('country_id', $id);
        }

        return response()->json($query->orderBy('name')->get());
    }

    public function ports(): JsonResponse
    {
        return response()->json(Port::query()->orderBy('name')->get());
    }

    public function auctions(): JsonResponse
    {
        return response()->json(Auction::query()->where('active', true)->orderBy('name')->get());
    }

    public function brands(): JsonResponse
    {
        return response()->json(TransportBrand::query()->with('models')->orderBy('name')->get());
    }

    public function counterparties(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('counterparties.read'), 403);

        $query = Counterparty::query()->with('banks');
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        return response()->json($query->orderBy('name')->paginate(40));
    }

    public function storeCounterparty(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('counterparties.create'), 403);

        $data = $request->validate([
            'type' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
        ]);

        return response()->json(Counterparty::query()->create($data), 201);
    }

    public function credentials(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('credentials.read'), 403);

        return response()->json(Credential::query()->orderBy('id')->paginate(40));
    }

    public function storeCredential(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('credentials.create'), 403);

        $data = $request->validate([
            'auction_id' => ['nullable', 'exists:auctions,id'],
            'login' => ['required', 'string'],
            'secret' => ['required', 'string', 'min:4'],
        ]);

        $data['user_id'] = $request->user()->id;

        return response()->json(Credential::query()->create($data), 201);
    }
}
