<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 32)->index();
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('rate_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_card_id')->constrained('rate_cards')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('layer', 16)->default('base');
            $table->timestamp('effective_from')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['rate_card_id', 'version', 'layer']);
        });

        Schema::create('rate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_version_id')->constrained('rate_versions')->cascadeOnDelete();
            $table->json('dimensions');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 8)->default('USD');
            $table->timestamps();
        });

        Schema::create('package_services', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('amount', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_services');
        Schema::dropIfExists('rate_items');
        Schema::dropIfExists('rate_versions');
        Schema::dropIfExists('rate_cards');
    }
};
