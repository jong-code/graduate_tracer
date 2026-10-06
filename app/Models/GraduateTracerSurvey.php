<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GraduateTracerSurvey extends Model
{
    protected $table = 'graduate_tracer_survey';
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'academic_program_id', 'school_year_id',
        'institution_code', 'control_code', 'submitted_at',
        'advance_study_reason', 'advance_study_reason_other',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForGraduateUsers(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $users) => $users->where('role', 'user'));
    }

    public function academicProgram()
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    // Section A
    public function generalInformation()
    {
        return $this->hasOne(GeneralInformation::class, 'survey_id');
    }

    // Section B
    public function educationalBackgrounds()
    {
        return $this->hasMany(EducationalBackground::class, 'survey_id');
    }

    public function professionalExams()
    {
        return $this->hasMany(ProfessionalExam::class, 'survey_id');
    }

    public function courseReasons()
    {
        return $this->hasMany(CourseReason::class, 'survey_id');
    }

    // Section C
    public function trainings()
    {
        return $this->hasMany(Training::class, 'survey_id');
    }

    // Section D
    public function employmentData()
    {
        return $this->hasOne(EmploymentData::class, 'survey_id');
    }

    // Section D (optional add-on): voluntary list of other alumni
    public function otherGraduates()
    {
        return $this->hasMany(OtherGraduate::class, 'survey_id');
    }
}
