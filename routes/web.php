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

// System Utility Route
Route::get('/system/force-clear', function() {
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return 'System Cache Cleared via Web Route';
});


Route::get('/system/reset-conversation/{id}', function($id) {
    $conversation = \App\Models\Conversation::findOrFail($id);
    $count = $conversation->messages()->count();
    $conversation->messages()->delete();
    $conversation->update([
        'message_count' => 0,
        'last_customer_message_at' => null,
        'last_ai_reply_at' => null,
    ]);
    return "✅ Deleted {$count} messages from conversation {$id}. Start fresh now!";
});

Route::get('/system/test-openai', function() {
    $key = \App\Models\Setting::where('key', 'openai_api_key')->first();

    echo "<h1>OpenAI & WhatsApp Configuration Check</h1><pre>";

    // Check WhatsApp Server URL
    $waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value');
    echo "=== WhatsApp Server ===\n";
    echo "Configured URL: " . ($waServerUrl ?: 'NOT SET (using default)') . "\n";
    echo "Should be: http://localhost:3005\n";
    if ($waServerUrl !== 'http://localhost:3005' && $waServerUrl !== 'http://127.0.0.1:3005') {
        echo "❌ WRONG PORT! This is why AI replies don't send!\n\n";
        echo "Fixing now...\n";
        \App\Models\Setting::updateOrCreate(
            ['key' => 'wa_server_url'],
            ['value' => 'http://localhost:3005']
        );
        echo "✅ Fixed! Now set to http://localhost:3005\n\n";
    } else {
        echo "✅ Correct!\n\n";
    }

    echo "=== OpenAI API ===\n";
    if ($key) {
        $value = $key->value;
        echo "✅ OpenAI API Key is SAVED in database\n";
        echo "Key starts with: " . substr($value, 0, 20) . "...\n";
        echo "Key length: " . strlen($value) . " characters\n\n";

        // Test if it's valid format
        if (str_starts_with($value, 'sk-proj-')) {
            echo "✅ Key format looks correct (sk-proj-...)\n\n";
        } else {
            echo "⚠️  Key format might be invalid\n\n";
        }

        // Check AI reply enabled
        $aiEnabled = \App\Models\Setting::where('key', 'ai_reply_enabled')->value('value');
        echo "AI Reply Enabled: " . ($aiEnabled === '1' ? 'YES ✅' : 'NO ❌') . "\n\n";

        // Test OpenAI connection
        echo "Testing OpenAI API connection...\n";
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $value,
            ])->get('https://api.openai.com/v1/models');

            if ($response->successful()) {
                echo "✅ OpenAI API Key is VALID and working!\n";
            } else {
                echo "❌ OpenAI API returned error: " . $response->status() . "\n";
                echo "Response: " . $response->body() . "\n";
            }
        } catch (\Exception $e) {
            echo "❌ Connection failed: " . $e->getMessage() . "\n";
        }

    } else {
        echo "❌ OpenAI API Key is NOT saved in database\n";
        echo "Please go to Admin > Global Settings and save it again\n";
    }

    echo "</pre>";
});

Route::get('/system/wa-debug', function() {
    $target = "127.0.0.1";
    $port = 3005;
    
    echo "<h1>Built-in WhatsApp Debugger (Port 3005)</h1><pre>";
    echo "Checking 127.0.0.1:3005...\n";
    $fp = @fsockopen($target, $port, $errno, $errstr, 2);
    if ($fp) {
        echo "✅ Port is OPEN\n";
        fclose($fp);
    } else {
        echo "❌ Port is CLOSED ($errstr)\n";
    }
    
    echo "\nTrying health check...\n";
    try {
        $res = \Illuminate\Support\Facades\Http::timeout(2)->get("http://127.0.0.1:3005/health");
        echo "Response: " . ($res->successful() ? "✅ " . $res->body() : "❌ Error " . $res->status());
    } catch (\Exception $e) {
        echo "❌ Health check failed: " . $e->getMessage();
    }
    echo "</pre>";
});

