<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPhoto extends Model
{
    protected $fillable = ['booking_id', 'professional_id', 'path', 'original_name', 'mime_type', 'size', 'category', 'captured_at'];
    protected function casts(): array { return ['captured_at' => 'datetime']; }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function professional(): BelongsTo { return $this->belongsTo(Professional::class); }
}
