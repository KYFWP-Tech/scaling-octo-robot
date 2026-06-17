<?php

namespace App\Services\Bible\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BibleApiProvider extends Provider
{
    public function getPassage(string $reference): array
    {
        $baseUrl = rtrim(config('services.bible.base_url'), '/');
        $translation = config('services.bible.translation');
        $encodedReference = str_replace(' ', '+', $this->normalizeReference($reference));

        $response = Http::get("{$baseUrl}/{$encodedReference}", [
            'translation' => $translation, 
        ]);

        if ($response->notFound()) {
            throw new RuntimeException("Bible passage not found for reference [{$reference}].");
        }

        $response->throw();

        $data = $response->json();

        return [
            'reference' => $data['reference'],
            'text' => $data['text'],
            'verses' => $data['verses'],
        ];
    }

    protected function normalizeReference(string $reference): string
    {
        return trim($reference);
    }
}