Route::get('/system/wa-purge', function() {
    $waServerUrl = \App\Models\Setting::where('key', 'wa_server_url')->value('value') 
                   ?? env('WA_SERVER_URL', 'http://127.0.0.1:3005');
    $waServerUrl = rtrim($waServerUrl, '/');
    
    echo "<h1>Emergency WhatsApp Purge</h1><pre>";
    echo "Calling Node server /cleanup-all...\n";
    try {
        $res = \Illuminate\Support\Facades\Http::get("{$waServerUrl}/cleanup-all");
        echo "Response: " . ($res->successful() ? "✅ " . $res->body() : "❌ Error " . $res->status());
    } catch (\Exception $e) {
        echo "❌ Failed to reach Node server: " . $e->getMessage();
    }
    echo "</pre>";
});

Route::get('/system/wa-reset/{id}', function($id) {
    $number = \App\Models\WhatsappNumber::findOrFail($id);
    $number->update([
        'status' => 'disconnected',
        'qr_code' => null,
    ]);
    return "Device {$id} has been reset to disconnected status.";
});

Route::get('/system/test-ticket-notify', function() {
    $admin = \App\Models\User::where('email', 'admin@blaster.com')->first();
    if (!$admin) return 'Admin not found';

    $device = $admin->whatsappNumbers()->where('status', 'connected')->first();
    if (!$device) return 'No connected device';

    $adminMobile = \App\Models\Setting::where('key', 'admin_mobile_number')->value('value');

    return [
        'admin_id' => $admin->id,
        'device_id' => $device->id,
        'device_phone_number' => $device->phone_number,
        'admin_mobile_setting' => $adminMobile,
        'will_send_to' => $adminMobile ?: $device->phone_number,
    ];
});

Route::get('/system/admin-notifications', function() {
    $service = new \App\Services\AdminNotificationService();

    $pending = \App\Models\AdminNotification::pending()->latest()->limit(10)->get();
    $failed = \App\Models\AdminNotification::failed()->latest()->limit(10)->get();
    $recent = \App\Models\AdminNotification::whereNotNull('sent_at')->latest('sent_at')->limit(10)->get();

    return [
        'stats' => [
            'pending_count' => $service->getPendingCount(),
            'failed_count' => $service->getFailedCount(),
        ],
        'pending' => $pending,
        'failed' => $failed,
        'recent_sent' => $recent,
    ];
});

