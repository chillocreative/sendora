<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\WhatsappNumberController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/l/{messageId}', [\App\Http\Controllers\LinkController::class, 'track'])->name('link.track');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        if ($user->is_admin) {
             // Admin Dashboard Stats
             $totalUsers = \App\Models\User::count();
             $activeSubscriptions = \App\Models\UserSubscription::where('status', 'active')->count();
             $totalRevenue = \App\Models\Transaction::where('status', 'paid')->sum('amount');
             $monthlyRevenue = \App\Models\Transaction::where('status', 'paid')
                 ->whereMonth('created_at', now()->month)
                 ->sum('amount');
             
             $recentTransactions = \App\Models\Transaction::with('user')
                 ->latest()
                 ->take(5)
                 ->get();
             
             // Server stats
             $diskUsage = disk_free_space('/') ? round((disk_total_space('/') - disk_free_space('/')) / disk_total_space('/') * 100, 2) : 0;
             $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2); // MB
             
             return Inertia::render('Admin/Dashboard', [
                'stats' => [
                    'totalUsers' => $totalUsers,
                    'activeSubscriptions' => $activeSubscriptions,
                    'totalRevenue' => $totalRevenue,
                    'monthlyRevenue' => $monthlyRevenue,
                    'diskUsage' => $diskUsage,
                    'memoryUsage' => $memoryUsage,
                ],
                'recentTransactions' => $recentTransactions,
             ]);
        }
        
        // User Dashboard with Analytics
        return app(\App\Http\Controllers\DashboardController::class)->index();
    })->name('dashboard');

    // Test Email Delivery
    Route::get('/test-email', function () {
        try {
            \Illuminate\Support\Facades\Mail::to(auth()->user()->email)->send(new \App\Mail\WelcomeEmail());
            return back()->with('flash', ['message' => 'Branded test email dispatched successfully. Check your Brevo logs.']);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Delivery failed: ' . $e->getMessage()]);
        }
    })->name('email.test');

    // Export Campaign Report
    Route::get('/reports/export', [\App\Http\Controllers\DashboardController::class, 'exportReport'])->name('reports.export');

    Route::get('/checkout', [PaymentController::class, 'checkout'])->name('checkout');
    Route::post('/payments/initiate', [PaymentController::class, 'initiatePayment'])->name('payments.initiate');
    Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/fail', [PaymentController::class, 'fail'])->name('payments.fail');

    // WhatsApp & Features Routes
    Route::get('/whatsapp', [WhatsappNumberController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp', [WhatsappNumberController::class, 'create'])->name('whatsapp.create')->middleware('subscription.limit:whatsapp_nos');
    Route::get('/whatsapp/{id}', [WhatsappNumberController::class, 'show'])->name('whatsapp.show');
    Route::get('/whatsapp/{id}/refresh-qr', [WhatsappNumberController::class, 'refreshQr'])->name('whatsapp.refresh-qr');
    Route::delete('/whatsapp/{id}', [WhatsappNumberController::class, 'destroy'])->name('whatsapp.destroy');

    // Test Message
    Route::get('/test-message', [\App\Http\Controllers\TestMessageController::class, 'index'])->name('test-message.index');
    Route::post('/test-message', [\App\Http\Controllers\TestMessageController::class, 'send'])->name('test-message.send');


    Route::get('/contacts', [\App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
    Route::post('/contacts', [\App\Http\Controllers\ContactController::class, 'store'])->name('contacts.store');
    Route::put('/contacts/{id}', [\App\Http\Controllers\ContactController::class, 'update'])->name('contacts.update');
    Route::delete('/contacts/{id}', [\App\Http\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');
    Route::post('/contacts/import', [\App\Http\Controllers\ContactController::class, 'import'])->name('contacts.import');
    Route::post('/contacts/bulk-delete', [\App\Http\Controllers\ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    
    Route::get('/campaigns', [\App\Http\Controllers\CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [\App\Http\Controllers\CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [\App\Http\Controllers\CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{id}/edit', [\App\Http\Controllers\CampaignController::class, 'edit'])->name('campaigns.edit');
    Route::post('/campaigns/{id}', [\App\Http\Controllers\CampaignController::class, 'update'])->name('campaigns.update');
    Route::post('/campaigns/{id}/stop', [\App\Http\Controllers\CampaignController::class, 'stop'])->name('campaigns.stop');
    Route::delete('/campaigns/{id}', [\App\Http\Controllers\CampaignController::class, 'destroy'])->name('campaigns.destroy');

    Route::middleware(['subscription.limit:auto_reply'])->group(function () {
        Route::get('/auto-replies', [\App\Http\Controllers\AutoReplyController::class, 'index'])->name('auto-replies.index');
        Route::post('/auto-replies', [\App\Http\Controllers\AutoReplyController::class, 'store'])->name('auto-replies.store');
        Route::put('/auto-replies/{id}', [\App\Http\Controllers\AutoReplyController::class, 'update'])->name('auto-replies.update');
        Route::delete('/auto-replies/{id}', [\App\Http\Controllers\AutoReplyController::class, 'destroy'])->name('auto-replies.destroy');
    });

    Route::post('/subscription/cancel-plan', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    // Fallback for potentially cached frontends - no name to avoid conflict
    Route::post('/subscription/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel']); 
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscription.show');

    Route::middleware(['subscription.limit:api_access'])->group(function () {
        // API Token Management
        Route::get('/user/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/user/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('/user/api-tokens/{tokenId}', [\App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
        
        // API Documentation
        Route::get('/api-docs', [\App\Http\Controllers\ApiTokenController::class, 'docs'])->name('api-docs');
    });

    // WhatsApp Warmer Routes
    Route::get('/warmer', [\App\Http\Controllers\WarmerController::class, 'index'])->name('warmer.index');
    Route::post('/warmer/toggle', [\App\Http\Controllers\WarmerController::class, 'toggle'])->name('warmer.toggle');
    Route::post('/warmer/pool/{id}', [\App\Http\Controllers\WarmerController::class, 'togglePool'])->name('warmer.pool.toggle');

    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/financials', [\App\Http\Controllers\AdminController::class, 'financials'])->name('financials');
        
        // Transaction CRUD
        Route::post('/transactions', [\App\Http\Controllers\TransactionController::class, 'store'])->name('transactions.store');
        Route::put('/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [\App\Http\Controllers\TransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::get('/transactions/{transaction}/edit', [\App\Http\Controllers\TransactionController::class, 'edit'])->name('transactions.edit');
        
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
        Route::post('/users', [\App\Http\Controllers\AdminController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [\App\Http\Controllers\AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('users.delete');
        Route::get('/plans', [\App\Http\Controllers\AdminController::class, 'plans'])->name('plans');
        Route::put('/plans/{id}', [\App\Http\Controllers\AdminController::class, 'updatePlan'])->name('plans.update');
        Route::get('/server-health', [\App\Http\Controllers\AdminController::class, 'server'])->name('server');
        Route::get('/settings', [\App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [\App\Http\Controllers\AdminController::class, 'saveSettings'])->name('settings.save');
        Route::get('/system-whatsapp', [\App\Http\Controllers\AdminController::class, 'systemWhatsapp'])->name('whatsapp');
    });
});

Route::get('/pricing', [PlanController::class, 'index'])->name('pricing');

Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook'])->name('payments.webhook');

// Legal & Support Routes
Route::get('/privacy-policy', function () {
    return Inertia::render('Legal/PrivacyPolicy');
})->name('privacy.policy');

Route::get('/terms', function () {
    return Inertia::render('Legal/TermsOfService');
})->name('terms.show');

Route::get('/support', function () {
    return Inertia::render('Support/Index');
})->name('support.index');

// FAQ Route
Route::get('/faq', function () {
    return Inertia::render('Faq', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('faq');