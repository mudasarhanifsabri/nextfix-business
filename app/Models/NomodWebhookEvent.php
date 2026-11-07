<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NomodWebhookEvent extends Model
{
    protected $fillable = ['event_id', 'event_type', 'object_id', 'payload', 'processed_at'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'processed_at' => 'datetime'];
    }
}
