<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->string('vin', 32)->index();
            $table->string('lot_number', 64)->nullable()->index();
            $table->foreignId('auction_id')->nullable()->constrained('auctions')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('transport_brands')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->constrained('transport_models')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('buyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->year('year')->nullable();
            $table->string('status_order', 64)->default('new')->index();
            $table->string('status_shipping', 64)->default('pending')->index();
            $table->string('status_finance', 64)->default('pending')->index();
            $table->boolean('is_auction_participant')->default(false);
            $table->boolean('archived')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lot_parties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('role', 32);
            $table->unsignedBigInteger('party_id')->nullable();
            $table->string('party_type', 64)->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->index(['party_type', 'party_id']);
        });

        Schema::create('lot_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->unique()->constrained('lots')->cascadeOnDelete();
            $table->decimal('hammer_price', 14, 2)->default(0);
            $table->decimal('fees', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->timestamps();
        });

        Schema::create('lot_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('path');
            $table->string('type', 32)->default('auction');
            $table->boolean('is_cover')->default(false);
            $table->boolean('is_selected')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('lot_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->default('lot');
            $table->foreignId('lot_id')->nullable()->constrained('lots')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_read_at')->nullable();
            $table->unique(['chat_id', 'user_id']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('chats')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_participants');
        Schema::dropIfExists('chats');
        Schema::dropIfExists('lot_notes');
        Schema::dropIfExists('lot_images');
        Schema::dropIfExists('lot_pricing');
        Schema::dropIfExists('lot_parties');
        Schema::dropIfExists('lots');
    }
};
