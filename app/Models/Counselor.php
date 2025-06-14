<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counselor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'avatar', 'email', 'phone', 'about', 'rate'
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}