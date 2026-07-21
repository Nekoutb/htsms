<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $device_id
 * @property int $slot_index
 * @property string|null $carrier_name
 * @property string|null $phone_number
 * @property bool $is_active
 */
final class DeviceSimSlot extends Model
{
    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'device_id',
        'slot_index',
        'carrier_name',
        'phone_number',
        'is_active',
    ];

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone_number' => 'encrypted',
            'slot_index' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
