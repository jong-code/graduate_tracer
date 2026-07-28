<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Competency extends Model
{
    protected $table = 'competencies';
    public $timestamps = false;

    protected $fillable = ['employment_data_id', 'competency_key', 'other_text'];

    public function employmentData()
    {
        return $this->belongsTo(EmploymentData::class, 'employment_data_id');
    }
}
