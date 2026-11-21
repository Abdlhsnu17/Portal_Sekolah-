<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginDevice extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['first_seen_at' => 'datetime', 'last_seen_at' => 'datetime'];
    }
}
