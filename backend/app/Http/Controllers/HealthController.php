<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('select 1');
            Cache::put('health:ready', 'ok', 10);
            if (Cache::get('health:ready') !== 'ok') {
                throw new \RuntimeException('Cache check failed.');
            }

            return response()->json(['status' => 'ready']);
        } catch (Throwable) {
            return response()->json(['status' => 'unavailable'], 503);
        }
    }
}
