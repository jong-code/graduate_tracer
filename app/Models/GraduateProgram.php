<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GraduateProgram extends Model
{
    protected $table = 'graduate_program';

    protected $fillable = ['user_id', 'academic_program_id', 'school_year_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function academicProgram()
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
