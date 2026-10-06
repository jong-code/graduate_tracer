<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * Loads public/data/occupations.json (the same file the Section D
 * "Present Occupation" combobox fetches client-side) and exposes an
 * id-keyed lookup plus the present_employment_status -> dataset
 * employment-type-id mapping, so GraduateTracerController::store() never
 * has to trust the occupation id/name or the employment-type match the
 * browser sent.
 */
class OccupationCatalog
{
    /** @var array<string, array{id: string, name: string, category_id: ?string, category_name: ?string, employment_type_availability: array<string, string>}>|null */
    private static ?array $cache = null;

    /**
     * Maps this app's `present_employment_status` enum values to the
     * dataset's own employment-type ids - keep in sync with the
     * EMPLOYMENT_TYPE_MAP object in tracer/dashboard.blade.php.
     */
    public const EMPLOYMENT_TYPE_MAP = [
        'regular_permanent' => 'EMP-REGULAR_PERMANENT',
        'temporary' => 'EMP-TEMPORARY',
        'casual' => 'EMP-CASUAL',
        'contractual' => 'EMP-CONTRACTUAL',
        'self_employed' => 'EMP-SELF_EMPLOYED',
    ];

    /**
     * @return array<string, array{id: string, name: string, category_id: ?string, category_name: ?string, employment_type_availability: array<string, string>}>
     */
    public static function lookup(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = public_path('data/occupations.json');
        $decoded = json_decode(File::exists($path) ? File::get($path) : '{}', true) ?: [];

        $lookup = [];
        foreach ($decoded['categories'] ?? [] as $category) {
            foreach ($category['occupations'] ?? [] as $occupation) {
                if (empty($occupation['id']) || empty($occupation['name'])) {
                    continue;
                }

                $lookup[$occupation['id']] = [
                    'id' => $occupation['id'],
                    'name' => $occupation['name'],
                    'category_id' => $category['id'] ?? null,
                    'category_name' => $category['name'] ?? null,
                    'employment_type_availability' => $occupation['employment_type_availability'] ?? [],
                ];
            }
        }

        return self::$cache = $lookup;
    }

    public static function find(string $occupationId): ?array
    {
        return self::lookup()[$occupationId] ?? null;
    }

    public static function findByName(string $name): ?array
    {
        foreach (self::lookup() as $occupation) {
            if (strcasecmp($occupation['name'], $name) === 0) {
                return $occupation;
            }
        }

        return null;
    }

    public static function employmentTypeIdFor(?string $presentEmploymentStatus): ?string
    {
        return self::EMPLOYMENT_TYPE_MAP[$presentEmploymentStatus] ?? null;
    }

    /**
     * Mirrors the dataset's own filtering_config (excluded_level:
     * "not_applicable"): common/possible/uncommon are all treated as
     * compatible - only "not_applicable", or an employment type the
     * occupation doesn't rate at all, is rejected.
     */
    public static function isCompatible(array $occupation, string $employmentTypeId): bool
    {
        $rating = $occupation['employment_type_availability'][$employmentTypeId] ?? null;

        return $rating !== null && $rating !== 'not_applicable';
    }
}
