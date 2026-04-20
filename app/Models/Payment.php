<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'yookassa_id',
        'status',
        'amount',
        'credits',
        'tariff',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
