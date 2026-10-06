<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Loads public/data/college_skills_acquired.json (the same file the
 * Section D "If self-employed, what skills..." combobox fetches
 * client-side for its suggestions) and exposes it as a flat, keyed
 * lookup so the server never has to trust a skill id/name submitted by
 * the browser - see GraduateTracerController::store()'s
 * self_employed_skills validation, which checks every submitted id
 * against lookup() and replaces the submitted name with the
 * authoritative one from here before saving.
 */
class CollegeSkillsCatalog
{
    /** @var array<string, array{id: string, name: string, category_id: string, category_name: string}>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, array{id: string, name: string, category_id: string, category_name: string}>
     */
    public static function lookup(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = public_path('data/college_skills_acquired.json');
        $decoded = json_decode(File::exists($path) ? File::get($path) : '{}', true) ?: [];

        $lookup = [];
        foreach ($decoded['categories'] ?? [] as $category) {
            foreach ($category['skills'] ?? [] as $skill) {
                if (empty($skill['id']) || empty($skill['name'])) {
                    continue;
                }

                $lookup[$skill['id']] = [
                    'id' => $skill['id'],
                    'name' => $skill['name'],
                    'category_id' => $category['id'] ?? null,
                    'category_name' => $category['name'] ?? null,
                ];
            }
        }

        return self::$cache = $lookup;
    }

    public static function find(string $skillId): ?array
    {
        return self::lookup()[$skillId] ?? null;
    }

    /**
     * Reverse lookup by name (case-insensitive) - used when restoring a
     * previously saved survey, since self_employed_skills only stores the
     * skill_name (see the table's actual columns), not its dataset id.
     */
    public static function findByName(string $name): ?array
    {
        foreach (self::lookup() as $skill) {
            if (strcasecmp($skill['name'], $name) === 0) {
                return $skill;
            }
        }

        return null;
    }
}
