<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('aec_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code', 32)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 32)->default('auction');
            $table->timestamps();
        });

        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('sea_lines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('delivery_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('destination_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('sea_line_id')->nullable()->constrained('sea_lines')->nullOnDelete();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('transport_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('aec_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('transport_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('transport_brands')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedBigInteger('aec_id')->nullable()->index();
            $table->timestamps();
            $table->unique(['brand_id', 'name']);
        });

        Schema::create('transport_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->decimal('rate_to_usd', 18, 6)->default(1);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
        Schema::dropIfExists('transport_types');
        Schema::dropIfExists('transport_models');
        Schema::dropIfExists('transport_brands');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('delivery_types');
        Schema::dropIfExists('sea_lines');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('locations');
        Schema::dropIfExists('ports');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
