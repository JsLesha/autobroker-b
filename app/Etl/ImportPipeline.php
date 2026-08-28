<?php

namespace App\Etl;

use App\Etl\Contracts\LegacySource;
use App\Models\Auction;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\City;
use App\Models\Container;
use App\Models\Counterparty;
use App\Models\Country;
use App\Models\Credential;
use App\Models\DeliveryType;
use App\Models\DocFee;
use App\Models\FinanceLine;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Location;
use App\Models\Lot;
use App\Models\LotClient;
use App\Models\LotImage;
use App\Models\LotNote;
use App\Models\LotPricing;
use App\Models\LotRoute;
use App\Models\LotVehicle;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Port;
use App\Models\Role;
use App\Models\ShippingEvent;
use App\Models\ShippingRecord;
use App\Models\StatusOrder;
use App\Models\TransportBrand;
use App\Models\TransportationAgent;
use App\Models\TransportModel;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ImportPipeline
{
    /** @var array<string, int> */
    public array $counts = [];

    public function __construct(
        private readonly LegacySource $source,
        private readonly IdMapper $map,
        private readonly bool $sanitize,
        private readonly bool $dryRun,
    ) {
    }

    public function run(): void
    {
        $this->step('directories', fn () => $this->directories());
        $this->step('identity', fn () => $this->identity());
        $this->step('counterparties', fn () => $this->counterparties());
        $this->step('lots', fn () => $this->lots());
        $this->step('notes', fn () => $this->notes());
        $this->step('shipping', fn () => $this->shipping());
        $this->step('finance', fn () => $this->finance());
        $this->step('containers', fn () => $this->containers());
        $this->step('wallets', fn () => $this->wallets());
        $this->step('chats', fn () => $this->chats());
        $this->step('prebid', fn () => $this->prebid());
    }

    private function step(string $name, callable $fn): void
    {
        if ($this->dryRun) {
            $this->counts[$name] = 0;
            foreach ($this->legacyTables($name) as $table) {
                $this->counts[$name] += $this->source->count($table);
            }

            return;
        }

        DB::transaction(function () use ($fn, $name) {
            $before = $this->map->counts();
            $fn();
            $after = $this->map->counts();
            $this->counts[$name] = array_sum($after) - array_sum($before);
        });
    }

    /** @return list<string> */
    private function legacyTables(string $step): array
    {
        return match ($step) {
            'directories' => [
                'countries', 'cities', 'ports', 'locations', 'auctions',
                'transport_brands', 'transport_models', 'status_orders', 'status_shippings', 'status_finances',
                'transport_fuels', 'transport_drives', 'transport_transmissions', 'transport_highlights',
                'transport_keys', 'transport_odometer_units', 'transport_run_statuses', 'transport_sizes',
                'delivery_types', 'doc_fees', 'transportation_agents',
            ],
            'identity' => ['user_roles', 'access_rights', 'users'],
            'counterparties' => ['counterparties', 'credentials'],
            'lots' => ['general_information'],
            'notes' => ['transport_notes'],
            'shipping' => ['shipping_information', 'image_information'],
            'finance' => ['fields', 'invoice_information', 'payment_information'],
            'containers' => ['containers', 'container_general_information'],
            'wallets' => ['virtual_wallets', 'loan_wallets', 'erip_transactions'],
            'chats' => ['chats', 'chat_messages'],
            'prebid' => ['pre_bids', 'prebid_transports', 'prebid_transport_bids'],
            default => [],
        };
    }

    private function directories(): void
    {
        foreach ($this->source->rows('countries') as $row) {
            $model = Country::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? $row['short_name_en'] ?? $row['id'])],
                ['name' => $row['name'] ?? $row['name_ru'] ?? $row['name_en'] ?? $row['title'] ?? 'Country '.$row['id'], 'active' => (bool) ($row['active'] ?? true)],
            );
            $this->map->remember('countries', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('cities') as $row) {
            $countryId = $this->map->get('countries', $row['country_id'] ?? null) ?? Country::query()->value('id');
            if (! $countryId) {
                continue;
            }
            $model = City::query()->updateOrCreate(
                ['country_id' => $countryId, 'name' => $row['name'] ?? $row['name_ru'] ?? $row['name_en'] ?? $row['title'] ?? 'City'],
                ['aec_id' => $row['xml_id'] ?? $row['aec_id'] ?? $row['xml_location_id'] ?? null],
            );
            $this->map->remember('cities', $row['id'] ?? null, $model->id);
            if (! empty($row['is_port'])) {
                $port = Port::query()->updateOrCreate(
                    ['name' => $model->name],
                    ['country_id' => $countryId, 'code' => $row['short_name_en'] ?? $row['xml_port_id'] ?? null],
                );
                $this->map->remember('city_ports', $row['id'] ?? null, $port->id);
            }
        }
        foreach ($this->source->rows('ports') as $row) {
            $model = Port::query()->updateOrCreate(
                ['name' => $row['name'] ?? $row['title'] ?? 'Port'],
                [
                    'country_id' => $this->map->get('countries', $row['country_id'] ?? null),
                    'code' => $row['code'] ?? null,
                ],
            );
            $this->map->remember('ports', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('auctions') as $row) {
            $model = Auction::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? 'auc-'.$row['id'])],
                ['name' => $row['name'] ?? 'Auction', 'active' => true, 'country_id' => $this->map->get('countries', $row['country_id'] ?? null)],
            );
            $this->map->remember('auctions', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('transport_brands') as $row) {
            $model = TransportBrand::query()->updateOrCreate(['name' => $row['name'] ?? $row['title'] ?? 'Brand']);
            $this->map->remember('transport_brands', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('transport_models') as $row) {
            $brandId = $this->map->get('transport_brands', $row['transport_brand_id'] ?? $row['brand_id'] ?? null);
            if (! $brandId) {
                continue;
            }
            $model = TransportModel::query()->updateOrCreate(
                ['brand_id' => $brandId, 'name' => $row['name'] ?? $row['title'] ?? 'Model'],
            );
            $this->map->remember('transport_models', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('status_orders') as $row) {
            $model = StatusOrder::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? $row['id'])],
                ['title' => $row['title'] ?? $row['name'] ?? 'Status'],
            );
            $this->map->remember('status_orders', $row['id'] ?? null, $model->id);
        }
        $this->importNamed('status_shippings', 'status_shippings');
        $this->importNamed('status_finances', 'status_finances');
        foreach ([
            'transport_fuels', 'transport_drives', 'transport_transmissions', 'transport_highlights',
            'transport_keys', 'transport_odometer_units', 'transport_run_statuses', 'transport_sizes',
        ] as $table) {
            $this->importNamed($table, $table);
        }
        foreach ($this->source->rows('locations') as $row) {
            $model = Location::query()->create([
                'name' => $row['name'] ?? $row['full_name'] ?? 'Location '.$row['id'],
                'type' => 'auction',
            ]);
            $this->map->remember('locations', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('delivery_types') as $row) {
            $model = DeliveryType::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? 'dt-'.$row['id'])],
                ['title' => $row['name'] ?? $row['title'] ?? 'Delivery'],
            );
            $this->map->remember('delivery_types', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('doc_fees') as $row) {
            $model = DocFee::query()->updateOrCreate(
                ['title' => $row['title'] ?? 'Doc fee '.$row['id']],
                ['amount' => $row['price'] ?? $row['amount'] ?? 0, 'active' => true],
            );
            $this->map->remember('doc_fees', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('transportation_agents') as $row) {
            $model = TransportationAgent::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? 'agent-'.$row['id'])],
                ['name' => $row['name'] ?? 'Agent', 'active' => (bool) ($row['active'] ?? true)],
            );
            $this->map->remember('transportation_agents', $row['id'] ?? null, $model->id);
        }
    }

    private function identity(): void
    {
        foreach ($this->source->rows('user_roles') as $row) {
            $model = Role::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? 'role-'.$row['id'])],
                ['title' => $row['title'] ?? $row['name'] ?? 'Role', 'is_prebid' => (bool) ($row['is_prebid'] ?? false)],
            );
            $this->map->remember('user_roles', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('access_rights') as $row) {
            $code = (string) ($row['code'] ?? 'right-'.$row['id']);
            $normalized = str_contains($code, '.') ? $code : str_replace('_', '.', $code);
            $model = Permission::query()->updateOrCreate(
                ['code' => $normalized],
                [
                    'title' => $row['title'] ?? $code,
                    'group_name' => $row['group_type_code'] ?? explode('.', $normalized)[0],
                    'description' => $row['description'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                    'group_type_code' => $row['group_type_code'] ?? null,
                    'sort' => (int) ($row['sort'] ?? 0),
                ],
            );
            $this->map->remember('access_rights', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('users') as $row) {
            $email = (string) ($row['email'] ?? 'user'.$row['id'].'@legacy.local');
            if ($this->sanitize) {
                $email = 'user'.$row['id'].'@sanitized.local';
            }
            $model = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $row['nickname'] ?? $email,
                    'nickname' => $row['nickname'] ?? null,
                    'password' => Hash::make($this->sanitize ? 'Password123!' : Str::password(24)),
                    'role_id' => $this->map->get('user_roles', $row['user_role_id'] ?? null),
                    'active' => (bool) ($row['active'] ?? false),
                    'active_prebid' => (bool) ($row['active_prebid'] ?? false),
                    'telegram_id' => $row['telegram_id'] ?? null,
                    'public_offer_status' => $row['public_offer_status'] ?? 'pending',
                ],
            );
            $this->map->remember('users', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('user_role_access_right') as $row) {
            $roleId = $this->map->get('user_roles', $row['user_role_id'] ?? null);
            $permId = $this->map->get('access_rights', $row['access_right_id'] ?? null);
            if ($roleId && $permId) {
                DB::table('role_permission')->insertOrIgnore(['role_id' => $roleId, 'permission_id' => $permId]);
            }
        }
    }

    private function counterparties(): void
    {
        foreach ($this->source->rows('counterparties') as $row) {
            $model = Counterparty::query()->updateOrCreate(
                ['code' => (string) ($row['code'] ?? 'cp-'.$row['id'])],
                [
                    'name' => $row['name'] ?? 'Counterparty',
                    'type' => (string) ($row['type'] ?? 'other'),
                    'email' => $this->sanitize ? null : ($row['email'] ?? null),
                    'phone' => $this->sanitize ? null : ($row['phone'] ?? null),
                    'active' => (bool) ($row['active'] ?? true),
                    'country_id' => $this->map->get('countries', $row['country_id'] ?? null),
                    'is_default' => (bool) ($row['is_default'] ?? false),
                    'payment_types' => is_string($row['payment_types'] ?? null)
                        ? json_decode((string) $row['payment_types'], true)
                        : ($row['payment_types'] ?? null),
                ],
            );
            $this->map->remember('counterparties', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('credentials') as $row) {
            $secret = (string) ($row['password'] ?? '');
            if ($this->sanitize) {
                $secret = 'sanitized';
            }
            $model = Credential::query()->create([
                'login' => $this->sanitize ? 'login-'.$row['id'] : ($row['login'] ?? ''),
                'secret' => $secret,
                'buyer_code' => $this->sanitize ? null : ($row['buyerCode'] ?? null),
                'auction_id' => $this->map->get('auctions', $row['auction_id'] ?? null),
                'counterparty_id' => $this->map->get('counterparties', $row['counterparty_id'] ?? null),
                'active' => (bool) ($row['active'] ?? true),
            ]);
            $this->map->remember('credentials', $row['id'] ?? null, $model->id);
        }
    }

    private function lots(): void
    {
        foreach ($this->source->rows('general_information') as $row) {
            $lot = Lot::query()->create([
                'vin' => (string) ($row['vin'] ?? 'UNKNOWN'),
                'lot_number' => $row['lot'] ?? null,
                'transport_name' => $row['transport_name'] ?? null,
                'outside' => (bool) ($row['outside'] ?? false),
                'is_unformat_vin' => (bool) ($row['is_unformat_vin'] ?? false),
                'is_auction_participant' => (bool) ($row['is_auction_participant'] ?? false),
                'year' => $row['year'] ?? null,
                'date_buy' => $row['date_buy'] ?? null,
                'brand_id' => $this->map->get('transport_brands', $row['transport_brand_id'] ?? null),
                'model_id' => $this->map->get('transport_models', $row['transport_model_id'] ?? null),
                'created_by' => $this->map->get('users', $row['creator_user_id'] ?? null),
                'buyer_user_id' => $this->map->get('users', $row['buyer_user_id'] ?? null),
                'counterparty_id' => $this->map->get('counterparties', $row['counterparty_id'] ?? null),
                'credential_id' => $this->map->get('credentials', $row['credential_id'] ?? null),
                'doc_fee_id' => $this->map->get('doc_fees', $row['doc_fee_id'] ?? null),
                'transportation_agent_id' => $this->map->get('transportation_agents', $row['transportation_agent_id'] ?? null),
                'status_order_id' => $this->map->get('status_orders', $row['status_order_id'] ?? null),
                'status_shipping_id' => $this->map->get('status_shippings', $row['status_shipping_id'] ?? null),
                'status_finance_id' => $this->map->get('status_finances', $row['status_finance_id'] ?? null),
                'buyer_role_id' => $this->map->get('user_roles', $row['buyer_role_id'] ?? null),
                'status_order' => $this->statusCode('status_orders', $row['status_order_id'] ?? null, 'imported'),
                'status_shipping' => $this->statusCode('status_shippings', $row['status_shipping_id'] ?? null, 'imported'),
                'status_finance' => $this->statusCode('status_finances', $row['status_finance_id'] ?? null, 'imported'),
            ]);
            $this->map->remember('general_information', $row['id'] ?? null, $lot->id);

            LotVehicle::query()->create([
                'lot_id' => $lot->id,
                'size_id' => $this->map->get('transport_sizes', $row['transport_size_id'] ?? null),
                'fuel_id' => $this->map->get('transport_fuels', $row['transport_fuel_id'] ?? null),
                'drive_id' => $this->map->get('transport_drives', $row['transport_drive_id'] ?? null),
                'transmission_id' => $this->map->get('transport_transmissions', $row['transport_transmission_id'] ?? null),
                'highlight_id' => $this->map->get('transport_highlights', $row['transport_highlight_id'] ?? null),
                'keys_id' => $this->map->get('transport_keys', $row['keys'] ?? null),
                'odometer_unit_id' => $this->map->get('transport_odometer_units', $row['odometer_unit_id'] ?? null),
                'run_status_id' => $this->map->get('transport_run_statuses', $row['run_status_id'] ?? null),
                'engine' => $row['engine'] ?? null,
                'engine_hp' => $row['engine_hp'] ?? null,
                'cylinders' => $row['cylinders'] ?? null,
                'odometer' => $row['odometer'] ?? null,
                'equipment' => $row['equipment'] ?? null,
                'complectation' => $row['complectation'] ?? null,
                'body_type' => $row['body_type'] ?? null,
                'electric' => (bool) ($row['electric'] ?? false),
                'color_id' => $row['color_id'] ?? null,
            ]);
            LotPricing::query()->create([
                'lot_id' => $lot->id,
                'hammer_price' => $row['price'] ?? 0,
                'start_price' => $row['start_price'] ?? 0,
                'step_price' => $row['step_price'] ?? 0,
                'cost_price' => $row['cost_price'] ?? null,
                'min_price' => $row['min_price'] ?? null,
                'now_price' => $row['now_price'] ?? null,
                'total' => $row['price'] ?? 0,
            ]);
            if (! $this->sanitize) {
                LotClient::query()->create([
                    'lot_id' => $lot->id,
                    'full_name' => $row['client_full_name'] ?? null,
                    'last_name' => $row['client_last_name'] ?? null,
                    'first_middle_name' => $row['client_first_middle_name'] ?? null,
                    'date_of_birth' => $row['client_date_of_birth'] ?? null,
                    'phone' => $row['client_phone_number'] ?? null,
                    'messenger' => $row['client_messenger'] ?? null,
                    'email' => $row['client_email'] ?? null,
                ]);
            }
            LotRoute::query()->create([
                'lot_id' => $lot->id,
                'city_from_id' => $this->map->get('cities', $row['city_from_id'] ?? $row['location_from_id'] ?? null),
                'city_to_id' => $this->map->get('cities', $row['city_to_id'] ?? $row['location_to_id'] ?? null),
                'port_to_id' => $this->map->get('city_ports', $row['port_to_id'] ?? null),
                'location_from_id' => $this->map->get('locations', $row['location_from_id'] ?? null),
                'location_to_id' => $this->map->get('locations', $row['location_to_id'] ?? null),
                'delivery_type_id' => $this->map->get('delivery_types', $row['delivery_type_id'] ?? null),
                'route_label' => $row['routes'] ?? null,
                'package_service_id' => $row['rates_package_service_id'] ?? null,
                'transportation_agent_id' => $this->map->get('transportation_agents', $row['transportation_agent_id'] ?? null),
                'carrier_id' => $row['carrier_id'] ?? null,
            ]);
            ShippingRecord::query()->create(['lot_id' => $lot->id, 'status' => 'imported']);
            Chat::query()->firstOrCreate(['lot_id' => $lot->id, 'type' => 'lot'], ['title' => 'Лот '.$lot->vin]);
        }
    }

    private function notes(): void
    {
        foreach ($this->source->rows('transport_notes') as $row) {
            $lotId = null;
            if (! empty($row['lot'])) {
                $lotId = Lot::query()->where('lot_number', $row['lot'])->value('id');
            }
            if (! $lotId) {
                continue;
            }
            LotNote::query()->create([
                'lot_id' => $lotId,
                'body' => (string) ($row['text'] ?? ''),
                'noted_on' => $row['date'] ?? null,
                'lot_label' => $row['lot'] ?? null,
                'credential_id' => $this->map->get('credentials', $row['credential_id'] ?? null),
                'counterparty_id' => $this->map->get('counterparties', $row['counterparty_id'] ?? null),
            ]);
        }
    }

    private function shipping(): void
    {
        $dateColumns = [
            'arrival_warehouse', 'date_arrival', 'lot_payment', 'call_auction', 'lot_send',
            'shipping_usa', 'arrival_end', 'loaded_at', 'arrive_at', 'auction_pickup_at',
        ];
        foreach ($this->source->rows('shipping_information') as $row) {
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            if (! $lotId) {
                continue;
            }
            ShippingRecord::query()->where('lot_id', $lotId)->update([
                'container_number' => $row['number_container'] ?? null,
                'documents_received' => (bool) ($row['documents_received'] ?? false),
                'lot_accepted_by_client' => (bool) ($row['lot_accepted_by_the_client'] ?? false),
            ]);
            foreach ($dateColumns as $code) {
                if (! empty($row[$code])) {
                    ShippingEvent::query()->updateOrCreate(
                        ['lot_id' => $lotId, 'code' => $code],
                        ['occurred_at' => $row[$code]],
                    );
                }
            }
        }
        foreach ($this->source->rows('image_information') as $row) {
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            if (! $lotId) {
                continue;
            }
            LotImage::query()->create([
                'lot_id' => $lotId,
                'path' => $row['image_path'] ?? $row['image_name'] ?? '',
                'type' => $row['type'] ?? 'auction',
                'is_cover' => (bool) ($row['is_cover'] ?? false),
                'is_selected' => (bool) ($row['is_selected'] ?? false),
            ]);
        }
    }

    private function finance(): void
    {
        foreach ($this->source->rows('fields') as $row) {
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            if (! $lotId && isset($row['invoice_id'])) {
                continue;
            }
            if (! $lotId) {
                continue;
            }
            FinanceLine::query()->create([
                'lot_id' => $lotId,
                'code' => (string) ($row['name'] ?? 'line'),
                'title' => (string) ($row['name'] ?? 'Field'),
                'amount' => $row['value'] ?? 0,
                'is_ag' => (bool) ($row['is_ag'] ?? false),
                'is_paid' => (bool) ($row['is_paid'] ?? false),
                'finance_checked' => (bool) ($row['finance_checked'] ?? false),
                'logist_checked' => (bool) ($row['logist_checked'] ?? false),
                'counterparty_id' => $this->map->get('counterparties', $row['counterparty_id'] ?? null),
            ]);
        }
        foreach ($this->source->rows('invoice_information') as $row) {
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            if (! $lotId) {
                continue;
            }
            $invoice = Invoice::query()->create([
                'lot_id' => $lotId,
                'number' => $row['name'] ?? null,
                'status' => (string) ($row['status'] ?? 'draft'),
                'amount' => $row['total'] ?? $row['price'] ?? 0,
                'total' => $row['total'] ?? 0,
                'lot_price' => $row['lot_price'] ?? 0,
                'delivery_price' => $row['delivery_price'] ?? 0,
                'commission_price' => $row['commission_price'] ?? 0,
                'invoice_type' => $row['invoice_type'] ?? 'payment',
                'language' => $row['language'] ?? 'en',
                'docx_path' => $row['file'] ?? null,
            ]);
            $this->map->remember('invoice_information', $row['id'] ?? null, $invoice->id);
        }
        foreach ($this->source->rows('payment_information') as $row) {
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            if (! $lotId) {
                continue;
            }
            Payment::query()->create([
                'lot_id' => $lotId,
                'amount' => $row['pay_price'] ?? $row['price'] ?? 0,
                'status' => (string) ($row['status'] ?? 'draft'),
                'comment' => $row['comment'] ?? null,
                'type' => 'incoming',
            ]);
        }
    }

    private function containers(): void
    {
        foreach ($this->source->rows('containers') as $row) {
            $model = Container::query()->create([
                'number' => $row['number'] ?? null,
                'status' => 'imported',
                'l_date' => $row['l_date'] ?? null,
                'pod' => $row['pod'] ?? null,
                'consolidation' => (bool) ($row['consolidation'] ?? false),
                'is_full' => (bool) ($row['full'] ?? false),
            ]);
            $this->map->remember('containers', $row['id'] ?? null, $model->id);
        }
        foreach ($this->source->rows('container_general_information') as $row) {
            $containerId = $this->map->get('containers', $row['container_id'] ?? null);
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            if ($containerId && $lotId) {
                DB::table('container_lot')->insertOrIgnore([
                    'container_id' => $containerId,
                    'lot_id' => $lotId,
                ]);
            }
        }
    }

    private function wallets(): void
    {
        foreach ($this->source->rows('virtual_wallets') as $row) {
            $account = LedgerAccount::query()->create([
                'type' => 'virtual',
                'owner_type' => 'counterparty',
                'owner_id' => $this->map->get('counterparties', $row['counterparty_id'] ?? null) ?? 0,
                'title' => $row['name'] ?? 'Virtual',
                'currency' => 'USD',
                'legacy_balance' => $row['sum'] ?? 0,
            ]);
            $this->map->remember('virtual_wallets', $row['id'] ?? null, $account->id);
            if (! empty($row['sum'])) {
                LedgerEntry::query()->create([
                    'batch_id' => (string) Str::uuid(),
                    'account_id' => $account->id,
                    'debit' => max(0, (float) $row['sum']),
                    'credit' => 0,
                    'currency' => 'USD',
                    'memo' => 'legacy virtual_wallets.sum',
                ]);
            }
        }
        foreach (['cash_account_auctions' => 'auction', 'cash_account_carriers' => 'carrier', 'cash_account_counterparties' => 'counterparty'] as $table => $type) {
            foreach ($this->source->rows($table) as $row) {
                LedgerAccount::query()->create([
                    'type' => $type,
                    'owner_type' => $type,
                    'owner_id' => (int) ($row['id'] ?? 0),
                    'title' => $row['name'] ?? $table,
                    'currency' => 'USD',
                    'legacy_balance' => $row['sum'] ?? $row['balance'] ?? 0,
                ]);
            }
        }
    }

    private function chats(): void
    {
        foreach ($this->source->rows('chats') as $row) {
            $lotId = $this->map->get('general_information', $row['general_information_id'] ?? null);
            $chat = Chat::query()->firstOrCreate(
                ['lot_id' => $lotId, 'type' => 'lot'],
                ['title' => $row['title'] ?? $row['name'] ?? 'Chat'],
            );
            $this->map->remember('chats', $row['id'] ?? null, $chat->id);
        }
        foreach ($this->source->rows('chat_messages') as $row) {
            $chatId = $this->map->get('chats', $row['chat_id'] ?? null);
            $userId = $this->map->get('users', $row['user_id'] ?? null);
            if (! $chatId || ! $userId) {
                continue;
            }
            ChatMessage::query()->create([
                'chat_id' => $chatId,
                'user_id' => $userId,
                'body' => (string) ($row['message'] ?? $row['body'] ?? ''),
            ]);
        }
    }

    private function prebid(): void
    {
        foreach ($this->source->rows('pre_bids') as $row) {
            DB::table('crm_pre_bids')->insert([
                'lot' => $row['lot'] ?? '',
                'price' => $row['price'] ?? 0,
                'success_price' => $row['success_price'] ?? 0,
                'auto_name' => $row['auto_name'] ?? null,
                'auction_id' => $this->map->get('auctions', $row['auction_id'] ?? null),
                'status' => 'imported',
                'comment' => $row['comment'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function importNamed(string $table, string $mapAs): void
    {
        foreach ($this->source->rows($table) as $row) {
            $code = (string) ($row['code'] ?? $table.'-'.($row['id'] ?? ''));
            $title = (string) ($row['title'] ?? $row['name'] ?? $code);
            $attrs = ['created_at' => now(), 'updated_at' => now()];
            if (Schema::hasColumn($table, 'code')) {
                $attrs['code'] = $code;
            }
            if (Schema::hasColumn($table, 'title')) {
                $attrs['title'] = $title;
            } elseif (Schema::hasColumn($table, 'name')) {
                $attrs['name'] = $title;
            }
            if ($table === 'transport_sizes' && Schema::hasColumn($table, 'autos_count')) {
                $attrs['autos_count'] = 1;
            }
            $lookup = Schema::hasColumn($table, 'code') ? ['code' => $code] : ['title' => $title];
            DB::table($table)->updateOrInsert($lookup, $attrs);
            $id = DB::table($table)->where($lookup)->value('id');
            $this->map->remember($mapAs, $row['id'] ?? null, $id ? (int) $id : null);
        }
    }

    private function statusCode(string $mapAs, mixed $oldId, string $fallback): string
    {
        $id = $this->map->get($mapAs, $oldId);
        if (! $id) {
            return $fallback;
        }
        $table = $mapAs;
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'code')) {
            return $fallback;
        }

        return (string) (DB::table($table)->where('id', $id)->value('code') ?? $fallback);
    }
}
