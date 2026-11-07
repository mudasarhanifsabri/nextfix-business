<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'building', 'unit_number', 'rate_unit_type', 'bedrooms', 'size_sqft', 'address', 'access_notes', 'parking_notes', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getRateUnitLabelAttribute(): string
    {
        return \App\Support\RateCatalogue::unitTypes()[$this->rate_unit_type] ?? ucfirst(str_replace('_', ' ', $this->rate_unit_type));
    }
}
