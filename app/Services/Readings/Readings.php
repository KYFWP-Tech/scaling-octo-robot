<?php

namespace App\Services\Readings;

use App\Services\Readings\Providers\Provider;
use Carbon\Carbon;
use InvalidArgumentException;

class Readings
{
    protected Provider $providerInstance;

    public function __construct(string $provider)
    {
        $this->providerInstance = $this->createProvider($provider);
    }

    protected function createProvider(string $provider): Provider
    {
        $class = sprintf('%s\\Providers\\%sProvider', __NAMESPACE__, $provider);

        if (! class_exists($class)) {
            throw new InvalidArgumentException("Readings provider [{$provider}] does not exist.");
        }

        $instance = new $class;

        if (! $instance instanceof Provider) {
            throw new InvalidArgumentException("Readings provider [{$provider}] must extend ".Provider::class.'.');
        }

        return $instance;
    }

    public function getDailyReadings(Carbon $date): array
    {
        return $this->providerInstance->getDailyReadings($date);
    }

    public function getCelebration(Carbon $date): array
    {
        return $this->providerInstance->getCelebration($date);
    }
}
