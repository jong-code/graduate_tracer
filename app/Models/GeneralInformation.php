<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralInformation extends Model
{
    protected $table = 'general_information';
    public $timestamps = false;

    protected $fillable = [
        'survey_id', 'name', 'last_name', 'middle_name', 'telephone', 'email',
        'mobile_number', 'civil_status', 'sex', 'birthday',
        'region_of_origin', 'residence_location',
    ];

    protected $casts = [
        'birthday' => 'date',
        'name' => 'encrypted',
        'last_name' => 'encrypted',
        'middle_name' => 'encrypted',
        'email' => 'encrypted',
        'mobile_number' => 'encrypted',
    ];

    public function survey()
    {
        return $this->belongsTo(GraduateTracerSurvey::class, 'survey_id');
    }

    public function formattedName(): string
    {
        $middle = trim($this->middle_name ?? '');
        return trim(implode(' ', array_filter([
            trim($this->name ?? ''),
            $middle !== '' ? mb_substr($middle, 0, 1).'.' : null,
            trim($this->last_name ?? ''),
        ])));
    }

    public function address()
    {
        return $this->hasOne(Address::class);
    }
}
