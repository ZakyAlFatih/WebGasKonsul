<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'counselor_id', 'day', 'time', 'isBooked'
    ];

    public function counselor()
    {
        return $this->belongsTo(Counselor::class);
    }
}