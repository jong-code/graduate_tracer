<?php

namespace App\Models;

use App\Casts\EncryptedFloat;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $table = 'location';
    public $timestamps = false;

    protected $fillable = ['employment_data_id', 'longitude', 'latitude'];

    protected $casts = [
        'longitude' => EncryptedFloat::class,
        'latitude' => EncryptedFloat::class,
    ];

    public function employmentData()
    {
        return $this->belongsTo(EmploymentData::class, 'employment_data_id');
    }
}
