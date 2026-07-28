<?php

namespace App\Services;

use App\Models\GraduateTracerSurvey;

/**
 * Converts a survey's normalized rows into the exact tag shape the GTS
 * docxtemplater template (storage/app/templates/gts_template.docx) expects:
 * flat scalars, {#loop}/{/loop} arrays, and a `check` object of booleans
 * for every [/ ] / [ ] checkbox in the form.
 *
 * If the template is ever replaced with one that uses different tag
 * names, this is the only file that needs to change to match it.
 */
class GtsDocxDataMapper
{
    /**
     * Legacy GTS region scheme (pre-MIMAROPA-split / pre-BARMM-rename) used
     * by the template's is_region_1..12 / is_ncr / is_car / is_armm /
     * is_caraga tags, keyed by the exact region name stored in
     * general_information.region_of_origin (see public/data/ph_geo.json).
     * Negros Island Region (NIR) has no equivalent box in this template and
     * is intentionally left unmatched (all region flags false).
     */
    private const REGION_FLAG_MAP = [
        'Region I (Ilocos Region)' => 'is_region_1',
        'Region II (Cagayan Valley)' => 'is_region_2',
        'Region III (Central Luzon)' => 'is_region_3',
        'Region IV-A (CALABARZON)' => 'is_region_4',
        'MIMAROPA Region' => 'is_region_4', // historically "Region IV-B"
        'Region V (Bicol Region)' => 'is_region_5',
        'Region VI (Western Visayas)' => 'is_region_6',
        'Region VII (Central Visayas)' => 'is_region_7',
        'Region VIII (Eastern Visayas)' => 'is_region_8',
        'Region IX (Zamboanga Peninsula)' => 'is_region_9',
        'Region X (Northern Mindanao)' => 'is_region_10',
        'Region XI (Davao Region)' => 'is_region_11',
        'Region XII (SOCCSKSARGEN)' => 'is_region_12',
        'Region XIII (Caraga)' => 'is_caraga',
        'National Capital Region (NCR)' => 'is_ncr',
        'Cordillera Administrative Region (CAR)' => 'is_car',
        'Bangsamoro Autonomous Region In Muslim Mindanao (BARMM)' => 'is_armm',
    ];

    private const COURSE_REASON_SUFFIX = [
        'high_grades_course' => 'high_grades',
        'good_grades_hs' => 'good_grades',
        'parents_influence' => 'parents',
        'peer_influence' => 'peer',
        'role_model' => 'role_model',
        'passion' => 'passion',
        'immediate_employment' => 'immediate_job',
        'prestige' => 'prestige',
        'availability' => 'availability',
        'career_advancement' => 'career',
        'affordable' => 'affordable',
        'attractive_compensation' => 'compensation',
        'abroad' => 'abroad',
        'no_particular_choice' => 'no_choice',
    ];

    private const NOT_EMPLOYED_SUFFIX = [
        'advance_study' => 'advance_study',
        'no_job_opportunity' => 'no_job',
        'family_concern' => 'family_concern',
        'did_not_look' => 'did_not_look',
        'health' => 'health',
        'lack_experience' => 'lack_experience',
    ];

    private const JOB_REASON_PREFIX = ['staying' => 'stay', 'accepting' => 'accept', 'changing' => 'change'];

    private const JOB_REASON_SUFFIX = [
        'salaries_benefits' => 'salary',
        'career_challenge' => 'career_challenge',
        'special_skill' => 'special_skill',
        'related_course' => 'related_course', // only present for "staying"
        'proximity' => 'proximity',
        'peer_influence' => 'peer', // only present for "staying"
        'family_influence' => 'family', // only present for "staying"
    ];

    private const COMPETENCY_SUFFIX = [
        'communication' => 'communication',
        'human_relations' => 'human_relations',
        'entrepreneurial' => 'entrepreneurial',
        'it_skills' => 'it',
        'problem_solving' => 'problem_solving',
        'critical_thinking' => 'critical_thinking',
    ];

    private const DURATION_KEY = [
        'Less than a month' => 'less_month',
        '1 to 6 months' => '1_6_months',
        '7 to 11 months' => '7_11_months',
        '1 year to less than 2 years' => '1_2_years',
        '2 years to less than 3 years' => '2_3_years',
        '3 years to less than 4 years' => '3_4_years',
        'Others' => 'others',
    ];

    private const HOW_FOUND_KEY = [
        'Response to an advertisement' => 'advertisement',
        'As walk-in applicant' => 'walk_in',
        'Recommended by someone' => 'recommended',
        'Information from friends' => 'friends',
        "Arranged by school's job placement officer" => 'school',
        'Family business' => 'family_business',
        'Job Fair or PESO' => 'peso',
        'Others' => 'others',
    ];

