<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('device_sim_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient', 32);
            $table->text('body');
            $table->string('status', 32);
            $table->string('idempotency_key', 100)->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('failure_code', 80)->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'idempotency_key']);
            $table->index(['organization_id', 'status', 'scheduled_at']);
            $table->index(['device_id', 'status']);
        });

        Schema::create('message_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('message_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('source', 32);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            $table->index(['message_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_events');
        Schema::dropIfExists('messages');
    }
};
