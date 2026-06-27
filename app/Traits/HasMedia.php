<?php

namespace App\Traits;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    public function mediaExists(string $path, string $prefix): bool
    {
        $path = trim($path);

        if ($path === '' || ! $this->pathMatchesPrefix($path, $prefix)) {
            return false;
        }

        return Storage::disk(config('services.media.disk'))->exists($path);
    }

    public function temporaryMediaUrl(string $path, ?DateTimeInterface $expiration = null): string
    {
        return Storage::disk(config('services.media.disk'))->temporaryUrl(
            trim($path),
            $expiration ?? now()->addMinutes((int) config('services.media.url_ttl')),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $media
     * @return array{message: string, errors: array<string, array<int, string>>}|null
     */
    public function validateMedia(array $media, string $prefix): ?array
    {
        $errors = [];

        foreach ($media as $index => $item) {
            $path = $item['path'] ?? null;

            if (! is_string($path) || ! $this->mediaExists($path, $prefix)) {
                $errors["media.{$index}.path"] = ['The selected media file does not exist.'];
            }
        }

        if ($errors === []) {
            return null;
        }

        return [
            'message' => 'One or more media files could not be found.',
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $media
     * @return array<int, array{type: string, url: string}>|null
     */
    public function mediaWithUrls(?array $media): ?array
    {
        if ($media === null || $media === []) {
            return null;
        }

        return array_map(fn (array $item): array => [
            'type' => $item['type'],
            'url' => $this->temporaryMediaUrl($item['path']),
        ], $media);
    }

    public function validateAssets(Request $request, Model $model): JsonResponse|null
    {
        if ($request->has('media') && $error = $model->validateMedia($request->array('media'), $model::MEDIA_STORAGE_PREFIX)) {
            return response()->json($error, 422);
        }

        if ($request->has('cover_image') && ! $model->mediaExists($request->string('cover_image'), $model::MEDIA_STORAGE_PREFIX)) {
            return response()->json([
                'cover_image' => 'The selected cover image does not exist.',
            ], 422);
        }

        if ($request->has('file') && ! $model->mediaExists($request->string('file'), $model::MEDIA_STORAGE_PREFIX)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['file' => ['The selected audio file does not exist.']],
            ], 422);
        }

        return null;
    }

    protected function pathMatchesPrefix(string $path, string $prefix): bool
    {
        $prefix = trim($prefix, '/');

        if ($prefix === '') {
            return false;
        }

        return str_starts_with($path, $prefix.'/') || $path === $prefix;
    }
}
