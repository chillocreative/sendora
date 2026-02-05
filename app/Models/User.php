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
        'warmer_enabled',
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
     * Get the user's active plan OR the default Starter plan if none exists.
     */
    public function getCurrentPlanAttribute()
    {
        $sub = $this->activeSubscription()->with('plan')->first();
        
        if ($sub) {
            return $sub->plan;
        }

        // Return the default Starter plan if no active subscription
        return SubscriptionPlan::where('name', 'Starter')->first();
    }

    public function latestSubscription()
    {
        return $this->hasOne(UserSubscription::class)->latestOfMany();
    }

    public function whatsappNumbers()
    {
        return $this->hasMany(WhatsappNumber::class);
    }

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function autoReplies()
    {
        return $this->hasMany(AutoReply::class);
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
            'warmer_enabled' => 'boolean',
        ];
    }
}
