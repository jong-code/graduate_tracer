<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationalBackground extends Model
{
    protected $table = 'educational_background';
    public $timestamps = false;

    protected $fillable = ['survey_id', 'degree', 'college_university', 'year_graduated', 'honors'];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }
}
