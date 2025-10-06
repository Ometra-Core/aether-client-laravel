<?php

namespace Ometra\AetherClient;

use Illuminate\Support\Facades\Schedule;

Schedule::command('aether:report heartbeat')->everyFiveMinutes();
