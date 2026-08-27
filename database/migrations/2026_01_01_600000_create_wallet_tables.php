<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('owner_type', 64)->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('currency', 8)->default('USD');
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['owner_type', 'owner_id']);
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->foreignId('account_id')->constrained('ledger_accounts')->cascadeOnDelete();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('memo')->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('erip_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->string('external_id')->nullable()->index();
            $table->string('status', 32)->default('pending');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('BYN');
            $table->jsonb('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erip_transactions');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_accounts');
    }
};
