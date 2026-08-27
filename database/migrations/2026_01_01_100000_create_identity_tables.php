<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 128)->unique();
            $table->string('title');
            $table->string('group_name', 64)->index();
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('role_id')->constrained('departments')->nullOnDelete();
            $table->string('nickname')->nullable()->after('name');
            $table->boolean('active')->default(true)->after('password');
            $table->boolean('active_prebid')->default(false);
            $table->boolean('is_office_dealer')->default(false);
            $table->unsignedBigInteger('telegram_id')->nullable();
            $table->string('telegram_name')->nullable();
            $table->unsignedBigInteger('bitrix_user_id')->nullable();
            $table->string('public_offer_status', 32)->default('pending');
            $table->timestamp('public_offer_accepted_at')->nullable();
            $table->timestamp('public_offer_declined_at')->nullable();
            $table->timestamp('public_offer_modal_shown_at')->nullable();
            $table->unsignedTinyInteger('public_offer_decline_count')->default(0);
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->boolean('sign_document_buy')->default(false);
            $table->boolean('sign_document_sale')->default(false);
            $table->softDeletes();
        });

        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64);
            $table->string('path');
            $table->string('original_name');
            $table->timestamps();
        });

        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('mode', 32)->default('retail');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sub_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 64);
            $table->string('entity_type', 128)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('legacy_id_map', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 64);
            $table->unsignedBigInteger('old_id');
            $table->unsignedBigInteger('new_id');
            $table->timestamps();
            $table->unique(['entity', 'old_id']);
            $table->index(['entity', 'new_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_id_map');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sub_users');
        Schema::dropIfExists('dealers');
        Schema::dropIfExists('offices');
        Schema::dropIfExists('user_documents');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'nickname', 'active', 'active_prebid', 'is_office_dealer',
                'telegram_id', 'telegram_name', 'bitrix_user_id',
                'public_offer_status', 'public_offer_accepted_at', 'public_offer_declined_at',
                'public_offer_modal_shown_at', 'public_offer_decline_count',
                'first_login_at', 'last_login_at', 'sign_document_buy', 'sign_document_sale',
            ]);
        });
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('departments');
    }
};
