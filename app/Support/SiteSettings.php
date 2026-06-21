<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SiteSettings
{
    public const CACHE_KEY = 'site_settings';

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'site_name' => 'ComTech Repair',
            'site_tagline' => 'Professional Computer Repair & Support',
            'seo_title' => 'ComTech Repair — Computer Repair Booking',
            'seo_description' => 'Book computer repair services online, track tickets, and communicate with technicians.',
            'seo_keywords' => 'computer repair, laptop repair, tech support, booking',
            'contact_email' => '',
            'contact_phone' => '',
            'support_hours' => 'Mon–Fri, 9:00 AM – 6:00 PM',
            'footer_text' => '',
            'primary_color' => '#2563eb',
            'welcome_badge' => 'Computer Repair Booking System',
            'welcome_headline' => 'Fast, Reliable Repairs — Booked Online',
            'welcome_subheadline' => 'Submit repair requests, track ticket status in real time, and communicate directly with our technicians — all in one place.',
            'logo_path' => null,
            'auto_assign_enabled' => false,
            'auto_assign_technician_id' => null,
            'mail_enabled' => false,
            'mail_host' => '',
            'mail_port' => '587',
            'mail_username' => '',
            'mail_encryption' => 'tls',
            'mail_from_address' => '',
            'mail_from_name' => '',
            'require_email_verification' => false,
            'require_admin_2fa' => false,
            'privacy_policy_title' => 'Privacy Policy',
            'privacy_policy_content' => '',
            'terms_of_service_title' => 'Terms of Service',
            'terms_of_service_content' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = Setting::query()->pluck('value', 'key')->toArray();

            return array_merge(self::defaults(), $stored);
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        if (array_key_exists($key, $all)) {
            return self::castValue($key, $all[$key]);
        }

        return $default ?? self::defaults()[$key] ?? null;
    }

    public static function getOrDefault(string $key): mixed
    {
        $stored = Setting::query()->where('key', $key)->value('value');

        if ($stored === null || $stored === '') {
            return self::defaults()[$key] ?? null;
        }

        return self::castValue($key, $stored);
    }

    /**
     * @return array<string, mixed>
     */
    public static function allForAdmin(): array
    {
        $defaults = self::defaults();
        $stored = Setting::query()->pluck('value', 'key')->toArray();
        $result = $defaults;

        foreach ($stored as $key => $value) {
            if (! array_key_exists($key, $defaults)) {
                continue;
            }

            $result[$key] = match ($key) {
                'auto_assign_enabled', 'mail_enabled', 'require_email_verification', 'require_admin_2fa' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'auto_assign_technician_id' => $value !== '' && $value !== null ? (int) $value : null,
                default => $value,
            };
        }

        return $result;
    }

    public static function siteObject(): object
    {
        return (object) [
            'name' => self::getOrDefault('site_name'),
            'tagline' => self::getOrDefault('site_tagline'),
            'logo_url' => self::logoUrl(),
            'seo_title' => self::getOrDefault('seo_title'),
            'seo_description' => self::getOrDefault('seo_description'),
            'seo_keywords' => self::getOrDefault('seo_keywords'),
            'contact_email' => self::get('contact_email'),
            'contact_phone' => self::get('contact_phone'),
            'support_hours' => self::getOrDefault('support_hours'),
            'footer_text' => self::get('footer_text'),
            'primary_color' => self::getOrDefault('primary_color'),
            'welcome_badge' => self::getOrDefault('welcome_badge'),
            'welcome_headline' => self::getOrDefault('welcome_headline'),
            'welcome_subheadline' => self::getOrDefault('welcome_subheadline'),
            'privacy_policy_title' => self::getOrDefault('privacy_policy_title'),
            'privacy_policy_content' => self::get('privacy_policy_content'),
            'terms_of_service_title' => self::getOrDefault('terms_of_service_title'),
            'terms_of_service_content' => self::get('terms_of_service_content'),
            'has_privacy_policy' => trim((string) self::get('privacy_policy_content', '')) !== '',
            'has_terms_of_service' => trim((string) self::get('terms_of_service_content', '')) !== '',
        ];
    }

    public static function set(string $key, mixed $value): void
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value === null) {
            $value = '';
        } else {
            $value = (string) $value;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }

        Cache::forget(self::CACHE_KEY);
    }

    public static function bool(string $key): bool
    {
        return filter_var(self::get($key), FILTER_VALIDATE_BOOLEAN);
    }

    public static function logoUrl(): ?string
    {
        $path = self::get('logo_path');

        return ($path && $path !== '') ? asset('storage/' . $path) : null;
    }

    public static function applyAutoAssign(Ticket $ticket): void
    {
        if (! self::bool('auto_assign_enabled')) {
            return;
        }

        $technicianId = self::get('auto_assign_technician_id');

        if (! $technicianId) {
            return;
        }

        $technician = User::query()
            ->where('id', $technicianId)
            ->where('role', 'technician')
            ->first();

        if (! $technician) {
            return;
        }

        $ticket->update([
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);
    }

    private static function castValue(string $key, mixed $value): mixed
    {
        if (in_array($key, ['auto_assign_enabled', 'mail_enabled', 'require_email_verification', 'require_admin_2fa'], true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if (in_array($key, ['privacy_policy_content', 'terms_of_service_content'], true)) {
            return $value === '' ? '' : $value;
        }

        if ($key === 'auto_assign_technician_id') {
            return $value !== '' && $value !== null ? (int) $value : null;
        }

        if ($value === '') {
            return self::defaults()[$key] ?? $value;
        }

        return $value;
    }
}
