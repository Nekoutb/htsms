<?php

use App\Models\User;

User::query()->where('email', 'demo@htsms.test')->update(['email_verified_at' => now()]);
echo 'verified='.User::query()->where('email', 'demo@htsms.test')->whereNotNull('email_verified_at')->count()."\n";
