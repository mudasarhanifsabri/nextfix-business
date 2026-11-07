<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'unit_id', 'professional_id', 'reference', 'service_type', 'service_name', 'scheduled_at', 'status', 'payment_status', 'amount_minor', 'currency', 'notes', 'completed_items', 'progress', 'workflow_stage', 'inspection_condition', 'inspection_issues', 'inspection_report', 'labour_hours', 'labour_amount_minor', 'parts_amount_minor', 'parts_required', 'estimate_note', 'estimate_submitted_at', 'estimate_approved_at', 'hold_reason', 'hold_note', 'work_started_at', 'work_completed_at'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime', 'completed_items' => 'array', 'inspection_issues' => 'array', 'estimate_submitted_at' => 'datetime', 'estimate_approved_at' => 'datetime', 'work_started_at' => 'datetime', 'work_completed_at' => 'datetime'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function getAmountAttribute(): string
    {
        return number_format($this->amount_minor / 100, 2);
    }
}
