<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_plan_id',
        'amount',
        'currency',
        'status',
        'reference_id',
        'payment_method',
        'gateway_id',
        'token_pack_id',
        'token_amount',
        'fulfilled_at',
    ];

    protected $casts = ['fulfilled_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }
}
