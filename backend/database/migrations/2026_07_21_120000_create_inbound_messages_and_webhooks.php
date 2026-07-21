<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('device_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('device_sim_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_event_id', 100);
            $table->string('sender', 32);
            $table->string('recipient', 32)->nullable();
            $table->text('body');
            $table->timestamp('received_at');
            $table->timestamps();
            $table->unique(['device_id', 'device_event_id']);
            $table->index(['organization_id', 'received_at']);
        });

        Schema::create('webhook_endpoints', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('url', 2048);
            $table->text('signing_secret');
            $table->string('secret_prefix', 12);
            $table->json('events');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('consecutive_failures')->default(0);
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();
            $table->index(['organization_id', 'is_active']);
        });

        Schema::create('webhook_deliveries', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('webhook_endpoint_id')->constrained()->cascadeOnDelete();
            $table->uuid('event_id');
            $table->string('event_type', 80);
            $table->json('payload');
            $table->string('status', 24)->default('pending');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('response_excerpt')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['webhook_endpoint_id', 'event_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhook_endpoints');
        Schema::dropIfExists('inbound_messages');
    }
};
