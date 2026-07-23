<?php

declare(strict_types=1);

return [
    'plans' => [
        'trial' => ['name' => 'Trial', 'monthly_price_xaf' => 0, 'messages' => 100, 'devices' => 1, 'api_keys' => 2],
        'starter' => ['name' => 'Starter', 'monthly_price_xaf' => 15000, 'messages' => 5000, 'devices' => 2, 'api_keys' => 5],
        'business' => ['name' => 'Business', 'monthly_price_xaf' => 45000, 'messages' => 25000, 'devices' => 10, 'api_keys' => 20],
    ],
    'trial_days' => 14,
    'apk' => [
        'version' => '0.2.0-beta',
        'path' => 'downloads/htsms-gateway-v0.2.0-beta.apk',
        'checksum_path' => 'downloads/htsms-gateway-v0.2.0-beta.apk.sha256',
    ],
];
