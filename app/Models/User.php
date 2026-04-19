<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'credits',
        'is_blocked',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_blocked' => 'boolean',
        'is_admin' => 'boolean',
    ];

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function charts()
    {
        return $this->hasMany(Chart::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class);
    }

    public function hasEnoughCredits(int $amount): bool
    {
        return $this->credits >= $amount;
    }

    public function deductCredits(int $amount): void
    {
        $this->decrement('credits', $amount);
    }

    public function addCredits(int $amount): void
    {
        $this->increment('credits', $amount);
    }
}
