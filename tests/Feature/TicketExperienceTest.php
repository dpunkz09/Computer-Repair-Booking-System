<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_ticket_marks_related_notifications_as_read(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        UserNotification::create([
            'user_id' => $customer->id,
            'type' => NotificationService::TYPE_TICKET_COMMENT,
            'title' => 'New message',
            'message' => 'You have a reply.',
            'data' => ['ticket_id' => $ticket->id, 'url' => route('tickets.show', $ticket)],
        ]);

        $this->actingAs($customer)->get(route('tickets.show', $ticket))->assertOk();

        $this->assertNotNull(
            UserNotification::where('user_id', $customer->id)->first()->read_at
        );
    }

    public function test_comments_feed_returns_json_for_ticket_viewers(): void
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
            'comment_text' => 'We received your device.',
            'is_internal_note' => false,
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'comment_text' => 'Parts on order.',
            'is_internal_note' => true,
        ]);

        $this->actingAs($customer)
            ->getJson(route('tickets.comments.feed', $ticket))
            ->assertOk()
            ->assertJsonCount(1, 'comments')
            ->assertJsonFragment(['body' => 'We received your device.']);

        $this->actingAs($technician)
            ->getJson(route('tickets.comments.feed', $ticket))
            ->assertOk()
            ->assertJsonCount(2, 'comments');
    }

    public function test_comment_can_be_posted_via_json(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($customer)
            ->postJson(route('tickets.comments.store', $ticket), [
                'comment_text' => 'Any update on my laptop?',
            ])
            ->assertOk()
            ->assertJsonFragment(['body' => 'Any update on my laptop?']);

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'comment_text' => 'Any update on my laptop?',
        ]);
    }

    public function test_customer_can_cancel_new_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'new',
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.cancel', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();

        $this->assertNotNull($ticket->cancelled_at);
        $this->assertSame('closed', $ticket->status);
        $this->assertSame('cancelled', $ticket->displayStatus());

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_TICKET_CANCELLED,
        ]);
    }

    public function test_customer_cannot_cancel_ticket_once_work_has_started(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.cancel', $ticket))
            ->assertForbidden();
    }

    public function test_technician_can_set_estimated_completion_date(): void
    {
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create([
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $eta = now()->addDays(2)->format('Y-m-d H:i:s');

        $this->actingAs($technician)
            ->patch(route('tickets.eta.update', $ticket), [
                'estimated_completion_at' => $eta,
            ])
            ->assertRedirect();

        $this->assertNotNull($ticket->fresh()->estimated_completion_at);
    }

    public function test_customer_sees_estimated_completion_on_ticket_page(): void
    {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'estimated_completion_at' => now()->addDay(),
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Estimated completion');
    }

    public function test_layout_includes_mobile_navigation_links(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Toggle navigation', false)
            ->assertSee(route('tickets.index'), false);
    }
}
