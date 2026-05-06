<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exception extends Model
{
    use HasFactory;

    protected $fillable = [
        'exception_date',
        'start_time',
        'end_time',
        'reason',
        'is_available',
    ];

    protected $casts = [
        'exception_date' => 'date',
        'start_time' => 'datetime:H:i',   // Returns Carbon instance
        'end_time' => 'datetime:H:i',   // Returns Carbon instance
        'is_available' => 'boolean',
    ];
}