<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BookingMessage extends Model
{
    protected $fillable = ['booking_id', 'user_id', 'type', 'body', 'path', 'original_name', 'mime_type', 'size'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'booking_message_reads')->withPivot('read_at');
    }
}
