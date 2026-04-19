<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'birth_date',
        'birth_time',
        'birth_place',
        'lat',
        'lng',
        'is_default',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_default' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function charts()
    {
        return $this->hasMany(Chart::class);
    }
}
