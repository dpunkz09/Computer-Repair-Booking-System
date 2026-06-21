<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class MailSettings
{
    public static function applyIfConfigured(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            if (! SiteSettings::bool('mail_enabled')) {
                return;
            }

            $host = SiteSettings::get('mail_host');

            if (! $host) {
                return;
            }

            $port = (int) (SiteSettings::get('mail_port') ?: 587);
            $encryption = SiteSettings::get('mail_encryption') ?: 'tls';
            $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';

            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.transport', 'smtp');
            Config::set('mail.mailers.smtp.scheme', $scheme);
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', SiteSettings::get('mail_username') ?: null);
            Config::set('mail.mailers.smtp.password', self::decryptedPassword());

            $fromAddress = SiteSettings::get('mail_from_address') ?: SiteSettings::getOrDefault('contact_email');
            $fromName = SiteSettings::get('mail_from_name') ?: SiteSettings::getOrDefault('site_name');

            if ($fromAddress) {
                Config::set('mail.from.address', $fromAddress);
                Config::set('mail.from.name', $fromName);
            }
        } catch (\Throwable) {
            // Database may be unavailable during install/migrate.
        }
    }

    public static function hasPassword(): bool
    {
        if (! Schema::hasTable('settings')) {
            return false;
        }

        $value = Setting::query()->where('key', 'mail_password')->value('value');

        return $value !== null && $value !== '';
    }

    public static function setPassword(string $plainPassword): void
    {
        SiteSettings::set('mail_password', Crypt::encryptString($plainPassword));
    }

    public static function decryptedPassword(): ?string
    {
        if (! Schema::hasTable('settings')) {
            return null;
        }

        $encrypted = Setting::query()->where('key', 'mail_password')->value('value');

        if ($encrypted === null || $encrypted === '') {
            return null;
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function isConfigured(): bool
    {
        return SiteSettings::bool('mail_enabled')
            && (bool) SiteSettings::get('mail_host')
            && (bool) (SiteSettings::get('mail_from_address') ?: SiteSettings::get('contact_email'));
    }

    public static function canSendTransactionalEmail(): bool
    {
        self::applyIfConfigured();

        $mailer = config('mail.default');

        if (in_array($mailer, ['log'], true)) {
            return false;
        }

        return ! empty(config('mail.from.address'));
    }
}
