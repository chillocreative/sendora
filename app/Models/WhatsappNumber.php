<?php

namespace App\Models;

use App\Services\WhatsappCloudService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WhatsappNumber extends Model
{
    protected $fillable = [
        'user_id',
        'phone_number',
        'session_data',
        'status',
        'qr_code',
        'phone_info',
        'playbook_id',
        'ai_reply_enabled',
        'provider',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'access_token',
        'token_expires_at',
        'quality_rating',
        'messaging_limit',
        'webhook_subscribed_at',
        'owner_phone',
        'owner_phone_verified_at',
        'owner_verification_code',
    ];

    protected $hidden = [
        'access_token',
        'owner_verification_code',
    ];

    protected $casts = [
        'phone_info' => 'array',
        'ai_reply_enabled' => 'boolean',
        'access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'webhook_subscribed_at' => 'datetime',
        'owner_phone_verified_at' => 'datetime',
    ];

    public function isCloud(): bool
    {
        return $this->provider === 'cloud';
    }

    public function hasVerifiedOwnerPhone(): bool
    {
        return ! empty($this->owner_phone) && $this->owner_phone_verified_at !== null;
    }

    /**
     * Begins owner-phone verification; returns the plain code the owner must send
     * from that phone as "VERIFY <code>". Only a hash is stored.
     */
    public function startOwnerVerification(string $phone): string
    {
        $code = Str::upper(Str::random(6));

        $this->forceFill([
            'owner_phone' => app(WhatsappCloudService::class)->normalizePhone($phone),
            'owner_phone_verified_at' => null,
            'owner_verification_code' => Hash::make($code),
        ])->save();

        return $code;
    }

    public function confirmOwnerVerification(string $from, string $code): bool
    {
        if (empty($this->owner_phone) || empty($this->owner_verification_code)) {
            return false;
        }

        if (! $this->phoneMatchesOwner($from)) {
            return false;
        }

        if (! Hash::check(strtoupper(trim($code)), $this->owner_verification_code)) {
            return false;
        }

        $this->forceFill([
            'owner_phone_verified_at' => now(),
            'owner_verification_code' => null,
        ])->save();

        return true;
    }

    public function isOwnerPhone(string $from): bool
    {
        return $this->hasVerifiedOwnerPhone() && $this->phoneMatchesOwner($from);
    }

    protected function phoneMatchesOwner(string $from): bool
    {
        $digits = preg_replace('/\D/', '', preg_replace('/@.*$/', '', $from));

        return $digits !== '' && $digits === $this->owner_phone;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function playbook()
    {
        return $this->belongsTo(Playbook::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }
}
