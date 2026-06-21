<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HighPriorityFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_assigned_ticket_before_work_starts(): void
    {
        $customer = User::factory()->customer()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.cancel', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertTrue($ticket->fresh()->isCancelled());
    }

    public function test_customer_cannot_cancel_after_technician_comment(): void
    {
        $customer = User::factory()->customer()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'comment_text' => 'Starting diagnostics.',
            'is_internal_note' => false,
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.cancel', $ticket))
            ->assertForbidden();
    }

    public function test_cancelled_ticket_cannot_be_updated_by_staff(): void
    {
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'technician_id' => $technician->id,
            'status' => 'closed',
            'cancelled_at' => now(),
        ]);

        $this->actingAs($technician)
            ->put(route('tickets.update', $ticket), [
                'status' => 'in_progress',
                'priority' => 3,
            ])
            ->assertForbidden();
    }

    public function test_closed_filter_excludes_cancelled_tickets(): void
    {
        $admin = User::factory()->admin()->create();

        Ticket::factory()->create([
            'status' => 'closed',
            'cancelled_at' => null,
            'issue_summary' => 'Completed keyboard repair',
        ]);
        Ticket::factory()->create([
            'status' => 'closed',
            'cancelled_at' => now(),
            'issue_summary' => 'Customer cancelled screen fix',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['status' => 'closed']))
            ->assertOk()
            ->assertSee('Completed keyboard repair')
            ->assertDontSee('Customer cancelled screen fix');
    }

    public function test_cancelled_filter_shows_only_cancelled_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $cancelled = Ticket::factory()->create([
            'status' => 'closed',
            'cancelled_at' => now(),
            'issue_summary' => 'Cancelled laptop screen',
        ]);
        Ticket::factory()->create([
            'status' => 'closed',
            'cancelled_at' => null,
            'issue_summary' => 'Completed keyboard fix',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['status' => 'cancelled']))
            ->assertOk()
            ->assertSee($cancelled->issue_summary)
            ->assertDontSee('Completed keyboard fix');
    }

    public function test_profile_email_change_requires_reverification_when_enabled(): void
    {
        SiteSettings::set('require_email_verification', true);
        $customer = User::factory()->customer()->create(['email' => 'old@example.com']);

        $this->actingAs($customer)
            ->put(route('profile.update'), [
                'name' => $customer->name,
                'email' => 'new@example.com',
            ])
            ->assertRedirect(route('verification.notice'));

        $customer->refresh();
        $this->assertSame('new@example.com', $customer->email);
        $this->assertNull($customer->email_verified_at);
    }

    public function test_technician_downgrade_unassigns_open_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->assignedTo($technician)->create();

        $this->actingAs($admin)
            ->post(route('admin.downgrade-technician', $technician))
            ->assertRedirect();

        $ticket->refresh();
        $technician->refresh();

        $this->assertSame('customer', $technician->role);
        $this->assertNull($ticket->technician_id);
        $this->assertSame('new', $ticket->status);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_TICKET_UNASSIGNED,
        ]);
    }

    public function test_notifications_feed_is_available_for_bell(): void
    {
        $customer = User::factory()->customer()->create();

        UserNotification::create([
            'user_id' => $customer->id,
            'type' => NotificationService::TYPE_TICKET_COMMENT,
            'title' => 'New message',
            'message' => 'You have a reply.',
            'data' => ['url' => route('dashboard')],
        ]);

        $this->actingAs($customer)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'notifications');
    }
}
