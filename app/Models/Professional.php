<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Professional extends Model
{
    protected $fillable = ['name', 'role', 'email', 'phone', 'status', 'skills', 'working_days', 'notes'];

    protected function casts(): array
    {
        return ['skills' => 'array', 'working_days' => 'array'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
