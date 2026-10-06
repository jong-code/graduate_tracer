<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Loads public/data/major_lines_of_business.json (the same file the
 * Section D "Major line of business of the company" combobox fetches
 * client-side) and exposes an id-keyed lookup, so the server never has
 * to trust the business line id/name the browser sent.
 */
class BusinessLineCatalog
{
    /** @var array<string, array{id: string, name: string, category_id: ?string, category_name: ?string}>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, array{id: string, name: string, category_id: ?string, category_name: ?string}>
     */
    public static function lookup(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = public_path('data/major_lines_of_business.json');
        $decoded = json_decode(File::exists($path) ? File::get($path) : '{}', true) ?: [];

        $lookup = [];
        foreach ($decoded['categories'] ?? [] as $category) {
            foreach ($category['business_lines'] ?? [] as $line) {
                if (empty($line['id']) || empty($line['name'])) {
                    continue;
                }

                $lookup[$line['id']] = [
                    'id' => $line['id'],
                    'name' => $line['name'],
                    'category_id' => $category['id'] ?? null,
                    'category_name' => $category['name'] ?? null,
                ];
            }
        }

        return self::$cache = $lookup;
    }

    public static function find(string $id): ?array
    {
        return self::lookup()[$id] ?? null;
    }

    public static function findByName(string $name): ?array
    {
        foreach (self::lookup() as $line) {
            if (strcasecmp($line['name'], $name) === 0) {
                return $line;
            }
        }

        return null;
    }
}
