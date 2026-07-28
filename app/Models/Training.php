<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    protected $table = 'trainings';
    public $timestamps = false;

    protected $fillable = ['survey_id', 'title', 'duration_credits', 'institution'];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }
}
