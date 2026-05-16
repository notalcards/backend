<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_id',
        'type',
        'input_data',
        'result_data',
        'interpretation',
        'credits_spent',
        'share_token',
    ];

    protected $casts = [
        'input_data' => 'array',
        'result_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}
