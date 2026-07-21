<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 32);
            $table->string('name', 160)->nullable();
            $table->json('attributes')->nullable();
            $table->string('consent_status', 24)->default('unknown');
            $table->string('consent_source', 120)->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('opted_out_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'phone']);
            $table->index(['organization_id', 'consent_status']);
        });

        Schema::create('suppressions', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 32);
            $table->string('reason', 80);
            $table->string('source', 80);
            $table->timestamp('created_at');
            $table->unique(['organization_id', 'phone']);
        });

        Schema::create('campaigns', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160);
            $table->text('content');
            $table->string('status', 24)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('launched_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('suppressed_count')->default(0);
            $table->timestamps();
            $table->index(['organization_id', 'status']);
        });

        Schema::create('campaign_recipients', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 32);
            $table->string('status', 24);
            $table->string('reason', 80)->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('suppressions');
        Schema::dropIfExists('contacts');
    }
};
