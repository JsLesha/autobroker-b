<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prebid_auctions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prebid_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prebid_auction_id')->nullable()->constrained('prebid_auctions')->nullOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('moderation')->index();
            $table->decimal('start_price', 14, 2)->default(0);
            $table->decimal('buy_now_price', 14, 2)->nullable();
            $table->decimal('current_price', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('prebid_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('prebid_listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->boolean('is_buy_now')->default(false);
            $table->timestamps();
        });

        Schema::create('prebid_favorites', function (Blueprint $table) {
            $table->foreignId('listing_id')->constrained('prebid_listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['listing_id', 'user_id']);
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 64)->index();
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32)->index();
            $table->string('direction', 16);
            $table->string('status', 32);
            $table->jsonb('payload')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('prebid_favorites');
        Schema::dropIfExists('prebid_bids');
        Schema::dropIfExists('prebid_listings');
        Schema::dropIfExists('prebid_auctions');
    }
};
