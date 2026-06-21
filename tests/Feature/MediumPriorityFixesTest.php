<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Services\TwoFactorService;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class MediumPriorityFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_eta_update_notifies_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($technician)
            ->patch(route('tickets.eta.update', $ticket), [
                'estimated_completion_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->id,
            'type' => NotificationService::TYPE_TICKET_ETA_UPDATED,
        ]);
    }

    public function test_unassign_ticket_notifies_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.unassign-ticket', $ticket))
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_TICKET_UNASSIGNED,
        ]);

        $ticket->refresh();
        $this->assertNull($ticket->technician_id);
        $this->assertSame('new', $ticket->status);
    }

    public function test_unassigned_list_uses_scope_and_includes_orphaned_tickets(): void
    {
        $admin = User::factory()->admin()->create();

        $orphaned = Ticket::factory()->create([
            'technician_id' => null,
            'status' => 'in_progress',
        ]);

        Ticket::factory()->create([
            'technician_id' => null,
            'status' => 'new',
        ]);

        Ticket::factory()->create([
            'technician_id' => User::factory()->technician()->create()->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.unassigned-tickets'))
            ->assertOk()
            ->assertSee('#'.$orphaned->id, false);
    }

    public function test_customer_can_update_booking_details_while_new(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'new',
            'device_type' => 'Laptop',
            'brand' => 'Dell',
            'os' => 'Windows 11',
            'issue_summary' => 'Screen flicker',
            'description' => 'Screen flickers on boot.',
        ]);

        $this->actingAs($customer)
            ->patch(route('tickets.details.update', $ticket), [
                'device_type' => 'Desktop',
                'brand' => 'HP',
                'os' => 'Windows 10',
                'issue_summary' => 'No display',
                'description' => 'Monitor stays black after power on.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame('Desktop', $ticket->device_type);
        $this->assertSame('No display', $ticket->issue_summary);
    }

    public function test_customer_cannot_update_booking_details_when_assigned(): void
    {
        $customer = User::factory()->customer()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($customer)
            ->patch(route('tickets.details.update', $ticket), [
                'device_type' => 'Desktop',
                'brand' => 'HP',
                'os' => 'Windows 10',
                'issue_summary' => 'Changed',
                'description' => 'Changed description.',
            ])
            ->assertForbidden();
    }

    public function test_comment_text_is_limited_to_five_thousand_characters(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->post(route('tickets.comments.store', $ticket), [
                'comment_text' => Str::repeat('a', 5001),
            ])
            ->assertSessionHasErrors(['comment_text']);
    }

    public function test_cancelled_ticket_cannot_receive_photo_uploads(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'closed',
            'cancelled_at' => now(),
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.photos.store', $ticket), [
                'photos' => [],
            ])
            ->assertForbidden();
    }

    public function test_overdue_eta_scope_excludes_terminal_tickets(): void
    {
        Ticket::factory()->create([
            'status' => 'in_progress',
            'estimated_completion_at' => now()->subHour(),
        ]);

        Ticket::factory()->create([
            'status' => 'resolved',
            'estimated_completion_at' => now()->subHour(),
        ]);

        $this->assertSame(1, Ticket::overdueEta()->count());
    }

    public function test_admin_dashboard_shows_overdue_eta_count(): void
    {
        $admin = User::factory()->admin()->create();

        Ticket::factory()->create([
            'status' => 'in_progress',
            'estimated_completion_at' => now()->subHour(),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Overdue ETA');
    }

    public function test_admin_cannot_disable_two_factor_when_required(): void
    {
        SiteSettings::set('require_admin_2fa', true);
        $admin = User::factory()->admin()->create();
        $secret = (new Google2FA)->generateSecretKey();
        app(TwoFactorService::class)->enable($admin, $secret, ['ABCD-EFGH']);

        $this->actingAs($admin)
            ->withSession(['two_factor.passed' => $admin->id])
            ->post(route('two-factor.disable'), [
                'password' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }
}
