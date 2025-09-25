<?php

namespace Ometra\AetherClient;

use Illuminate\Console\Scheduling\Schedule;

Schedule::command('aether:report Heartbeat')
    ->everyFiveMinutes();
