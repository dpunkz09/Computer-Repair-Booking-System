<?php

namespace Tests\Feature;

use App\Mail\SmtpTestMail;
use App\Models\User;
use App\Support\MailSettings;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_smtp_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'ComTech Repair',
                'primary_color' => '#2563eb',
                'mail_enabled' => '1',
                'mail_host' => 'smtp.example.com',
                'mail_port' => '587',
                'mail_username' => 'mailer@example.com',
                'mail_password' => 'secret-password',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'noreply@example.com',
                'mail_from_name' => 'ComTech Repair',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue(SiteSettings::bool('mail_enabled'));
        $this->assertSame('smtp.example.com', SiteSettings::get('mail_host'));
        $this->assertSame('secret-password', MailSettings::decryptedPassword());
    }

    public function test_mail_settings_apply_to_laravel_config(): void
    {
        SiteSettings::setMany([
            'mail_enabled' => true,
            'mail_host' => 'smtp.mailtrap.io',
            'mail_port' => '2525',
            'mail_username' => 'user',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'test@example.com',
            'mail_from_name' => 'Test Site',
        ]);
        MailSettings::setPassword('pass');

        MailSettings::applyIfConfigured();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.mailtrap.io', config('mail.mailers.smtp.host'));
        $this->assertSame(2525, config('mail.mailers.smtp.port'));
        $this->assertSame('user', config('mail.mailers.smtp.username'));
        $this->assertSame('pass', config('mail.mailers.smtp.password'));
        $this->assertSame('test@example.com', config('mail.from.address'));
    }

    public function test_admin_can_send_test_email_when_smtp_configured(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();

        SiteSettings::setMany([
            'mail_enabled' => true,
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_username' => 'user@example.com',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'ComTech Repair',
        ]);
        MailSettings::setPassword('secret');

        $this->actingAs($admin)
            ->post(route('admin.settings.test-mail'), [
                'test_email' => 'admin@test.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertQueued(SmtpTestMail::class);
    }

    public function test_test_email_fails_when_smtp_not_configured(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.test-mail'), [
                'test_email' => 'admin@test.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_settings_page_shows_email_tab(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Email / SMTP');
    }
}
