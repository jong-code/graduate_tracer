<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfessionalExam extends Model
{
    protected $table = 'professional_exams';
    public $timestamps = false;

    protected $fillable = ['survey_id', 'exam_name', 'date_taken', 'rating'];

    protected $casts = ['date_taken' => 'date'];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }
}
