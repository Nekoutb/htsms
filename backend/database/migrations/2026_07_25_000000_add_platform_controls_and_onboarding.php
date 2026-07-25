<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('onboarded_by_user_id')->nullable()->after('is_platform_admin')
                ->constrained('users')->nullOnDelete();
        });
        Schema::table('organizations', function (Blueprint $table): void {
            $table->boolean('inbound_enabled')->default(true)->after('locale');
            $table->boolean('outbound_enabled')->default(true)->after('inbound_enabled');
        });

        DB::table('subscriptions')->where('plan', 'trial')->update([
            'plan' => 'free',
            'status' => 'active',
            'trial_ends_at' => null,
        ]);
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropColumn(['inbound_enabled', 'outbound_enabled']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('onboarded_by_user_id');
        });
    }
};
