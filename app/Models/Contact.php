<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'user_id',
        'whatsapp_number_id',
        'name',
        'phone_number',
        'country_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function whatsappNumber()
    {
        return $this->belongsTo(WhatsappNumber::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function contactBooks()
    {
        return $this->belongsToMany(ContactBook::class);
    }
