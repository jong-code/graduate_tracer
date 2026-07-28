<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNumber extends Model
{
    // Table name is singular ("user_number") by explicit request, rather
    // than Eloquent's default pluralized "user_numbers".
    protected $table = 'user_number';

    protected $fillable = ['user_id', 'number', 'is_done'];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
