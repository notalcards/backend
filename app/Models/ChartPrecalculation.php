<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChartPrecalculation extends Model
{
    protected $fillable = ['token', 'birth_data', 'result_data', 'interpretation', 'expires_at'];

    protected $casts = [
        'birth_data'  => 'array',
        'result_data' => 'array',
        'expires_at'  => 'datetime',
    ];
}
