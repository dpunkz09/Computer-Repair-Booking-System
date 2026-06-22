@extends('layouts.app')

@section('title', 'Site Settings')

@section('content')
<div class="mb-8">
    <p class="text-sm font-medium text-indigo-600">{{ now()->format('l, F j') }}</p>
    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">Site Settings</h1>
    <p class="mt-1 text-gray-500">
        @if($readOnly ?? false)
            View-only access to branding, SEO, email, security, and automation settings.
        @else
            Customize branding, SEO, contact info, email delivery, and ticket automation.
        @endif
    </p>
</div>

@if($readOnly ?? false)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <strong>Read-only:</strong> Demo admin accounts cannot change site settings. Contact a full administrator to make updates.
    </div>
@endif

@if($errors->any())
    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800">
        <ul class="list-inside list-disc text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data"
      x-data="{ tab: 'branding' }">
    @csrf
    @method('PUT')

    <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-4">
        @foreach(['branding' => 'Branding', 'seo' => 'SEO', 'homepage' => 'Homepage', 'contact' => 'Contact', 'email' => 'Email / SMTP', 'tickets' => 'Tickets', 'security' => 'Security', 'legal' => 'Legal'] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'"
                class="rounded-lg px-4 py-2 text-sm font-semibold border border-gray-200 transition">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <fieldset @disabled($readOnly ?? false) class="min-w-0 border-0 p-0 m-0">

    <div class="space-y-6">
        {{-- Branding --}}
        <div x-show="tab === 'branding'" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">Branding</h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1.5">Site Name</label>
                    <input type="text" name="site_name" id="site_name" value="{{ old('site_name', $settings['site_name']) }}" required
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="site_tagline" class="block text-sm font-medium text-gray-700 mb-1.5">Tagline</label>
                    <input type="text" name="site_tagline" id="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-gray-700 mb-1.5">Primary Color</label>
                    <input type="color" name="primary_color" id="primary_color" value="{{ old('primary_color', $settings['primary_color']) }}"
                        class="h-11 w-full max-w-[120px] rounded-lg border border-gray-200 cursor-pointer">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <label class="block text-sm font-medium text-gray-700 mb-3">Site Logo</label>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                    <div class="flex h-24 min-w-[160px] items-center justify-center rounded-xl border border-gray-200 bg-gray-50 p-4">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="Current logo" class="max-h-20 max-w-[200px] object-contain">
                        @else
                            <span class="text-sm text-gray-400">No logo uploaded</span>
                        @endif
                    </div>
                    <div class="flex-1 space-y-3">
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif"
                            class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500">PNG recommended. Resized to max 320×120 and compressed automatically.</p>
                        @if($logoUrl)
                            <label class="flex items-center gap-2 text-sm text-rose-600">
                                <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
                                Remove current logo
                            </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- SEO --}}
        <div x-show="tab === 'seo'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">SEO & Meta Tags</h2>
            <div>
                <label for="seo_title" class="block text-sm font-medium text-gray-700 mb-1.5">Meta Title</label>
                <input type="text" name="seo_title" id="seo_title" value="{{ old('seo_title', $settings['seo_title']) }}"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="seo_description" class="block text-sm font-medium text-gray-700 mb-1.5">Meta Description</label>
                <textarea name="seo_description" id="seo_description" rows="3"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('seo_description', $settings['seo_description']) }}</textarea>
            </div>
            <div>
                <label for="seo_keywords" class="block text-sm font-medium text-gray-700 mb-1.5">Meta Keywords</label>
                <input type="text" name="seo_keywords" id="seo_keywords" value="{{ old('seo_keywords', $settings['seo_keywords']) }}"
                    placeholder="repair, laptop, computer"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        {{-- Homepage --}}
        <div x-show="tab === 'homepage'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">Homepage Content</h2>
            <div>
                <label for="welcome_badge" class="block text-sm font-medium text-gray-700 mb-1.5">Hero Badge Text</label>
                <input type="text" name="welcome_badge" id="welcome_badge" value="{{ old('welcome_badge', $settings['welcome_badge']) }}"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="welcome_headline" class="block text-sm font-medium text-gray-700 mb-1.5">Hero Headline</label>
                <input type="text" name="welcome_headline" id="welcome_headline" value="{{ old('welcome_headline', $settings['welcome_headline']) }}"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="welcome_subheadline" class="block text-sm font-medium text-gray-700 mb-1.5">Hero Subheadline</label>
                <textarea name="welcome_subheadline" id="welcome_subheadline" rows="3"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('welcome_subheadline', $settings['welcome_subheadline']) }}</textarea>
            </div>
        </div>

        {{-- Contact --}}
        <div x-show="tab === 'contact'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">Contact & Footer</h2>
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1.5">Contact Email</label>
                    <input type="email" name="contact_email" id="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="contact_phone" class="block text-sm font-medium text-gray-700 mb-1.5">Contact Phone</label>
                    <input type="text" name="contact_phone" id="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label for="support_hours" class="block text-sm font-medium text-gray-700 mb-1.5">Support Hours</label>
                <input type="text" name="support_hours" id="support_hours" value="{{ old('support_hours', $settings['support_hours']) }}"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="footer_text" class="block text-sm font-medium text-gray-700 mb-1.5">Footer Text</label>
                <input type="text" name="footer_text" id="footer_text" value="{{ old('footer_text', $settings['footer_text']) }}"
                    placeholder="Leave blank for default copyright"
                    class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        {{-- Email / SMTP --}}
        <div x-show="tab === 'email'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Email / SMTP</h2>
                <p class="text-sm text-gray-500 mt-1">Configure outgoing mail for password reset links and system notifications.</p>
            </div>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="mail_enabled" value="1"
                    {{ old('mail_enabled', $settings['mail_enabled']) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Enable custom SMTP settings</span>
            </label>

            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                When disabled, Laravel uses your <code class="rounded bg-blue-100 px-1">.env</code> mail settings (default: log driver). Enable this to send real emails for password reset.
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <label for="mail_host" class="block text-sm font-medium text-gray-700 mb-1.5">SMTP Host</label>
                    <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host', $settings['mail_host']) }}"
                        placeholder="smtp.gmail.com"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="mail_port" class="block text-sm font-medium text-gray-700 mb-1.5">SMTP Port</label>
                    <input type="number" name="mail_port" id="mail_port" value="{{ old('mail_port', $settings['mail_port']) }}"
                        placeholder="587"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="mail_username" class="block text-sm font-medium text-gray-700 mb-1.5">SMTP Username</label>
                    <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username', $settings['mail_username']) }}"
                        placeholder="your@email.com"
                        autocomplete="off"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="mail_password" class="block text-sm font-medium text-gray-700 mb-1.5">SMTP Password</label>
                    <input type="password" name="mail_password" id="mail_password" value=""
                        placeholder="{{ $hasMailPassword ? 'Leave blank to keep current password' : 'App password or SMTP password' }}"
                        autocomplete="new-password"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @if($hasMailPassword)
                        <p class="mt-1 text-xs text-emerald-600">A password is saved. Leave this field empty to keep it.</p>
                    @endif
                </div>
                <div>
                    <label for="mail_encryption" class="block text-sm font-medium text-gray-700 mb-1.5">Encryption</label>
                    <select name="mail_encryption" id="mail_encryption"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach(['tls' => 'TLS (port 587 — recommended)', 'ssl' => 'SSL (port 465)', 'none' => 'None'] as $value => $label)
                            <option value="{{ $value }}" {{ old('mail_encryption', $settings['mail_encryption']) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="mail_from_address" class="block text-sm font-medium text-gray-700 mb-1.5">From Email Address</label>
                    <input type="email" name="mail_from_address" id="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                        placeholder="noreply@yourdomain.com"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="md:col-span-2">
                    <label for="mail_from_name" class="block text-sm font-medium text-gray-700 mb-1.5">From Name</label>
                    <input type="text" name="mail_from_name" id="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                        placeholder="{{ $settings['site_name'] }}"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-5">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">Test SMTP Connection</h3>
                <p class="text-sm text-gray-500 mb-3">Save your settings first, then send a test email to verify delivery.</p>
            </div>
        </div>

        {{-- Ticket automation --}}
        <div x-show="tab === 'tickets'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <h2 class="text-lg font-semibold text-gray-900">Ticket Automation</h2>
            <p class="text-sm text-gray-500">Automatically assign new customer tickets to a technician when they are created.</p>

            <label class="flex items-center gap-3">
                <input type="checkbox" name="auto_assign_enabled" value="1"
                    {{ old('auto_assign_enabled', $settings['auto_assign_enabled']) ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700">Enable auto-assign for new tickets</span>
            </label>

            <div>
                <label for="auto_assign_technician_id" class="block text-sm font-medium text-gray-700 mb-1.5">Default Technician</label>
                <select name="auto_assign_technician_id" id="auto_assign_technician_id"
                    class="w-full max-w-md rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select a technician...</option>
                    @foreach($technicians as $technician)
                        <option value="{{ $technician->id }}"
                            {{ (string) old('auto_assign_technician_id', $settings['auto_assign_technician_id'] ?? '') === (string) $technician->id ? 'selected' : '' }}>
                            {{ $technician->name }} ({{ $technician->email }})
                        </option>
                    @endforeach
                </select>
                @if($technicians->isEmpty())
                    <p class="mt-2 text-sm text-amber-600">No technicians available. Upgrade a customer to technician first.</p>
                @endif
            </div>
        </div>

        {{-- Security --}}
        <div x-show="tab === 'security'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Security</h2>
                <p class="text-sm text-gray-500 mt-1">Optional protections for registration and admin access.</p>
            </div>

            <label class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4">
                <input type="checkbox" name="require_email_verification" value="1"
                    {{ old('require_email_verification', $settings['require_email_verification']) ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Require email verification on registration</span>
                    <span class="mt-1 block text-sm text-gray-500">New customers must verify their email before using the app. Admin notifications are sent only after verification.</span>
                </span>
            </label>

            <label class="flex items-start gap-3 rounded-xl border border-amber-100 bg-amber-50 p-4">
                <input type="checkbox" name="require_admin_2fa" value="1"
                    {{ old('require_admin_2fa', $settings['require_admin_2fa']) ? 'checked' : '' }}
                    class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <span>
                    <span class="block text-sm font-medium text-gray-900">Require two-factor authentication for admins</span>
                    <span class="mt-1 block text-sm text-gray-600">Admin accounts must enable an authenticator app before accessing admin features.</span>
                </span>
            </label>
        </div>

        {{-- Legal --}}
        <div x-show="tab === 'legal'" x-cloak class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Legal Pages</h2>
                <p class="text-sm text-gray-500 mt-1">Content appears at <code class="rounded bg-gray-100 px-1">/privacy</code> and <code class="rounded bg-gray-100 px-1">/terms</code> when filled in. Footer links show automatically.</p>
            </div>

            <div class="grid grid-cols-1 gap-5">
                <div>
                    <label for="privacy_policy_title" class="block text-sm font-medium text-gray-700 mb-1.5">Privacy Policy Title</label>
                    <input type="text" name="privacy_policy_title" id="privacy_policy_title"
                        value="{{ old('privacy_policy_title', $settings['privacy_policy_title']) }}"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="privacy_policy_content" class="block text-sm font-medium text-gray-700 mb-1.5">Privacy Policy Content</label>
                    <textarea name="privacy_policy_content" id="privacy_policy_content" rows="10"
                        placeholder="Describe how you collect, use, and protect customer data..."
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('privacy_policy_content', $settings['privacy_policy_content']) }}</textarea>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 border-t border-gray-100 pt-5">
                <div>
                    <label for="terms_of_service_title" class="block text-sm font-medium text-gray-700 mb-1.5">Terms of Service Title</label>
                    <input type="text" name="terms_of_service_title" id="terms_of_service_title"
                        value="{{ old('terms_of_service_title', $settings['terms_of_service_title']) }}"
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label for="terms_of_service_content" class="block text-sm font-medium text-gray-700 mb-1.5">Terms of Service Content</label>
                    <textarea name="terms_of_service_content" id="terms_of_service_content" rows="10"
                        placeholder="Booking rules, liability, cancellation policy..."
                        class="w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('terms_of_service_content', $settings['terms_of_service_content']) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    </fieldset>

    @unless($readOnly ?? false)
    <div class="mt-8 flex flex-wrap gap-3">
        <button type="submit" class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 transition">
            Save All Settings
        </button>
        <a href="{{ route('dashboard') }}" class="rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
            Cancel
        </a>
    </div>
    @else
    <div class="mt-8 flex flex-wrap gap-3">
        <button type="button" disabled class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm opacity-50 cursor-not-allowed">
            Save All Settings
        </button>
        <a href="{{ route('dashboard') }}" class="rounded-xl border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
            Back to dashboard
        </a>
    </div>
    @endunless
</form>

<form action="{{ route('admin.settings.test-mail') }}" method="POST" class="mt-6 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm {{ ($readOnly ?? false) ? 'opacity-90' : '' }}">
    @csrf
    <h3 class="text-sm font-semibold text-gray-900 mb-1">Send Test Email</h3>
    <p class="text-sm text-gray-500 mb-4">Uses the saved SMTP settings above (save changes before testing).</p>
    <fieldset @disabled($readOnly ?? false) class="border-0 p-0 m-0">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="test_email" class="block text-sm font-medium text-gray-700 mb-1.5">Recipient email</label>
                <input type="email" name="test_email" id="test_email" value="{{ old('test_email', Auth::user()->email) }}" required
                    class="w-full max-w-md rounded-xl border-gray-200 bg-gray-50 px-4 py-2.5 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <button type="submit" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition shrink-0 disabled:cursor-not-allowed disabled:opacity-60">
                Send Test Email
            </button>
        </div>
    </fieldset>
</form>

@endsection
