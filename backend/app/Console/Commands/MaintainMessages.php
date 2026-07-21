<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Messaging\MessageMaintenanceService;
use Illuminate\Console\Command;

final class MaintainMessages extends Command
{
    protected $signature = 'messages:maintain';

    protected $description = 'Promote schedules, expire messages, and safely recover abandoned leases';

    public function handle(MessageMaintenanceService $maintenance): int
    {
        $result = $maintenance->run();
        $this->info(sprintf(
            'Promoted %d, expired %d, recovered %d, uncertain %d.',
            $result['promoted'], $result['expired'], $result['recovered'], $result['uncertain'],
        ));

        return self::SUCCESS;
    }
}
