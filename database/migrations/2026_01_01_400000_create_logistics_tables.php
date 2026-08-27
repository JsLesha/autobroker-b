<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('lots')->cascadeOnDelete();
            $table->string('status', 64)->default('pending')->index();
            $table->foreignId('origin_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('destination_port_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->timestamp('ready_to_load_at')->nullable();
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('sailed_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('timeline')->nullable();
            $table->timestamps();
        });

        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 64)->nullable()->index();
            $table->string('status', 32)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('loaded_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('container_lot', function (Blueprint $table) {
            $table->foreignId('container_id')->constrained('containers')->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->primary(['container_id', 'lot_id']);
        });

        Schema::create('local_hauls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('status', 32)->default('application')->index();
            $table->string('from_place')->nullable();
            $table->string('to_place')->nullable();
            $table->timestamp('transit_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('local_hauls');
        Schema::dropIfExists('container_lot');
        Schema::dropIfExists('containers');
        Schema::dropIfExists('shipping_records');
    }
};
