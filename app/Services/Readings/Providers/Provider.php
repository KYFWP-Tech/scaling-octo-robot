<?php

namespace App\Services\Readings\Providers;

use Carbon\Carbon;

abstract class Provider
{
    abstract public function getDailyReadings(Carbon $date): array;

    abstract public function getCelebration(Carbon $date): array;
}
