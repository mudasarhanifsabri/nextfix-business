<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['company_id', 'number', 'public_token', 'issued_at', 'due_at', 'status', 'line_items', 'subtotal_minor', 'tax_minor', 'amount_minor', 'currency', 'notes'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'due_at' => 'date', 'line_items' => 'array'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getAmountAttribute(): string
    {
        return number_format($this->amount_minor / 100, 2);
    }
}
