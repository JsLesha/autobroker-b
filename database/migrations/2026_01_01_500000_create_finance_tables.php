<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('code', 64);
            $table->string('title');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->index(['lot_id', 'code']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('number')->nullable();
            $table->string('status', 32)->default('draft');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('currency', 8)->default('USD');
            $table->string('docx_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->char('preview_token', 64)->nullable()->unique();
            $table->timestamp('preview_expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('type', 32)->default('incoming');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('USD');
            $table->string('method', 32)->nullable();
            $table->string('document_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('lot_drops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_drops');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('finance_lines');
    }
};
