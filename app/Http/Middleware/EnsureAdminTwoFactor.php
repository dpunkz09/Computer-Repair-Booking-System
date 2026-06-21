<?php

namespace App\Http\Middleware;

use App\Support\SiteSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    /**
     * @var array<int, string>
     */
    private const SETUP_BYPASS_ROUTES = [
        'profile.edit',
        'profile.update',
        'profile.picture.store',
        'profile.picture.destroy',
        'two-factor.setup',
        'two-factor.confirm',
        'two-factor.disable',
        'verification.notice',
        'verification.verify',
        'verification.send',
        'logout',
    ];

    /**
     * @var array<int, string>
     */
    private const CHALLENGE_BYPASS_ROUTES = [
        'two-factor.challenge',
        'two-factor.verify',
        'logout',
    ];

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'admin') {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($user->hasTwoFactorEnabled() && $request->session()->get('two_factor.passed') !== $user->id) {
            if (! $this->routeIs($routeName, self::CHALLENGE_BYPASS_ROUTES)) {
                return redirect()->route('two-factor.challenge');
            }

            return $next($request);
        }

        if (SiteSettings::bool('require_admin_2fa') && ! $user->hasTwoFactorEnabled()) {
            if (! $this->routeIs($routeName, self::SETUP_BYPASS_ROUTES)) {
                return redirect()
                    ->route('profile.edit')
                    ->with('error', 'Two-factor authentication is required for admin accounts. Enable it below to continue.');
            }
        }

        return $next($request);
    }

    /**
     * @param  array<int, string>  $routes
     */
    private function routeIs(?string $routeName, array $routes): bool
    {
        return $routeName && in_array($routeName, $routes, true);
    }
}
