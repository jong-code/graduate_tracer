<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotEmployedReason extends Model
{
    protected $table = 'not_employed_reasons_17';
    public $timestamps = false;

    protected $fillable = ['employment_data_id', 'reason_key', 'other_text'];

    public function employmentData()
    {
        return $this->belongsTo(EmploymentData::class, 'employment_data_id');
    }
}
