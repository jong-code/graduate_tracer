<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmploymentData extends Model
{
    protected $table = 'employment_data';
    public $timestamps = false;

    protected $fillable = [
        'survey_id', 'employment_status', 'present_employment_status',
        'present_occupation', 'business_line',
        'place_of_work', 'is_first_job', 'first_job_related_to_course',
        'first_job_duration', 'how_found_first_job', 'time_to_land_first_job',
        'job_level_first', 'job_level_current', 'initial_gross_monthly_earning',
        'curriculum_relevant', 'curriculum_suggestions',
    ];

    protected $casts = [
        'is_first_job' => 'boolean',
        'first_job_related_to_course' => 'boolean',
        'curriculum_relevant' => 'boolean',
    ];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }

    public function notEmployedReasons()
    {
        return $this->hasMany(NotEmployedReason::class, 'employment_data_id');
    }

    public function jobReasons()
    {
        return $this->hasMany(JobReason::class, 'employment_data_id');
    }

    public function competencies()
    {
        return $this->hasMany(Competency::class, 'employment_data_id');
    }

    public function selfEmployedSkills()
    {
        return $this->hasMany(SelfEmployedSkill::class, 'employment_data_id');
    }

    public function location()
    {
        return $this->hasOne(Location::class, 'employment_data_id');
    }
}
