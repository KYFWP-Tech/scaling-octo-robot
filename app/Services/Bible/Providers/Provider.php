<?php

namespace App\Services\Bible\Providers;

abstract class Provider
{
    abstract public function getPassage(string $reference): array; 
}
