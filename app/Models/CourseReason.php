<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseReason extends Model
{
    protected $table = 'course_reasons';
    public $timestamps = false;

    protected $fillable = ['survey_id', 'level', 'reason_key', 'other_text'];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }
}
