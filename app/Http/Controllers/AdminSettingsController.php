<?php

namespace App\Http\Controllers;

use App\Mail\SmtpTestMail;
use App\Models\User;
use App\Services\BrandingImageService;
use App\Support\AdminAuditLog;
use App\Support\MailSettings;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class AdminSettingsController extends Controller
{
    public function __construct(
        private BrandingImageService $brandingImages
    ) {}

    public function index(Request $request)
    {
        $settings = SiteSettings::allForAdmin();
        $technicians = User::query()->where('role', 'technician')->orderBy('name')->get();
        $logoUrl = SiteSettings::logoUrl();
        $hasMailPassword = MailSettings::hasPassword();

        return view('admin.settings.index', [
            'settings' => $settings,
            'technicians' => $technicians,
            'logoUrl' => $logoUrl,
            'hasMailPassword' => $hasMailPassword,
            'readOnly' => ! $request->user()?->canManageSystemSettings(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:255',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'seo_keywords' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'support_hours' => 'nullable|string|max:255',
            'footer_text' => 'nullable|string|max:500',
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'welcome_badge' => 'nullable|string|max:255',
            'welcome_headline' => 'nullable|string|max:255',
            'welcome_subheadline' => 'nullable|string|max:500',
            'auto_assign_enabled' => 'nullable|boolean',
            'auto_assign_technician_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('role', 'technician'),
            ],
            'mail_enabled' => 'nullable|boolean',
            'mail_host' => 'nullable|required_if:mail_enabled,1|string|max:255',
            'mail_port' => 'nullable|required_if:mail_enabled,1|integer|between:1,65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'mail_from_address' => 'nullable|required_if:mail_enabled,1|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
            'require_email_verification' => 'nullable|boolean',
            'require_admin_2fa' => 'nullable|boolean',
            'privacy_policy_title' => 'nullable|string|max:255',
            'privacy_policy_content' => 'nullable|string|max:50000',
            'terms_of_service_title' => 'nullable|string|max:255',
            'terms_of_service_content' => 'nullable|string|max:50000',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'remove_logo' => 'nullable|boolean',
        ]);

        if ($request->boolean('remove_logo')) {
            $this->brandingImages->deleteLogo();
        } elseif ($request->hasFile('logo')) {
            $this->brandingImages->storeLogo($request->file('logo'));
        }

        if ($request->filled('mail_password')) {
            MailSettings::setPassword($request->input('mail_password'));
        }

        SiteSettings::setMany([
            'site_name' => $validated['site_name'],
            'site_tagline' => $validated['site_tagline'] ?? '',
            'seo_title' => $validated['seo_title'] ?? '',
            'seo_description' => $validated['seo_description'] ?? '',
            'seo_keywords' => $validated['seo_keywords'] ?? '',
            'contact_email' => $validated['contact_email'] ?? '',
            'contact_phone' => $validated['contact_phone'] ?? '',
            'support_hours' => $validated['support_hours'] ?? '',
            'footer_text' => $validated['footer_text'] ?? '',
            'primary_color' => $validated['primary_color'],
            'welcome_badge' => $validated['welcome_badge'] ?? '',
            'welcome_headline' => $validated['welcome_headline'] ?? '',
            'welcome_subheadline' => $validated['welcome_subheadline'] ?? '',
            'auto_assign_enabled' => $request->boolean('auto_assign_enabled'),
            'auto_assign_technician_id' => $validated['auto_assign_technician_id'] ?? '',
            'mail_enabled' => $request->boolean('mail_enabled'),
            'mail_host' => $validated['mail_host'] ?? '',
            'mail_port' => $validated['mail_port'] ?? '587',
            'mail_username' => $validated['mail_username'] ?? '',
            'mail_encryption' => $validated['mail_encryption'] ?? 'tls',
            'mail_from_address' => $validated['mail_from_address'] ?? '',
            'mail_from_name' => $validated['mail_from_name'] ?? '',
            'require_email_verification' => $request->boolean('require_email_verification'),
            'require_admin_2fa' => $request->boolean('require_admin_2fa'),
            'privacy_policy_title' => $validated['privacy_policy_title'] ?? 'Privacy Policy',
            'privacy_policy_content' => $validated['privacy_policy_content'] ?? '',
            'terms_of_service_title' => $validated['terms_of_service_title'] ?? 'Terms of Service',
            'terms_of_service_content' => $validated['terms_of_service_content'] ?? '',
        ]);

        AdminAuditLog::record('settings.update', [
            'site_name' => $validated['site_name'],
            'auto_assign_enabled' => $request->boolean('auto_assign_enabled'),
            'mail_enabled' => $request->boolean('mail_enabled'),
            'require_email_verification' => $request->boolean('require_email_verification'),
            'require_admin_2fa' => $request->boolean('require_admin_2fa'),
        ]);

        return back()->with('success', 'Site settings saved successfully.');
    }

    public function sendTestMail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email|max:255',
        ]);

        if (! MailSettings::isConfigured()) {
            return back()->with('error', 'Enable SMTP and save host, from address, and credentials before sending a test email.');
        }

        MailSettings::applyIfConfigured();

        $siteName = SiteSettings::getOrDefault('site_name');

        try {
            Mail::to($request->input('test_email'))->queue(new SmtpTestMail($siteName));

            return back()->with('success', 'Test email queued for ' . $request->input('test_email') . '. Check your inbox shortly.');
        } catch (Throwable $e) {
            return back()->with('error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
