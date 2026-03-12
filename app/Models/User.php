<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'is_admin',
    ];

    public function getIsAdminAttribute()
    {
        return $this->email === 'admin@blaster.com';
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)->where('status', 'active')->latest();
    }

    /**
     * Get the user's active plan, or null if no active subscription.
     */
    public function getCurrentPlanAttribute()
    {
        $sub = $this->activeSubscription()->with('plan')->first();

        return $sub?->plan;
    }

    public function latestSubscription()
    {
        return $this->hasOne(UserSubscription::class)->latestOfMany();
    }

    public function whatsappNumbers()
    {
        return $this->hasMany(WhatsappNumber::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function googleCalendarConnection()
    {
        return $this->hasOne(GoogleCalendarConnection::class);
    }

    public function playbooks()
    {
        return $this->hasMany(Playbook::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\CustomResetPassword($token));
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
