<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class Suppression extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = ['organization_id', 'phone', 'reason', 'source', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
