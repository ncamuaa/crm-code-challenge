<?php

namespace App\Console;

use App\Console\Commands\SetupSearchIndex;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        SetupSearchIndex::class,
    ];
}
