<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Auction;
use App\Models\Country;
use App\Models\City;
use App\Models\Counterparty;
use App\Models\LedgerAccount;
use App\Models\Location;
use App\Models\Port;
use App\Models\RateCard;
use App\Models\RateItem;
use App\Models\RateVersion;
use App\Models\Role;
use App\Models\TransportBrand;
use App\Models\TransportModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::query()->where('code', RoleCode::Admin)->first();

        User::query()->updateOrCreate(
            ['email' => 'admin@autobroker.local'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('Password123!'),
                'role_id' => $adminRole?->id,
                'active' => true,
                'public_offer_status' => 'accepted',
                'public_offer_accepted_at' => now(),
            ],
        );

        $by = Country::query()->updateOrCreate(['code' => 'BY'], ['name' => 'Беларусь', 'active' => true]);
        $us = Country::query()->updateOrCreate(['code' => 'US'], ['name' => 'США', 'active' => true]);
        City::query()->updateOrCreate(['country_id' => $by->id, 'name' => 'Минск']);
        Port::query()->updateOrCreate(['name' => 'Poti'], ['country_id' => $us->id, 'code' => 'POTI']);
        Auction::query()->updateOrCreate(['code' => 'copart'], ['name' => 'Copart', 'active' => true]);
        Auction::query()->updateOrCreate(['code' => 'iaai'], ['name' => 'IAAI', 'active' => true]);
        Location::query()->updateOrCreate(['name' => 'Copart New Jersey'], ['type' => 'auction']);
        Counterparty::query()->updateOrCreate(
            ['code' => 'demo'],
            ['name' => 'Demo counterparty', 'type' => 'dealer', 'active' => true],
        );

        $toyota = TransportBrand::query()->updateOrCreate(['name' => 'Toyota']);
        TransportModel::query()->updateOrCreate(['brand_id' => $toyota->id, 'name' => 'Camry']);

        foreach (['new' => 'Новый', 'won' => 'Выкуплен', 'archive' => 'Архив'] as $code => $title) {
            \App\Models\StatusOrder::query()->updateOrCreate(['code' => $code], ['title' => $title]);
        }

        LedgerAccount::query()->updateOrCreate(['title' => 'Касса USD', 'type' => 'cash'], [
            'owner_type' => 'platform',
            'owner_id' => 0,
            'currency' => 'USD',
            'active' => true,
        ]);
        LedgerAccount::query()->updateOrCreate(['title' => 'Дилеры USD', 'type' => 'dealer'], [
            'owner_type' => 'platform',
            'owner_id' => 0,
            'currency' => 'USD',
            'active' => true,
        ]);

        $card = RateCard::query()->updateOrCreate(['kind' => 'sea', 'title' => 'Море базовый'], ['active' => true]);
        $version = RateVersion::query()->updateOrCreate(
            ['rate_card_id' => $card->id, 'version' => 1, 'layer' => 'base'],
            ['effective_from' => now()],
        );
        RateItem::query()->updateOrCreate(
            ['rate_version_id' => $version->id],
            ['dimensions' => ['port' => 'Poti'], 'amount' => 1450, 'currency' => 'USD'],
        );
    }
}
