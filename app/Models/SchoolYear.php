<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolYear extends Model
{
    protected $fillable = ['label', 'is_current'];

    protected $casts = ['is_current' => 'boolean'];

    public function surveys()
    {
        return $this->hasMany(GraduateTracerSurvey::class);
    }
}
