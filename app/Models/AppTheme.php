<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppTheme extends Model
{
    protected $fillable = ['scope', 'configuration', 'previous_configuration', 'revision', 'updated_by'];

    protected function casts(): array
    {
        return ['configuration' => 'array', 'previous_configuration' => 'array', 'revision' => 'integer'];
    }
}
