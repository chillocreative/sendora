<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Playbook extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'content',
        'is_active',
        'model',
        'temperature',
        'max_tokens',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'temperature' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappNumbers()
    {
        return $this->hasMany(WhatsappNumber::class);
    }

    public function versions()
    {
        return $this->hasMany(PlaybookVersion::class)->orderByDesc('version_number');
    }

    public function latestVersion()
    {
        return $this->hasOne(PlaybookVersion::class)->latestOfMany('version_number');
    }
}
