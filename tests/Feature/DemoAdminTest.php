<?php

namespace Tests\Feature;

use App\Models\ServiceCategory;
use App\Models\Ticket;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_admin_can_access_admin_dashboard_and_assign_tickets(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create(['status' => 'new']);

        $this->actingAs($demoAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('System Overview');

        $this->actingAs($demoAdmin)
            ->post(route('admin.assign-ticket', $ticket), [
                'technician_id' => $technician->id,
            ])
            ->assertRedirect();

        $this->assertSame($technician->id, $ticket->fresh()->technician_id);
    }

    public function test_demo_admin_can_view_settings_but_cannot_update_them(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();

        $this->actingAs($demoAdmin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Read-only');

        $this->actingAs($demoAdmin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Hacked Site Name',
                'primary_color' => '#000000',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_demo_admin_sees_user_management_actions_disabled(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();
        User::factory()->customer()->create();

        $this->actingAs($demoAdmin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Upgrade to Technician')
            ->assertSee('Promote to Admin');
    }

    public function test_demo_admin_sees_category_forms_disabled(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();
        ServiceCategory::create([
            'name' => 'Screen Repair',
            'description' => 'Display fixes',
            'is_active' => true,
        ]);

        $this->actingAs($demoAdmin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee('Add Category')
            ->assertSee('Create Category')
            ->assertSee('Delete');
    }

    public function test_demo_admin_cannot_manage_users_or_categories(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();
        $customer = User::factory()->customer()->create();
        $category = ServiceCategory::create([
            'name' => 'Screen Repair',
            'description' => 'Display fixes',
            'is_active' => true,
        ]);

        $this->actingAs($demoAdmin)
            ->post(route('admin.promote-admin', $customer))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($demoAdmin)
            ->post(route('admin.categories.store'), [
                'name' => 'New Category',
                'description' => 'Should fail',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->actingAs($demoAdmin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Changed Name',
                'is_active' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_demo_admin_cannot_delete_tickets(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($demoAdmin)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_login_page_shows_sample_credentials(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('demo@example.com')
            ->assertSee('technician@example.com')
            ->assertSee('test@example.com')
            ->assertSee('Sample logins');
    }

    public function test_demo_admin_can_update_tickets_and_set_eta(): void
    {
        $demoAdmin = User::factory()->demoAdmin()->create();
        $ticket = Ticket::factory()->create(['status' => 'new']);

        $this->actingAs($demoAdmin)
            ->put(route('tickets.update', $ticket), [
                'status' => 'assigned',
                'priority' => 4,
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->actingAs($demoAdmin)
            ->patch(route('tickets.eta.update', $ticket), [
                'estimated_completion_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();
    }

    public function test_seeded_demo_admin_account_exists_after_seed(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'demo@example.com',
            'role' => UserRole::DEMO_ADMIN,
        ]);
    }
}
