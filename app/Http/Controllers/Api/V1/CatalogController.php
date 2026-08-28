<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\City;
use App\Models\Country;
use App\Models\Counterparty;
use App\Models\Credential;
use App\Models\DeliveryType;
use App\Models\DocFee;
use App\Models\Location;
use App\Models\Port;
use App\Models\StatusOrder;
use App\Models\TransportationAgent;
use App\Models\TransportBrand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    public function countries(): JsonResponse
    {
        return response()->json(Cache::remember('catalog:countries', 3600, fn () => Country::query()->where('active', true)->orderBy('name')->get()));
    }

    public function storeCountry(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('directory.create'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:8', 'unique:countries,code'],
            'name' => ['required', 'string', 'max:191'],
        ]);
        $row = Country::query()->create($data);
        Cache::forget('catalog:countries');

        return response()->json($row, 201);
    }

    public function storeCity(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('directory.create'), 403);
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'name' => ['required', 'string', 'max:191'],
        ]);

        return response()->json(City::query()->create($data), 201);
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
        return response()->json(Cache::remember('catalog:auctions', 3600, fn () => Auction::query()->where('active', true)->orderBy('name')->get()));
    }

    public function storeAuction(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('directory.create'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:auctions,code'],
            'name' => ['required', 'string', 'max:191'],
        ]);
        $row = Auction::query()->create($data);
        Cache::forget('catalog:auctions');

        return response()->json($row, 201);
    }

    public function statuses(): JsonResponse
    {
        return response()->json(Cache::remember('catalog:status_orders', 3600, fn () => StatusOrder::query()->orderBy('sort')->get()));
    }

    public function brands(): JsonResponse
    {
        return response()->json(TransportBrand::query()->with('models')->orderBy('name')->get());
    }

    public function storeBrand(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('directory.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:transport_brands,name'],
        ]);

        return response()->json(TransportBrand::query()->create($data), 201);
    }

    public function storeModel(Request $request, TransportBrand $brand): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('directory.create'), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        return response()->json($brand->models()->create($data), 201);
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

    public function updateCounterparty(Request $request, Counterparty $counterparty): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('counterparties.update'), 403);

        $data = $request->validate([
            'type' => ['sometimes', 'string', 'max:32'],
            'name' => ['sometimes', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $counterparty->update($data);

        return response()->json($counterparty->load('banks'));
    }

    public function docFees(): JsonResponse
    {
        return response()->json(DocFee::query()->where('active', true)->orderBy('title')->get());
    }

    public function deliveryTypes(): JsonResponse
    {
        return response()->json(DeliveryType::query()->orderBy('title')->get());
    }

    public function locations(): JsonResponse
    {
        return response()->json(Location::query()->orderBy('name')->get());
    }

    public function agents(): JsonResponse
    {
        return response()->json(TransportationAgent::query()->where('active', true)->orderBy('name')->get());
    }

    public function statusShippings(): JsonResponse
    {
        return response()->json(
            Cache::remember('catalog:status_shippings', 3600, fn () => DB::table('status_shippings')->orderBy('sort')->orderBy('id')->get())
        );
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
