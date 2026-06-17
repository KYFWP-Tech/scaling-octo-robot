<?php

namespace App\Services\Bible;

use App\Services\Bible\Providers\Provider;
use InvalidArgumentException;

class Bible
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
            throw new InvalidArgumentException("Bible provider [{$provider}] does not exist.");
        }

        $instance = new $class;

        if (! $instance instanceof Provider) {
            throw new InvalidArgumentException("Bible provider [{$provider}] must extend ".Provider::class.'.');
        }

        return $instance;
    }

    public function getPassage(string $reference): array
    {
        return $this->providerInstance->getPassage($reference); 
    }
}
