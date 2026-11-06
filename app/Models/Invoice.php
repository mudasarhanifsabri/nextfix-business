<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['company_id', 'number', 'issued_at', 'due_at', 'status', 'amount_minor', 'currency'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'due_at' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getAmountAttribute(): string
    {
        return number_format($this->amount_minor / 100, 2);
    }
}
