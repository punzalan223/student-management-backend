<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'student_id',
        'service_type',
        'date_requested',
        'status',
        'remarks'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
