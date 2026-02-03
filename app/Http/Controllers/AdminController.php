<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Transaction;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'status' => 'required|string|in:Active,Inactive',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Create personal team (Jetstream requirement)
            $user->ownedTeams()->create([
                'name' => $user->name . "'s Team",
                'personal_team' => true,
            ]);

            $user->switchTeam($user->personalTeam());

            $planId = $request->plan_id;
            if (!$planId) {
                $planId = SubscriptionPlan::where('name', 'Starter')->value('id');
            }

            if ($planId) {
                $user->subscriptions()->create([
                    'subscription_plan_id' => $planId,
                    'status' => $request->status === 'Active' ? 'active' : 'inactive',
                    'ends_at' => now()->addMonth(),
                ]);
            }
        });

        return back()->with('success', 'User created successfully.');
    }

    public function financials(Request $request)
    {
        $totalRevenue = Transaction::where('status', 'paid')->sum('amount');
        
        // MRR Approximation
        $mrr = Transaction::where('status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('amount');

        $activeSubscribers = \App\Models\UserSubscription::where('status', 'active')->count();

        $recentTransactions = Transaction::with(['user', 'plan'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'user_id' => $t->user_id,
                    'subscription_plan_id' => $t->subscription_plan_id,
                    'customer' => $t->user->name ?? 'Unknown',
                    'plan' => $t->plan->name ?? 'N/A',
                    'date' => $t->created_at->format('M d, Y'),
                    'amount' => number_format($t->amount, 2),
                    'raw_amount' => $t->amount,
                    'status' => $t->status,
                    'currency' => $t->currency,
                    'reference_id' => $t->reference_id,
                    'payment_method' => $t->payment_method,
                ];
            });

        $currency = Setting::where('key', 'currency')->value('value') ?? 'MYR';

        // Get all users and plans for the forms
        $users = User::select('id', 'name', 'email')->get();
        $plans = SubscriptionPlan::select('id', 'name', 'monthly_price', 'yearly_price')->get();

        // Monthly Stats Filtering
        $filterMonth = $request->input('month', Carbon::now()->month);
        $filterYear = $request->input('year', Carbon::now()->year);

        $monthlyStats = [
            'revenue' => Transaction::where('status', 'paid')
                ->whereMonth('created_at', $filterMonth)
                ->whereYear('created_at', $filterYear)
                ->sum('amount'),
            'count' => Transaction::where('status', 'paid')
                ->whereMonth('created_at', $filterMonth)
                ->whereYear('created_at', $filterYear)
                ->count(),
            'month' => (int)$filterMonth,
            'year' => (int)$filterYear,
        ];

        // Get available years for filter
        $years = Transaction::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
            
        if (empty($years)) {
            $years = [Carbon::now()->year];
        }

        return Inertia::render('Admin/Financials', [
            'stats' => [
                'total_revenue' => number_format($totalRevenue, 2),
                'mrr' => number_format($mrr, 2),
                'active_subscribers' => $activeSubscribers,
                'currency' => $currency,
            ],
            'transactions' => $recentTransactions,
            'users' => $users,
            'plans' => $plans,
            'monthlyStats' => $monthlyStats,
            'filterYears' => $years,
        ]);
    }

    public function users()
    {
        $users = User::with(['activeSubscription.plan', 'subscriptions.plan'])
            ->latest()
            ->paginate(15)
            ->through(function ($u) {
                $sub = $u->activeSubscription ?? $u->subscriptions()->latest()->first();
                $lastPayment = Transaction::where('user_id', $u->id)->where('status', 'paid')->latest()->first();
                
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'profile_photo_url' => $u->profile_photo_url,
                    'role' => $u->is_admin ? 'Admin' : 'User',
                    'plan' => $sub?->plan?->name ?? 'Free',
                    'subscription_id' => $sub?->id,
                    'plan_id' => $sub?->subscription_plan_id,
                    'status' => $sub && $u->activeSubscription()->exists() ? 'Active' : 'Inactive',
                    'payment_date' => $lastPayment ? $lastPayment->created_at->format('M d, Y') : 'No record',
                ];
            });

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'plans' => SubscriptionPlan::all(),
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'status' => 'nullable|string|in:Active,Inactive',
        ]);

        $user->update($request->only('name', 'email'));

        if ($request->has('plan_id') || $request->has('status')) {
            $sub = $user->activeSubscription ?? $user->subscriptions()->latest()->first();
            $status = $request->status === 'Active' ? 'active' : 'inactive';
            
            if ($sub) {
                $updateData = ['status' => $status];
                if ($request->filled('plan_id')) {
                    $updateData['subscription_plan_id'] = $request->plan_id;
                }
                $sub->update($updateData);
            } else if ($request->filled('plan_id')) {
                $user->subscriptions()->create([
                    'subscription_plan_id' => $request->plan_id,
                    'status' => $status,
                    'ends_at' => now()->addMonth(),
                ]);
            }
        }

        return back()->with('success', 'User updated successfully.');
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function plans()
    {
        $currency = Setting::where('key', 'currency')->value('value') ?? 'USD';
        
        return Inertia::render('Admin/SubscriptionPlans', [
            'plans' => SubscriptionPlan::all(),
            'currency' => $currency,
        ]);
    }

    public function updatePlan(Request $request, $id)
    {
        $plan = SubscriptionPlan::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string',
            'monthly_price' => 'required|numeric',
            'yearly_price' => 'required|numeric',
            'limits' => 'nullable|array',
        ]);

        $plan->update($request->all());

        return back()->with('success', 'Plan updated successfully.');
    }

    public function server()
    {
        // Database Check
        try {
            DB::connection()->getPdo();
            $dbStatus = 'Online';
        } catch (\Exception $e) {
            $dbStatus = 'Offline';
        }

        // Disk Usage
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercentage = round(($diskUsed / $diskTotal) * 100);

        // Memory (PHP usage as proxy for now)
        $memoryUsage = memory_get_usage(true);
        $memoryFormatted = round($memoryUsage / 1024 / 1024, 2) . ' MB';

        return Inertia::render('Admin/ServerHealth', [
            'status' => [
                'database' => $dbStatus,
                'disk_usage' => $diskPercentage,
                'memory_usage' => $memoryFormatted,
                'php_version' => PHP_VERSION,
            ]
        ]);
    }

    public function settings()
    {
        $settings = Setting::all()->pluck('value', 'key');
        
        // Ensure defaults for specific keys if they don't exist in DB
        $defaults = [
            'app_name' => 'Syncra',
            'app_url' => config('app.url'),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'maintenance_mode' => '0',
            'chip_in_brand_id' => '',
            'chip_in_private_key' => '',
            'admin_mobile_number' => '', // For system notifications
            'mail_host' => 'smtp-relay.brevo.com',
            'mail_port' => '587',
            'mail_username' => '',
            'mail_password' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'hello@sendora.com',
            'mail_from_name' => 'Sendora',
        ];

        foreach ($defaults as $key => $val) {
            if (!isset($settings[$key])) {
                $settings[$key] = $val;
            }
        }

        return Inertia::render('Admin/GlobalSettings', [
            'settings' => $settings,
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->except(['_token']); // Get all inputs

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Settings saved successfully.');
    }

    public function systemWhatsapp()
    {
        $user = auth()->user();
        $numbers = $user->whatsappNumbers()->get();

        return Inertia::render('Admin/SystemWhatsapp', [
            'numbers' => $numbers,
        ]);
    }
}
