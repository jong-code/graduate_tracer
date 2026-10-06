<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SelfEmployedSkill extends Model
{
    // Table has no created_at/updated_at columns.
    public $timestamps = false;

    protected $fillable = ['employment_data_id', 'skill_name'];

    public function employmentData()
    {
        return $this->belongsTo(EmploymentData::class, 'employment_data_id');
    }
}