    private const INCOME_KEY = [
        'Below P5,000' => 'below_5000',
        'P5,000 to less than P10,000' => '5000_10000',
        'P10,000 to less than P15,000' => '10000_15000',
        'P15,000 to less than P20,000' => '15000_20000',
        'P20,000 to less than P25,000' => '20000_25000',
        'P25,000 and above' => '25000_above',
    ];

    private const JOB_LEVEL_KEY = [
        'Rank or Clerical' => 'rank',
        'Professional, Technical or Supervisory' => 'professional',
        'Managerial or Executive' => 'managerial',
        'Self-employed' => 'self_employed',
    ];

    private const BUSINESS_LINE_KEY = [
        'Agriculture, Hunting and Forestry' => 'agriculture',
        'Fishing' => 'fishing',
        'Mining and Quarrying' => 'mining',
        'Manufacturing' => 'manufacturing',
        'Electricity, Gas and Water Supply' => 'electricity',
        'Construction' => 'construction',
        'Wholesale and Retail Trade, repair of motor vehicles, motorcycles and personal/household goods' => 'wholesale',
        'Hotels and Restaurants' => 'hotels',
        'Transport, Storage and Communication' => 'transport',
        'Financial Intermediation' => 'financial',
        'Real Estate, Renting and Business Activities' => 'real_estate',
        'Public Administration and Defense; Compulsory Social Security' => 'public_admin',
        'Education' => 'education',
        'Health and Social Work' => 'health',
        'Other Community, Social and Personal Service Activities' => 'other_services',
        'Private Households with Employed Persons' => 'private_households',
        'Extra-territorial Organizations and Bodies' => 'extra_territorial',
    ];

    /**
     * The template itself is inconsistent: {#check.stay_special_skill} is
     * singular, but {#check.accept_special_skills} and
     * {#check.change_special_skills} are plural. Everything else about the
     * "special_skill" reason is otherwise identical across the three
     * sections, so this is the one spot that needs to special-case it
     * rather than assuming one suffix fits all three prefixes.
     */
    private function jobReasonSuffixFor(string $prefix, string $suffix): string
    {
        if ($suffix === 'special_skill' && $prefix !== 'stay') {
            return 'special_skills';
        }

        return $suffix;
    }

