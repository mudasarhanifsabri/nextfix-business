<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'unit_id', 'reference', 'service_type', 'service_name', 'scheduled_at', 'status', 'payment_status', 'amount_minor', 'currency', 'notes'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function getAmountAttribute(): string
    {
        return number_format($this->amount_minor / 100, 2);
    }
}
