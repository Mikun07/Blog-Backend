<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BlogImageService
{
    private const DISK = 'public';
    private const DIRECTORY = 'blog-images';

    public function store(UploadedFile $image): string
    {
        $path = $image->store(self::DIRECTORY, self::DISK);

        if (! is_string($path)) {
            throw new RuntimeException('Unable to store the blog cover image.');
        }

        return Storage::disk(self::DISK)->url($path);
    }

    public function replace(UploadedFile $image, ?string $currentUrl): string
    {
        $this->delete($currentUrl);

        return $this->store($image);
    }

    public function delete(?string $url): void
    {
        $path = $this->storagePathFromUrl($url);

        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    private function storagePathFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        $prefix = '/storage/';
        $prefixPosition = strpos($path, $prefix);

        if ($prefixPosition === false) {
            return null;
        }

        $storagePath = ltrim(substr($path, $prefixPosition + strlen($prefix)), '/');

        return str_starts_with($storagePath, self::DIRECTORY . '/')
            ? $storagePath
            : null;
    }
}