Route::get('/system/process-admin-notifications', function() {
    $service = new \App\Services\AdminNotificationService();
    $result = $service->sendPendingNotifications();

    return [
        'result' => $result,
        'stats' => [
            'pending_count' => $service->getPendingCount(),
            'failed_count' => $service->getFailedCount(),
        ],
    ];
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // EMERGENCY ROUTE for Cancellation
    Route::post('/cancel-plan-action', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel_action');

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

    // Reminders
    Route::get('/reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
    Route::get('/reminders/create', [\App\Http\Controllers\ReminderController::class, 'create'])->name('reminders.create');
    Route::post('/reminders', [\App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
    Route::get('/reminders/{id}/edit', [\App\Http\Controllers\ReminderController::class, 'edit'])->name('reminders.edit');
    Route::put('/reminders/{id}', [\App\Http\Controllers\ReminderController::class, 'update'])->name('reminders.update');
    Route::delete('/reminders/{id}', [\App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');

    // Google Calendar
    Route::middleware(['subscription.limit:google_calendar'])->group(function () {
        Route::get('/google-calendar', [\App\Http\Controllers\GoogleCalendarController::class, 'index'])->name('google-calendar.index');
        Route::get('/google-calendar/connect', [\App\Http\Controllers\GoogleCalendarController::class, 'connect'])->name('google-calendar.connect');
        Route::get('/google-calendar/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback'])->name('google-calendar.callback');
        Route::post('/google-calendar/disconnect', [\App\Http\Controllers\GoogleCalendarController::class, 'disconnect'])->name('google-calendar.disconnect');
        Route::post('/google-calendar/sync', [\App\Http\Controllers\GoogleCalendarController::class, 'sync'])->name('google-calendar.sync');
    });

    // AI Playbooks & Conversations
    Route::middleware(['subscription.limit:auto_reply'])->group(function () {
        Route::get('/playbooks', [\App\Http\Controllers\PlaybookController::class, 'index'])->name('playbooks.index');
        Route::get('/playbooks/create', [\App\Http\Controllers\PlaybookController::class, 'create'])->name('playbooks.create');
        Route::post('/playbooks', [\App\Http\Controllers\PlaybookController::class, 'store'])->name('playbooks.store');
        Route::get('/playbooks/{id}/edit', [\App\Http\Controllers\PlaybookController::class, 'edit'])->name('playbooks.edit');
        Route::put('/playbooks/{id}', [\App\Http\Controllers\PlaybookController::class, 'update'])->name('playbooks.update');
        Route::delete('/playbooks/{id}', [\App\Http\Controllers\PlaybookController::class, 'destroy'])->name('playbooks.destroy');
        Route::post('/playbooks/assign', [\App\Http\Controllers\PlaybookController::class, 'assignToNumber'])->name('playbooks.assign');
        Route::get('/playbooks/{id}/versions', [\App\Http\Controllers\PlaybookController::class, 'versions'])->name('playbooks.versions');
        Route::get('/playbooks/{id}/versions/{versionId}', [\App\Http\Controllers\PlaybookController::class, 'showVersion'])->name('playbooks.version.show');
        Route::post('/playbooks/{id}/versions/{versionId}/restore', [\App\Http\Controllers\PlaybookController::class, 'restoreVersion'])->name('playbooks.version.restore');

        Route::get('/conversations', [\App\Http\Controllers\ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/{id}', [\App\Http\Controllers\ConversationController::class, 'show'])->name('conversations.show');
        Route::put('/conversations/{id}/mode', [\App\Http\Controllers\ConversationController::class, 'toggleMode'])->name('conversations.toggle-mode');
    });

    Route::post('/subscription/cancel-plan', [\App\Http\Controllers\SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    // Fallback for potentially cached frontends - no name to avoid conflict
    Route::post('/subscription/cancel', [\App\Http\Controllers\SubscriptionController::class, 'cancel']); 
    Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'show'])->name('subscription.show');

    Route::middleware(['subscription.limit:api_access'])->group(function () {
        // API Token Management
        Route::get('/user/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/user/api-tokens', [\App\Http\Controllers\ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::get('/user/api-tokens/{tokenId}', [\App\Http\Controllers\ApiTokenController::class, 'show'])->name('api-tokens.show');
        Route::delete('/user/api-tokens/{tokenId}', [\App\Http\Controllers\ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');
        
        // API Documentation
        Route::get('/api-docs', [\App\Http\Controllers\ApiTokenController::class, 'docs'])->name('api-docs');
    });

    // Tickets
    Route::get('/tickets', [\App\Http\Controllers\TicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/create', [\App\Http\Controllers\TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [\App\Http\Controllers\TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [\App\Http\Controllers\TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{id}/reply', [\App\Http\Controllers\TicketController::class, 'reply'])->name('tickets.reply');

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
        Route::get('/tickets', [\App\Http\Controllers\AdminController::class, 'tickets'])->name('tickets');
        Route::get('/tickets/{id}', [\App\Http\Controllers\AdminController::class, 'ticketShow'])->name('tickets.show');
        Route::post('/tickets/{id}/reply', [\App\Http\Controllers\AdminController::class, 'ticketReply'])->name('tickets.reply');
        Route::delete('/tickets/{id}', [\App\Http\Controllers\AdminController::class, 'ticketDelete'])->name('tickets.destroy');
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

// Public Developer Documentation
Route::get('/docs', function () {
    return Inertia::render('Docs', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'baseUrl' => rtrim(config('app.url'), '/'),
    ]);
})->name('docs');