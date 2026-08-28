<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_colors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->nullable()->index();
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('vehicle_damages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->nullable()->index();
            $table->string('title');
            $table->timestamps();
        });
        Schema::create('doc_fees', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('amount', 14, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('transportation_agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 64)->nullable()->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('shipping_status_triggers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_status_id')->nullable()->constrained('status_shippings')->nullOnDelete();
            $table->string('event_code', 64)->index();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
        Schema::table('lot_notes', function (Blueprint $table) {
            $table->date('noted_on')->nullable();
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->nullOnDelete();
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties')->nullOnDelete();
            $table->string('lot_label')->nullable();
        });
        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('doc_fee_id')->nullable()->constrained('doc_fees')->nullOnDelete();
            $table->foreignId('transportation_agent_id')->nullable()->constrained('transportation_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lot_vehicles', function (Blueprint $table) {
            //
        });
        Schema::table('lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('doc_fee_id');
            $table->dropConstrainedForeignId('transportation_agent_id');
        });
        Schema::table('lot_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credential_id');
            $table->dropConstrainedForeignId('counterparty_id');
            $table->dropColumn(['noted_on', 'lot_label']);
        });
        Schema::dropIfExists('shipping_status_triggers');
        Schema::dropIfExists('transportation_agents');
        Schema::dropIfExists('doc_fees');
        Schema::dropIfExists('vehicle_damages');
        Schema::dropIfExists('vehicle_colors');
    }
};
