<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobReason extends Model
{
    protected $table = 'job_reasons_23to25';
    public $timestamps = false;

    protected $fillable = ['employment_data_id', 'reason_type', 'reason_key', 'number', 'other_text'];

    /**
     * `number` always tracks the literal GTS question number and must be
     * derived from reason_type, never set independently, so the two can
     * never drift out of sync.
     */
    public const QUESTION_NUMBERS = [
        'staying' => 23,
        'accepting' => 25,
        'changing' => 26,
    ];

    public function employmentData()
    {
        return $this->belongsTo(EmploymentData::class, 'employment_data_id');
    }
}
