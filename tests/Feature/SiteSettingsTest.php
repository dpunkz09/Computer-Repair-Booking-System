<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_settings(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_settings(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_settings_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Site Settings');
    }

    public function test_admin_can_update_site_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Acme Repairs',
                'site_tagline' => 'We fix it fast',
                'seo_title' => 'Acme Repairs — Book Online',
                'seo_description' => 'Best repair shop in town.',
                'seo_keywords' => 'repair, acme',
                'contact_email' => 'help@acme.test',
                'contact_phone' => '555-0100',
                'support_hours' => 'Mon–Sat 8–8',
                'footer_text' => 'Acme Repairs ©',
                'primary_color' => '#ff5500',
                'welcome_badge' => 'Welcome',
                'welcome_headline' => 'Fix Your Device Today',
                'welcome_subheadline' => 'Book online in minutes.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('Acme Repairs', SiteSettings::getOrDefault('site_name'));
        $this->assertSame('#ff5500', SiteSettings::getOrDefault('primary_color'));
        $this->assertSame('help@acme.test', Setting::where('key', 'contact_email')->value('value'));
    }

    public function test_admin_can_upload_site_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'ComTech Repair',
                'primary_color' => '#2563eb',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $path = Setting::where('key', 'logo_path')->value('value');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_new_ticket_is_auto_assigned_when_enabled(): void
    {
        $customer = User::factory()->customer()->create();
        $technician = User::factory()->technician()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'ComTech Repair',
                'primary_color' => '#2563eb',
                'auto_assign_enabled' => '1',
                'auto_assign_technician_id' => $technician->id,
            ]);

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'device_type' => 'Laptop',
                'brand' => 'HP',
                'os' => 'Windows 11',
                'issue_summary' => 'No power',
                'description' => 'Laptop will not turn on.',
                'priority' => 4,
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->latest('id')->first();

        $this->assertSame($technician->id, $ticket->technician_id);
        $this->assertSame('assigned', $ticket->status);
    }

    public function test_new_ticket_stays_unassigned_when_auto_assign_disabled(): void
    {
        $customer = User::factory()->customer()->create();
        User::factory()->technician()->create();

        SiteSettings::setMany([
            'auto_assign_enabled' => false,
            'auto_assign_technician_id' => '',
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'device_type' => 'Desktop',
                'brand' => 'Dell',
                'os' => 'Windows 10',
                'issue_summary' => 'Slow boot',
                'description' => 'Takes forever to start.',
                'priority' => 2,
            ])
            ->assertRedirect();

        $ticket = Ticket::query()->latest('id')->first();

        $this->assertNull($ticket->technician_id);
        $this->assertSame('new', $ticket->status);
    }

    public function test_admin_can_update_homepage_content(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Acme Repairs',
                'primary_color' => '#2563eb',
                'homepage_show_features' => '1',
                'homepage_features_title' => 'Why Choose Acme?',
                'homepage_features_subtitle' => 'Fast, friendly service.',
                'homepage_features' => [
                    ['icon' => '⚡', 'title' => 'Quick Turnaround', 'description' => 'Most repairs done same day.'],
                ],
                'homepage_show_steps' => '0',
                'homepage_steps_title' => 'How It Works',
                'homepage_steps_subtitle' => '',
                'homepage_steps' => [],
                'homepage_show_cta' => '1',
                'homepage_cta_title' => 'Book With Acme Today',
                'homepage_cta_subtitle' => 'Sign up in minutes.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $content = \App\Support\HomepageContent::get();

        $this->assertSame('Why Choose Acme?', $content['features_title']);
        $this->assertFalse($content['show_steps']);
        $this->assertSame('Book With Acme Today', $content['cta_title']);
        $this->assertSame('Quick Turnaround', $content['features'][0]['title']);
    }

    public function test_homepage_renders_custom_content(): void
    {
        \App\Support\HomepageContent::save([
            'features_title' => 'Our Promise',
            'features' => [
                ['icon' => '🛠️', 'title' => 'Expert Repairs', 'description' => 'Certified technicians.'],
            ],
            'show_steps' => false,
            'cta_title' => 'Start Your Repair',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Our Promise')
            ->assertSee('Expert Repairs')
            ->assertSee('Start Your Repair')
            ->assertDontSee('How It Works');
    }

    public function test_admin_can_upload_homepage_hero_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'ComTech Repair',
                'primary_color' => '#2563eb',
                'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
                'homepage_show_features' => '1',
                'homepage_show_steps' => '1',
                'homepage_show_cta' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $content = \App\Support\HomepageContent::get();

        $this->assertNotEmpty($content['hero_image_path']);
        Storage::disk('public')->assertExists($content['hero_image_path']);
    }
}
