<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'grade_level',
        'email',
        'status'
    ];

    public function requests()
    {
        return $this->hasMany(ServiceRequest::class);
    }
}
