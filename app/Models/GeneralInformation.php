<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralInformation extends Model
{
    protected $table = 'general_information';
    public $timestamps = false;

    protected $fillable = [
        'survey_id', 'name', 'permanent_address', 'telephone', 'email',
        'mobile_number', 'civil_status', 'sex', 'birthday',
        'region_of_origin', 'province', 'residence_city_municipality', 'residence_location',
    ];

    protected $casts = ['birthday' => 'date'];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }
}