    public function map(GraduateTracerSurvey $survey): array
    {
        $gi = $survey->generalInformation;
        $ed = $survey->employmentData;

        $check = [];

        $data = [
            'name' => $gi?->name ?? '',
            'permanent_address' => $gi?->permanent_address ?? '',
            'email' => $gi?->email ?? '',
            'telephone' => $gi?->telephone ?? '',
            'mobile_number' => $gi?->mobile_number ?? '',
            'birthday' => $gi?->birthday?->format('F j, Y') ?? '',
            'province' => $gi?->province ?? '',
        ];

        // Civil status / sex - the current template prints these as plain
        // text ({civil_status}, {sex}) rather than checkboxes, but the old
        // boolean is_single/is_male/etc. flags are kept too in case a
        // future template goes back to checkboxes - unused data keys are
        // simply ignored by docxtemplater, so there's no harm either way.
        $civilStatusLabel = [
            'single' => 'Single', 'married' => 'Married', 'separated' => 'Separated',
            'single_parent' => 'Single Parent', 'widow_widower' => 'Widow/Widower',
        ][$gi?->civil_status] ?? '';
        $data['civil_status'] = $civilStatusLabel;
        $data['sex'] = ucfirst($gi?->sex ?? '');
        $data['region_of_origin'] = $gi?->region_of_origin ?? '';
        $data['residence_city_municipality'] = $gi?->residence_city_municipality ?? '';

        foreach (['single', 'married', 'separated', 'single_parent', 'widow_or_widower'] as $flag) {
            $data['is_' . $flag] = false;
        }
        $civilStatusFlag = ['single' => 'is_single', 'married' => 'is_married', 'separated' => 'is_separated', 'single_parent' => 'is_single_parent', 'widow_widower' => 'is_widow_or_widower'][$gi?->civil_status] ?? null;
        if ($civilStatusFlag) {
            $data[$civilStatusFlag] = true;
        }
        $data['is_male'] = $gi?->sex === 'male';
        $data['is_female'] = $gi?->sex === 'female';

        // Region of origin
        foreach (array_unique(array_values(self::REGION_FLAG_MAP)) as $flag) {
            $data[$flag] = false;
        }
        if ($gi && isset(self::REGION_FLAG_MAP[$gi->region_of_origin])) {
            $data[self::REGION_FLAG_MAP[$gi->region_of_origin]] = true;
        }

        // Residence: derived city/municipality flag (see GraduateTracerController::store)
        $check['residence_city'] = $gi?->residence_location === 'city';
        $check['residence_municipality'] = $gi?->residence_location === 'municipality';

        // ---- Educational Background (loop) ----
        $data['education'] = $survey->educationalBackgrounds->map(fn ($e) => [
            'degree' => $e->degree, 'college_university' => $e->college_university,
            'year_graduated' => $e->year_graduated, 'honors' => $e->honors ?? '',
        ])->values()->all();
        if (empty($data['education'])) {
            $data['education'] = [['degree' => '', 'college_university' => '', 'year_graduated' => '', 'honors' => '']];
        }

        $data['examinations'] = $survey->professionalExams->map(fn ($e) => [
            'exam_name' => $e->exam_name,
            'date_taken' => $e->date_taken?->format('M Y') ?? '',
            'rating' => $e->rating ?? '',
        ])->values()->all();

        // Course reasons (ug_* / grad_*) + free-text "others"
        foreach (self::COURSE_REASON_SUFFIX as $suffix) {
            $check['ug_' . $suffix] = false;
            $check['grad_' . $suffix] = false;
        }
        $data['course_reasons_others'] = '';
        foreach ($survey->courseReasons as $cr) {
            if ($cr->reason_key === 'other') {
                $data['course_reasons_others'] = $cr->other_text ?? '';
                continue;
            }
            $suffix = self::COURSE_REASON_SUFFIX[$cr->reason_key] ?? null;
            if (! $suffix) {
                continue;
            }
            $prefix = $cr->level === 'graduate' ? 'grad' : 'ug';
            $check[$prefix . '_' . $suffix] = true;
        }

        // ---- Section C: Trainings ----
        $data['trainings'] = $survey->trainings->map(fn ($t) => [
            'training_title' => $t->title, 'duration_credits' => $t->duration_credits ?? '', 'training_institution' => $t->institution ?? '',
        ])->values()->all();

        $check['promotion'] = $survey->advance_study_reason === 'promotion';
        $check['professional_development'] = $survey->advance_study_reason === 'professional_development';
        $check['advance_others'] = $survey->advance_study_reason === 'others';
        $data['advance_study_reason_others'] = $survey->advance_study_reason_other ?? '';

        // ---- Section D: Employment ----
        $check['employed_yes'] = $ed?->employment_status === 'yes';
        $check['employed_no'] = $ed?->employment_status === 'no';
        $check['employed_never'] = $ed?->employment_status === 'never_employed';

        foreach (self::NOT_EMPLOYED_SUFFIX as $suffix) {
            $check['notemp_' . $suffix] = false;
        }
        $check['notemp_others'] = false;
        $data['not_employed_reason_others'] = '';
        foreach ($ed?->notEmployedReasons ?? [] as $r) {
            if ($r->reason_key === 'other') {
                $check['notemp_others'] = true;
                $data['not_employed_reason_others'] = $r->other_text ?? '';
                continue;
            }
            $suffix = self::NOT_EMPLOYED_SUFFIX[$r->reason_key] ?? null;
            if ($suffix) {
                $check['notemp_' . $suffix] = true;
            }
        }

        $check['regular_permanent'] = $ed?->present_employment_status === 'regular_permanent';
        $check['contractual'] = $ed?->present_employment_status === 'contractual';
        $check['temporary'] = in_array($ed?->present_employment_status, ['temporary', 'casual'], true);
        $check['self_employed'] = $ed?->present_employment_status === 'self_employed';
        $data['self_employed_skills'] = $ed?->self_employed_skills ?? '';
        $data['present_occupation'] = $ed?->present_occupation ?? '';

        foreach (self::BUSINESS_LINE_KEY as $suffix) {
            $check['business_' . $suffix] = false;
        }
        if ($ed?->business_line && isset(self::BUSINESS_LINE_KEY[$ed->business_line])) {
            $check['business_' . self::BUSINESS_LINE_KEY[$ed->business_line]] = true;
        }

        $check['work_local'] = $ed?->place_of_work === 'local';
        $check['work_abroad'] = $ed?->place_of_work === 'abroad';
        $check['first_job_yes'] = $ed?->is_first_job === true;
        $check['first_job_no'] = $ed?->is_first_job === false;
        $check['first_job_related_yes'] = $ed?->first_job_related_to_course === true;
        $check['first_job_related_no'] = $ed?->first_job_related_to_course === false;

        foreach (['staying', 'accepting', 'changing'] as $type) {
            $prefix = self::JOB_REASON_PREFIX[$type];
            foreach (self::JOB_REASON_SUFFIX as $suffix) {
                $check[$prefix . '_' . $this->jobReasonSuffixFor($prefix, $suffix)] = false;
            }
            $check[$prefix . '_others'] = false;
            $data['reasons_' . ($type === 'staying' ? 'staying' : ($type === 'accepting' ? 'accepting_job' : 'changing_job')) . '_others'] = '';
        }
        foreach ($ed?->jobReasons ?? [] as $jr) {
            $prefix = self::JOB_REASON_PREFIX[$jr->reason_type] ?? null;
            if (! $prefix) {
                continue;
            }
            if ($jr->reason_key === 'other') {
                $field = $jr->reason_type === 'staying' ? 'reasons_staying_others'
                    : ($jr->reason_type === 'accepting' ? 'reasons_accepting_job_others' : 'reasons_changing_job_others');
                $check[$prefix . '_others'] = true;
                $data[$field] = $jr->other_text ?? '';
                continue;
            }
            $suffix = self::JOB_REASON_SUFFIX[$jr->reason_key] ?? null;
            if ($suffix) {
                $check[$prefix . '_' . $this->jobReasonSuffixFor($prefix, $suffix)] = true;
            }
        }

        foreach (self::DURATION_KEY as $suffix) {
            $check['duration_' . $suffix] = false;
            $check['land_' . $suffix] = false;
        }
        if ($ed?->first_job_duration && isset(self::DURATION_KEY[$ed->first_job_duration])) {
            $check['duration_' . self::DURATION_KEY[$ed->first_job_duration]] = true;
        }
        $data['first_job_duration_others'] = '';
        if ($ed?->time_to_land_first_job && isset(self::DURATION_KEY[$ed->time_to_land_first_job])) {
            $check['land_' . self::DURATION_KEY[$ed->time_to_land_first_job]] = true;
        }
        $data['time_to_land_first_job_others'] = '';

        foreach (self::HOW_FOUND_KEY as $suffix) {
            $check['found_' . $suffix] = false;
        }
        if ($ed?->how_found_first_job && isset(self::HOW_FOUND_KEY[$ed->how_found_first_job])) {
            $check['found_' . self::HOW_FOUND_KEY[$ed->how_found_first_job]] = true;
        }
        $data['first_job_found_others'] = '';

        foreach (['first', 'current'] as $when) {
            foreach (self::JOB_LEVEL_KEY as $suffix) {
                $check[$when . '_' . $suffix] = false;
            }
        }
        $jobLevelFirst = self::JOB_LEVEL_KEY[$ed?->job_level_first] ?? null;
        if ($jobLevelFirst) {
            $check['first_' . $jobLevelFirst] = true;
        }
        $jobLevelCurrent = self::JOB_LEVEL_KEY[$ed?->job_level_current] ?? null;
        if ($jobLevelCurrent) {
            $check['current_' . $jobLevelCurrent] = true;
        }

        foreach (self::INCOME_KEY as $suffix) {
            $check['income_' . $suffix] = false;
        }
        if ($ed?->initial_gross_monthly_earning && isset(self::INCOME_KEY[$ed->initial_gross_monthly_earning])) {
            $check['income_' . self::INCOME_KEY[$ed->initial_gross_monthly_earning]] = true;
        }

        $check['curriculum_relevant_yes'] = $ed?->curriculum_relevant === true;
        $check['curriculum_relevant_no'] = $ed?->curriculum_relevant === false;

        foreach (self::COMPETENCY_SUFFIX as $suffix) {
            $check['competency_' . $suffix] = false;
        }
        $check['competency_others'] = false;
        $data['useful_competencies_others'] = '';
        foreach ($ed?->competencies ?? [] as $c) {
            if ($c->competency_key === 'other') {
                $check['competency_others'] = true;
                $data['useful_competencies_others'] = $c->other_text ?? '';
                continue;
            }
            $suffix = self::COMPETENCY_SUFFIX[$c->competency_key] ?? null;
            if ($suffix) {
                $check['competency_' . $suffix] = true;
            }
        }

        $data['curriculum_suggestions'] = $ed?->curriculum_suggestions ?? '';

        // ---- Optional add-on: other graduates ----
        $data['graduates'] = $survey->otherGraduates->map(fn ($g) => [
            'graduate_name' => $g->name ?? '', 'graduate_address' => $g->address ?? '', 'graduate_contact' => $g->contact_number ?? '',
        ])->values()->all();

        $data['check'] = $check;

        return $data;
    }
}
