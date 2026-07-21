<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Device;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Device> */
final class DeviceFactory extends Factory
{
    protected $model = Device::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->word().' gateway',
            'manufacturer' => 'Samsung',
            'model' => 'SM-A145F',
            'android_version' => '14',
            'app_version' => '1.0.0',
            'battery_percent' => 80,
            'connection_type' => 'wifi',
            'last_seen_at' => now(),
        ];
    }
}
