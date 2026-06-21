<?php

namespace App\Providers;

use App\Support\MailSettings;
use App\Support\SiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        MailSettings::applyIfConfigured();

        $this->configureRateLimiting();

        View::composer('*', function ($view) {
            $view->with('site', SiteSettings::siteObject());
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');

            return Limit::perMinute(5)->by(strtolower($email).'|'.$request->ip());
        });

        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));

        RateLimiter::for('comments', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute(10)->by($key);
        });

        RateLimiter::for('verification-resend', fn (Request $request) => Limit::perMinute(3)->by($request->user()?->id ?: $request->ip()));

        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()));
    }
}
