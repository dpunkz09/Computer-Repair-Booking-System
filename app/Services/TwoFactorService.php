<?php

namespace App\Services;

use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(
        private Google2FA $google2fa
    ) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();
    }

    public function otpAuthUrl(User $user, string $secret): string
    {
        $issuer = SiteSettings::getOrDefault('site_name');

        return $this->google2fa->getQRCodeUrl($issuer, $user->email, $secret);
    }

    public function verifyCode(User $user, string $code): bool
    {
        $secret = $this->decryptedSecret($user);

        if (! $secret) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, preg_replace('/\s+/', '', $code) ?? '');
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $this->decryptedRecoveryCodes($user);
        $normalized = Str::upper(trim($code));

        if (! in_array($normalized, $codes, true)) {
            return false;
        }

        $remaining = array_values(array_filter(
            $codes,
            fn (string $stored) => $stored !== $normalized
        ));

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($remaining)),
        ])->save();

        return true;
    }

    public function enable(User $user, string $secret, array $recoveryCodes): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
    }

    public function decryptedSecret(User $user): ?string
    {
        if (! $user->two_factor_secret) {
            return null;
        }

        return decrypt($user->two_factor_secret);
    }

    /**
     * @return array<int, string>
     */
    public function decryptedRecoveryCodes(User $user): array
    {
        if (! $user->two_factor_recovery_codes) {
            return [];
        }

        $decoded = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return is_array($decoded) ? $decoded : [];
    }
}
