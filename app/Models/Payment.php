<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['company_id', 'invoice_id', 'reference', 'method', 'status', 'amount_minor', 'currency', 'paid_at', 'gateway_reference', 'notes'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function getAmountAttribute(): string
    {
        return number_format($this->amount_minor / 100, 2);
    }
}
