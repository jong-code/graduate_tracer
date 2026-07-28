<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherGraduate extends Model
{
    protected $table = 'other_graduates';
    public $timestamps = false;

    protected $fillable = ['survey_id', 'name', 'address', 'contact_number'];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }
}
