<?php
declare(strict_types=1);

namespace App\Services\Scrape;

use Illuminate\Support\Facades\Storage;

class Persist
{
    protected static string $prefix = 'pars/';

    public static function isSaved(string $path): bool
    {
        return Storage::exists(self::$prefix . $path);
    }

    public static function load(string $path): array
    {
        try {
            $data = Storage::get(self::$prefix . $path);
            $data = json_decode($data, true);
        } catch (\Throwable) {
            $data = [];
        }

        return $data;
    }

    public static function loadRaw(string $path): mixed
    {
        try {
            $data = Storage::get(self::$prefix . $path);
        } catch (\Throwable) {
            $data = null;
        }

        return $data;
    }

    public static function save(string $path, array $data): void
    {
        Storage::put(self::$prefix . $path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
