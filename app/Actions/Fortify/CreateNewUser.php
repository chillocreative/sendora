<?php

namespace App\Actions\Fortify;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            return tap(User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
            ]), function (User $user) use ($input) {
                $this->createTeam($user);

                if (!empty($input['plan_id'])) {
                    $plan = \App\Models\SubscriptionPlan::find($input['plan_id']);
                    if ($plan) {
                        $isFree = $plan->monthly_price <= 0;
                        $cycle = $input['billing_cycle'] ?? 'monthly';
                        
                        \App\Models\UserSubscription::create([
                            'user_id' => $user->id,
                            'subscription_plan_id' => $plan->id,
                            'ends_at' => $isFree ? null : ($cycle === 'yearly' ? now()->addYear() : now()->addMonth()),
                            'status' => $isFree ? 'active' : 'waiting_for_payment',
                        ]);

                        if (!$isFree) {
                            session(['registration_billing_cycle' => $cycle]);
                        }
                    }
                }
            });
        });
    }

    /**
     * Create a personal team for the user.
     */
    protected function createTeam(User $user): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'name' => explode(' ', $user->name, 2)[0]."'s Team",
            'personal_team' => true,
        ]));
    }
}
