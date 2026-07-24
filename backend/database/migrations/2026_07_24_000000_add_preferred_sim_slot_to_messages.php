<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            // Compose-time SIM preference (0 or 1). device_sim_slot_id records the SIM
            // actually used once a device leases the message; this is the request.
            $table->unsignedTinyInteger('preferred_sim_slot')->nullable()->after('device_sim_slot_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table): void {
            $table->dropColumn('preferred_sim_slot');
        });
    }
};
