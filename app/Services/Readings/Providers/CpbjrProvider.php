<?php

namespace App\Services\Readings\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CpbjrProvider extends Provider
{
    public function getDailyReadings(Carbon $date): array
    {
        return $this->fetch("readings/{$date->year}/{$date->format('m-d')}.json", $date);
    }

    public function getCelebration(Carbon $date): array
    {
        return $this->fetch("liturgical-calendar/{$date->year}/{$date->format('m-d')}.json", $date);
    }

    protected function fetch(string $path, Carbon $date): array
    {
        $baseUrl = rtrim(config('services.readings.base_url'), '/');
        $response = Http::get("{$baseUrl}/{$path}");

        if ($response->notFound()) {
            throw new RuntimeException("Readings data not found for date [{$date->toDateString()}].");
        }

        $response->throw(); 

        return $response->json();
    }
}
