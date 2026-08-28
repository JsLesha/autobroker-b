<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dictionaries();
        $this->identityExtras();
        $this->lotSlices();
        $this->logisticsExtras();
        $this->financeExtras();
        $this->counterpartyExtras();
        $this->prebidCrm();
        $this->settingsAndVin();
    }

    public function down(): void
    {
        Schema::dropIfExists('vin_check_reports');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('crm_pre_bids');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('image_archive_exports');
        Schema::dropIfExists('shipping_events');
        Schema::dropIfExists('lot_routes');
        Schema::dropIfExists('lot_clients');
        Schema::dropIfExists('lot_vehicles');
        Schema::dropIfExists('counterparty_types');
        Schema::dropIfExists('calculation_systems');
        Schema::dropIfExists('transport_sizes');
        Schema::dropIfExists('transport_run_statuses');
        Schema::dropIfExists('transport_odometer_units');
        Schema::dropIfExists('transport_keys');
        Schema::dropIfExists('transport_highlights');
        Schema::dropIfExists('transport_transmissions');
        Schema::dropIfExists('transport_drives');
        Schema::dropIfExists('transport_fuels');
        Schema::dropIfExists('status_finances');
        Schema::dropIfExists('status_shippings');
        Schema::dropIfExists('status_orders');
    }

    private function dictionaries(): void
    {
        Schema::create('status_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::create('status_shippings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::create('status_finances', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        foreach (['transport_fuels', 'transport_drives', 'transport_transmissions', 'transport_highlights', 'transport_keys', 'transport_odometer_units', 'transport_run_statuses'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->string('code', 64)->nullable()->index();
                $table->string('title');
                $table->timestamps();
            });
        }
        Schema::create('transport_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->nullable()->index();
            $table->string('title');
            $table->unsignedTinyInteger('autos_count')->default(1);
            $table->timestamps();
        });
        Schema::create('calculation_systems', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('counterparty_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->timestamps();
        });
    }

    private function identityExtras(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('group_type_code', 64)->nullable()->index();
            $table->unsignedInteger('sort')->default(0);
        });
        Schema::create('user_permission', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32)->index();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('telegram')->nullable();
            $table->boolean('is_head')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['user_id', 'kind']);
        });
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_prebid')->default(false);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('prebid_role_id')->nullable()->constrained('roles')->nullOnDelete();
        });
    }

    private function lotSlices(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->string('transport_name')->nullable();
            $table->boolean('outside')->default(false);
            $table->boolean('is_unformat_vin')->default(false);
            $table->date('date_buy')->nullable();
            $table->foreignId('status_order_id')->nullable()->constrained('status_orders')->nullOnDelete();
            $table->foreignId('status_shipping_id')->nullable()->constrained('status_shippings')->nullOnDelete();
            $table->foreignId('status_finance_id')->nullable()->constrained('status_finances')->nullOnDelete();
            $table->foreignId('buyer_role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->index('buyer_user_id');
        });

        Schema::create('lot_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('lots')->cascadeOnDelete();
            $table->foreignId('size_id')->nullable()->constrained('transport_sizes')->nullOnDelete();
            $table->foreignId('fuel_id')->nullable()->constrained('transport_fuels')->nullOnDelete();
            $table->foreignId('drive_id')->nullable()->constrained('transport_drives')->nullOnDelete();
            $table->foreignId('transmission_id')->nullable()->constrained('transport_transmissions')->nullOnDelete();
            $table->foreignId('highlight_id')->nullable()->constrained('transport_highlights')->nullOnDelete();
            $table->foreignId('keys_id')->nullable()->constrained('transport_keys')->nullOnDelete();
            $table->foreignId('odometer_unit_id')->nullable()->constrained('transport_odometer_units')->nullOnDelete();
            $table->foreignId('run_status_id')->nullable()->constrained('transport_run_statuses')->nullOnDelete();
            $table->string('engine')->nullable();
            $table->unsignedInteger('engine_hp')->nullable();
            $table->unsignedInteger('cylinders')->nullable();
            $table->string('odometer')->nullable();
            $table->string('equipment')->nullable();
            $table->string('body_type')->nullable();
            $table->string('complectation')->nullable();
            $table->boolean('electric')->default(false);
            $table->unsignedBigInteger('color_id')->nullable();
            $table->timestamps();
        });

        Schema::create('lot_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('lots')->cascadeOnDelete();
            $table->string('full_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('first_middle_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('messenger')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        Schema::create('lot_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('lots')->cascadeOnDelete();
            $table->foreignId('city_from_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('city_to_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('port_to_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('location_from_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('location_to_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('delivery_type_id')->nullable()->constrained('delivery_types')->nullOnDelete();
            $table->string('route_label')->nullable();
            $table->unsignedBigInteger('package_service_id')->nullable();
            $table->unsignedBigInteger('transportation_agent_id')->nullable();
            $table->unsignedBigInteger('carrier_id')->nullable();
            $table->timestamps();
        });

        Schema::table('lot_pricing', function (Blueprint $table) {
            $table->decimal('start_price', 14, 2)->default(0);
            $table->decimal('step_price', 14, 2)->default(0);
            $table->decimal('cost_price', 14, 2)->nullable();
            $table->decimal('min_price', 14, 2)->nullable();
            $table->decimal('now_price', 14, 2)->nullable();
            $table->decimal('usa_price', 14, 2)->default(0);
            $table->decimal('ag_price', 14, 2)->default(0);
            $table->foreignId('calculation_system_id')->nullable()->constrained('calculation_systems')->nullOnDelete();
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('reply_to_message_id')->nullable()->constrained('chat_messages')->nullOnDelete();
        });
    }

    private function logisticsExtras(): void
    {
        Schema::table('shipping_records', function (Blueprint $table) {
            $table->string('container_number', 16)->nullable()->index();
            $table->foreignId('sea_line_id')->nullable()->constrained('sea_lines')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('status_shippings')->nullOnDelete();
            $table->boolean('documents_received')->default(false);
            $table->boolean('lot_accepted_by_client')->default(false);
        });
        Schema::create('shipping_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('code', 64)->index();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
            $table->unique(['lot_id', 'code']);
        });
        Schema::table('containers', function (Blueprint $table) {
            $table->foreignId('sea_line_id')->nullable()->constrained('sea_lines')->nullOnDelete();
            $table->foreignId('port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('port_from_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('shipper_id')->nullable()->constrained('counterparties')->nullOnDelete();
            $table->boolean('consolidation')->default(false);
            $table->boolean('is_full')->default(false);
            $table->timestamp('l_date')->nullable();
            $table->timestamp('pod')->nullable();
        });
        Schema::create('image_archive_exports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('image_type', 64);
            $table->string('status', 32)->default('pending')->index();
            $table->string('storage_path', 512)->nullable();
            $table->timestamps();
        });
    }

    private function financeExtras(): void
    {
        Schema::table('finance_lines', function (Blueprint $table) {
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->boolean('is_block')->default(false);
            $table->boolean('is_ag')->default(false);
            $table->boolean('logist_checked')->default(false);
            $table->boolean('logist_close')->default(false);
            $table->boolean('finance_checked')->default(false);
            $table->boolean('finance_close')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('invoice_type', 32)->default('payment');
            $table->string('language', 8)->default('en');
            $table->decimal('lot_price', 14, 2)->default(0);
            $table->decimal('delivery_price', 14, 2)->default(0);
            $table->decimal('commission_price', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->date('invoice_date')->nullable();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 32)->default('draft')->index();
            $table->text('comment')->nullable();
            $table->foreignId('finance_line_id')->nullable()->constrained('finance_lines')->nullOnDelete();
            $table->foreignId('erip_transaction_id')->nullable()->constrained('erip_transactions')->nullOnDelete();
        });
        Schema::table('ledger_accounts', function (Blueprint $table) {
            $table->decimal('legacy_balance', 14, 2)->default(0);
        });
    }

    private function counterpartyExtras(): void
    {
        Schema::table('counterparties', function (Blueprint $table) {
            $table->string('code', 64)->nullable()->index();
            $table->foreignId('counterparty_type_id')->nullable()->constrained('counterparty_types')->nullOnDelete();
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('messenger')->nullable();
            $table->decimal('commission', 14, 2)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('hide_in_lot')->default(false);
            $table->boolean('hide_in_calculator')->default(false);
            $table->boolean('is_sea_carrier')->default(false);
            $table->jsonb('payment_types')->nullable();
        });
        Schema::table('counterparty_banks', function (Blueprint $table) {
            $table->string('payment_scope', 16)->nullable();
            $table->string('account')->nullable();
            $table->string('routing')->nullable();
            $table->string('bin')->nullable();
            $table->string('address')->nullable();
        });
        Schema::table('credentials', function (Blueprint $table) {
            $table->string('buyer_code')->nullable();
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties')->nullOnDelete();
        });
        Schema::table('auctions', function (Blueprint $table) {
            $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort')->default(1);
            $table->softDeletes();
        });
        Schema::table('rate_items', function (Blueprint $table) {
            $table->string('legacy_table', 64)->nullable()->index();
            $table->unsignedBigInteger('legacy_id')->nullable();
        });
    }

    private function prebidCrm(): void
    {
        Schema::create('crm_pre_bids', function (Blueprint $table) {
            $table->id();
            $table->string('lot')->index();
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('success_price', 14, 2)->default(0);
            $table->string('auto_name')->nullable();
            $table->foreignId('auction_id')->nullable()->constrained('auctions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('new')->index();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::table('prebid_listings', function (Blueprint $table) {
            $table->string('vin', 32)->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable();
            $table->jsonb('characteristics')->nullable();
        });
    }

    private function settingsAndVin(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique();
            $table->jsonb('value')->nullable();
            $table->timestamps();
        });
        Schema::create('vin_check_reports', function (Blueprint $table) {
            $table->id();
            $table->string('vin', 17)->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->jsonb('info')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }
};
