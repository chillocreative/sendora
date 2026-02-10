<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaybookVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'playbook_id',
        'version_number',
        'content',
        'change_summary',
        'source',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function playbook()
    {
        return $this->belongsTo(Playbook::class);
    }
}
