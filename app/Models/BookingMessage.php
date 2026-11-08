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

    public function displayType(): string
    {
        if ($this->type !== 'file') {
            return $this->type;
        }

        $name = strtolower((string) $this->original_name);
        $mime = strtolower((string) $this->mime_type);
        $isRecordedVoice = str_starts_with($name, 'voice-')
            && preg_match('/\.(webm|ogg|mp3|m4a|wav)$/', $name);

        return str_starts_with($mime, 'audio/') || $isRecordedVoice ? 'audio' : 'file';
    }
}
