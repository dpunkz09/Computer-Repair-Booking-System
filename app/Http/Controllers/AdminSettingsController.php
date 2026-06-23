<?php

namespace App\Http\Controllers;

use App\Mail\SmtpTestMail;
use App\Models\User;
use App\Services\BrandingImageService;
use App\Services\HomepageImageService;
use App\Support\AdminAuditLog;
use App\Support\HomepageContent;
use App\Support\MailSettings;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class AdminSettingsController extends Controller
{
    public function __construct(
        private BrandingImageService $brandingImages,
        private HomepageImageService $homepageImages,
    ) {}

    public function index(Request $request)
    {
        $settings = SiteSettings::allForAdmin();
        $technicians = User::query()->where('role', 'technician')->orderBy('name')->get();
        $logoUrl = SiteSettings::logoUrl();
        $hasMailPassword = MailSettings::hasPassword();
        $homepage = HomepageContent::forAdmin();

        return view('admin.settings.index', [
            'settings' => $settings,
            'technicians' => $technicians,
            'logoUrl' => $logoUrl,
            'hasMailPassword' => $hasMailPassword,
            'homepage' => $homepage,
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
            'hero_image' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'remove_hero_image' => 'nullable|boolean',
            'homepage_show_features' => 'nullable|boolean',
            'homepage_features_title' => 'nullable|string|max:255',
            'homepage_features_subtitle' => 'nullable|string|max:500',
            'homepage_features' => 'nullable|array|max:8',
            'homepage_features.*.icon' => 'nullable|string|max:10',
            'homepage_features.*.title' => 'nullable|string|max:255',
            'homepage_features.*.description' => 'nullable|string|max:500',
            'homepage_show_steps' => 'nullable|boolean',
            'homepage_steps_title' => 'nullable|string|max:255',
            'homepage_steps_subtitle' => 'nullable|string|max:500',
            'homepage_steps' => 'nullable|array|max:6',
            'homepage_steps.*.title' => 'nullable|string|max:255',
            'homepage_steps.*.description' => 'nullable|string|max:500',
            'homepage_image_sections' => 'nullable|array|max:4',
            'homepage_image_sections.*.title' => 'nullable|string|max:255',
            'homepage_image_sections.*.subtitle' => 'nullable|string|max:500',
            'homepage_image_sections.*.image_path' => 'nullable|string|max:500',
            'homepage_section_images.*' => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
            'remove_homepage_section_images.*' => 'nullable|boolean',
            'homepage_show_cta' => 'nullable|boolean',
            'homepage_cta_title' => 'nullable|string|max:255',
            'homepage_cta_subtitle' => 'nullable|string|max:500',
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

        $this->updateHomepageContent($request);

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

    private function updateHomepageContent(Request $request): void
    {
        $current = HomepageContent::get();
        $heroPath = $current['hero_image_path'] ?? null;

        if ($request->boolean('remove_hero_image')) {
            $this->homepageImages->deletePath($heroPath);
            $heroPath = null;
        } elseif ($request->hasFile('hero_image')) {
            $this->homepageImages->deletePath($heroPath);
            $heroPath = $this->homepageImages->storeHero($request->file('hero_image'));
        }

        $imageSections = [];
        $sectionsInput = $request->input('homepage_image_sections', []);

        if (is_array($sectionsInput)) {
            foreach ($sectionsInput as $index => $section) {
                if (! is_array($section)) {
                    continue;
                }

                $existingPath = $section['image_path'] ?? null;
                $existingPath = is_string($existingPath) && $existingPath !== '' ? $existingPath : null;

                if ($request->boolean("remove_homepage_section_images.{$index}")) {
                    $this->homepageImages->deletePath($existingPath);
                    $existingPath = null;
                }

                if ($request->hasFile("homepage_section_images.{$index}")) {
                    $this->homepageImages->deletePath($existingPath);
                    $existingPath = $this->homepageImages->storeSection(
                        $request->file("homepage_section_images.{$index}"),
                        (int) $index
                    );
                }

                $imageSections[] = [
                    'title' => (string) ($section['title'] ?? ''),
                    'subtitle' => (string) ($section['subtitle'] ?? ''),
                    'image_path' => $existingPath,
                ];
            }
        }

        HomepageContent::save([
            'hero_image_path' => $heroPath,
            'show_features' => $request->boolean('homepage_show_features'),
            'features_title' => $request->input('homepage_features_title', ''),
            'features_subtitle' => $request->input('homepage_features_subtitle', ''),
            'features' => $request->input('homepage_features', []),
            'show_steps' => $request->boolean('homepage_show_steps'),
            'steps_title' => $request->input('homepage_steps_title', ''),
            'steps_subtitle' => $request->input('homepage_steps_subtitle', ''),
            'steps' => $request->input('homepage_steps', []),
            'image_sections' => $imageSections,
            'show_cta' => $request->boolean('homepage_show_cta'),
            'cta_title' => $request->input('homepage_cta_title', ''),
            'cta_subtitle' => $request->input('homepage_cta_subtitle', ''),
        ]);
    }
}
