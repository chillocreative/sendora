<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\Event::listen(
            \Illuminate\Auth\Events\Registered::class,
            \App\Listeners\SendWelcomeEmail::class,
        );

        // Register WhatsApp Number observer for admin notifications
        \App\Models\WhatsappNumber::observe(\App\Observers\WhatsappNumberObserver::class);
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::all()->pluck('value', 'key');
                
                if (isset($settings['timezone'])) {
                    config(['app.timezone' => $settings['timezone']]);
                    date_default_timezone_set($settings['timezone']);
                }

                if (isset($settings['app_name'])) {
                    config(['app.name' => $settings['app_name']]);
                }

                // Inject Mail Infrastructure (Brevo)
                if (isset($settings['mail_host'])) {
                    config([
                        'mail.default' => 'smtp',
                        'mail.mailers.smtp.host' => $settings['mail_host'],
                        'mail.mailers.smtp.port' => $settings['mail_port'] ?? 587,
                        'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
                        'mail.mailers.smtp.password' => $settings['mail_password'] ?? null,
                        'mail.mailers.smtp.encryption' => $settings['mail_encryption'] ?? 'tls',
                        'mail.from.address' => $settings['mail_from_address'] ?? 'hello@sendora.com',
                        'mail.from.name' => $settings['mail_from_name'] ?? $settings['app_name'] ?? 'Sendora',
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Settings Bootstrap Error: ' . $e->getMessage());
        }
    }
}
