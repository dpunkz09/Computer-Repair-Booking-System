<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    public function __construct(
        private TwoFactorService $twoFactor
    ) {}

    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('profile.edit');
        }

        $secret = $request->session()->get('two_factor.setup_secret');

        if (! $secret) {
            $secret = $this->twoFactor->generateSecret();
            $request->session()->put('two_factor.setup_secret', $secret);
        }

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'otpAuthUrl' => $this->twoFactor->otpAuthUrl($user, $secret),
        ]);
    }

    public function confirm(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $secret = $request->session()->get('two_factor.setup_secret');

        if (! $secret) {
            return redirect()->route('two-factor.setup');
        }

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);

        if (! $google2fa->verifyKey($secret, preg_replace('/\s+/', '', $validated['code']))) {
            return back()->withErrors(['code' => 'Invalid authentication code. Try again.']);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $this->twoFactor->enable($user, $secret, $recoveryCodes);

        $request->session()->forget('two_factor.setup_secret');
        $request->session()->put('two_factor.passed', $user->id);
        $request->session()->regenerate();

        return redirect()
            ->route('profile.edit')
            ->with('success', 'Two-factor authentication enabled.')
            ->with('two_factor_recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            return back()->withErrors(['password' => 'The password is incorrect.']);
        }

        if (SiteSettings::bool('require_admin_2fa')) {
            return back()->withErrors([
                'password' => 'Two-factor authentication is required for admin accounts and cannot be disabled.',
            ]);
        }

        $this->twoFactor->disable($user);
        $request->session()->forget('two_factor.passed');

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    public function showChallenge(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        if ($request->session()->get('two_factor.passed') === $user->id) {
            return redirect()->route('dashboard');
        }

        return view('auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request)
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'code' => 'required|string',
            'recovery_code' => 'nullable|string',
        ]);

        $verified = false;

        if (! empty($validated['recovery_code'])) {
            $verified = $this->twoFactor->verifyRecoveryCode($user, $validated['recovery_code']);
        } else {
            $verified = $this->twoFactor->verifyCode($user, $validated['code']);
        }

        if (! $verified) {
            return back()->withErrors(['code' => 'Invalid authentication or recovery code.']);
        }

        $request->session()->put('two_factor.passed', $user->id);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
