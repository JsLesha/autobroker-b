<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparties', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('counterparty_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counterparty_id')->constrained('counterparties')->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('iban')->nullable();
            $table->string('swift')->nullable();
            $table->timestamps();
        });

        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->nullable()->constrained('auctions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('login');
            $table->text('secret');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('lots', function (Blueprint $table) {
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties')->nullOnDelete();
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credential_id');
            $table->dropConstrainedForeignId('counterparty_id');
        });
        Schema::dropIfExists('credentials');
        Schema::dropIfExists('counterparty_banks');
        Schema::dropIfExists('counterparties');
    }
};
