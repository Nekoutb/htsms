<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('manufacturer', 80);
            $table->string('model', 120);
            $table->string('android_version', 40);
            $table->string('app_version', 40);
            $table->text('fcm_token')->nullable();
            $table->unsignedTinyInteger('battery_percent')->nullable();
            $table->string('connection_type', 20)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'revoked_at']);
            $table->index('last_seen_at');
        });

        Schema::create('device_credentials', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('device_id')->constrained()->cascadeOnDelete();
            $table->string('prefix', 12)->unique();
            $table->char('secret_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_sim_slots', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_index');
            $table->string('carrier_name', 120)->nullable();
            $table->text('phone_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['device_id', 'slot_index']);
        });

        Schema::create('device_pairing_challenges', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_pairing_challenges');
        Schema::dropIfExists('device_sim_slots');
        Schema::dropIfExists('device_credentials');
        Schema::dropIfExists('devices');
    }
};
