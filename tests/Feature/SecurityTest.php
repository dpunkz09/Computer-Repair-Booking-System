<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationService;
use App\Services\TwoFactorService;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited(): void
    {
        User::factory()->customer()->create(['email' => 'user@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('register'), [
                'name' => 'User '.$i,
                'email' => "user{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);
        }

        $this->post(route('register'), [
            'name' => 'Blocked User',
            'email' => 'blocked@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
    }

    public function test_comment_posting_is_rate_limited(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = \App\Models\Ticket::factory()->create(['customer_id' => $customer->id]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($customer)->postJson(route('tickets.comments.store', $ticket), [
                'comment_text' => "Message {$i}",
            ]);
        }

        $this->actingAs($customer)
            ->postJson(route('tickets.comments.store', $ticket), [
                'comment_text' => 'One too many',
            ])
            ->assertStatus(429);
    }

    public function test_registration_requires_verification_when_setting_enabled(): void
    {
        SiteSettings::set('require_email_verification', true);
        $admin = User::factory()->admin()->create();

        $this->post(route('register'), [
            'name' => 'New Customer',
            'email' => 'verify-me@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_CUSTOMER_REGISTERED,
        ]);

        $user = User::where('email', 'verify-me@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    public function test_admin_is_notified_after_customer_verifies_email(): void
    {
        SiteSettings::set('require_email_verification', true);
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $customer->id, 'hash' => sha1($customer->email)]
        );

        $this->actingAs($customer)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_CUSTOMER_REGISTERED,
        ]);
    }

    public function test_unverified_customer_cannot_access_dashboard_when_verification_required(): void
    {
        SiteSettings::set('require_email_verification', true);
        $customer = User::factory()->customer()->unverified()->create();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_admin_without_two_factor_is_redirected_when_required(): void
    {
        SiteSettings::set('require_admin_2fa', true);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertRedirect(route('profile.edit'));
    }

    public function test_admin_can_complete_two_factor_setup_and_access_admin(): void
    {
        SiteSettings::set('require_admin_2fa', true);
        $admin = User::factory()->admin()->create();
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();

        $this->actingAs($admin)
            ->withSession(['two_factor.setup_secret' => $secret])
            ->post(route('two-factor.confirm'), [
                'code' => $google2fa->getCurrentOtp($secret),
            ])
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();
        $this->assertTrue($admin->hasTwoFactorEnabled());

        $this->actingAs($admin)
            ->withSession(['two_factor.passed' => $admin->id])
            ->get(route('admin.settings.index'))
            ->assertOk();
    }

    public function test_admin_login_with_two_factor_requires_challenge(): void
    {
        $admin = User::factory()->admin()->create();
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        app(TwoFactorService::class)->enable($admin, $secret, ['ABCD-EFGH']);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_privacy_page_is_available_when_content_is_configured(): void
    {
        SiteSettings::setMany([
            'privacy_policy_title' => 'Privacy Policy',
            'privacy_policy_content' => 'We respect your privacy.',
        ]);

        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSee('We respect your privacy.');
    }

    public function test_privacy_page_returns_not_found_when_empty(): void
    {
        $this->get(route('legal.privacy'))->assertNotFound();
    }

    public function test_footer_shows_legal_links_when_content_exists(): void
    {
        SiteSettings::setMany([
            'privacy_policy_content' => 'Privacy content',
            'terms_of_service_content' => 'Terms content',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('legal.privacy'), false)
            ->assertSee(route('legal.terms'), false);
    }
}
