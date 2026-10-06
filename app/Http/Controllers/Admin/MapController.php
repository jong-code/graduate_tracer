<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\SchoolYear;
use Illuminate\Http\Request;

/**
 * Plots every survey submission's recorded location (captured via the
 * Geolocation API alongside the Disclosure & Consent checkbox in Section D)
 * on a Leaflet/OpenStreetMap map, filterable by school year. Clicking a
 * marker opens that graduate's read-only survey preview - the same
 * low-friction preview already used from Admin > Survey Templates - in a
 * new tab.
 */
class MapController extends Controller
{
    public function index()
    {
        return view('admin.map.index', [
            'schoolYears' => SchoolYear::orderByDesc('label')->get(),
        ]);
    }

    public function locations(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => ['nullable', 'exists:school_years,id'],
        ]);

        $query = Location::query()
            ->with([
                'employmentData.survey.generalInformation',
                'employmentData.survey.academicProgram',
                'employmentData.survey.schoolYear',
            ])
            ->whereHas('employmentData.survey', function ($q) use ($validated) {
                $q->forGraduateUsers()->whereNotNull('submitted_at');
                if (! empty($validated['school_year_id'])) {
                    $q->where('school_year_id', $validated['school_year_id']);
                }
            });

        $points = $query->get()->map(function (Location $location) {
            $survey = $location->employmentData->survey;

            return [
                'survey_id' => $survey->id,
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'name' => $survey->generalInformation?->name ?? 'Graduate #' . $survey->id,
                'program' => $survey->academicProgram?->name ?? '—',
                'school_year' => $survey->schoolYear?->label ?? '—',
                'preview_url' => route('admin.templates.survey-preview', $survey),
            ];
        })->values();

        return response()->json($points);
    }
}
