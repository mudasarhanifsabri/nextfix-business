<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyRateCard extends Model
{
    protected $fillable = ['company_id', 'status', 'currency', 'maintenance_inspection_minor', 'rates', 'terms', 'review_note', 'submitted_at', 'approved_at', 'review_requested_at'];

    protected function casts(): array
    {
        return [
            'rates' => 'array',
            'terms' => 'array',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'review_requested_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function amount(string $code): ?int
    {
        $value = data_get($this->rates ?? [], $code);

        return is_numeric($value) ? (int) $value : null;
    }
}
