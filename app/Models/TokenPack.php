<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenPack extends Model
{
    protected $fillable = ['name', 'tokens', 'price', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
