<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'legal_name', 'trade_license_number', 'trn', 'industry', 'contact_name', 'email', 'phone', 'billing_email', 'billing_address', 'city', 'country', 'payment_terms', 'status', 'inspection_rate_minor', 'checkout_cleaning_rate_minor', 'regular_cleaning_rate_minor', 'deep_cleaning_rate_minor'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function rateCard(): HasOne
    {
        return $this->hasOne(CompanyRateCard::class);
    }
}
