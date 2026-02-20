<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'monthly_price',
        'yearly_price',
        'limits',
        'is_lifetime',
        'is_active',
    ];

    protected $casts = [
        'limits' => 'array',
        'is_lifetime' => 'boolean',
        'is_active' => 'boolean',
    ];}
