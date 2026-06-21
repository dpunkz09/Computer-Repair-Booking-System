<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    public function test_technician_cannot_access_admin_routes(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician)
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk();
    }

    public function test_customer_can_create_ticket(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'device_type' => 'Laptop',
                'brand' => 'Dell',
                'os' => 'Windows 11',
                'issue_summary' => 'Screen flickering',
                'description' => 'The screen flickers when opening apps.',
                'priority' => 3,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'customer_id' => $customer->id,
            'issue_summary' => 'Screen flickering',
        ]);
    }

    public function test_technician_cannot_create_ticket(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician)
            ->post(route('tickets.store'), [
                'device_type' => 'Laptop',
                'brand' => 'Dell',
                'os' => 'Windows 11',
                'issue_summary' => 'Screen flickering',
                'description' => 'The screen flickers when opening apps.',
            ])
            ->assertForbidden();
    }

    public function test_customer_cannot_view_other_customers_ticket(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['customer_id' => $otherCustomer->id]);

        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_customer_cannot_see_internal_notes(): void
    {
        $customer = User::factory()->customer()->create();
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->assignedTo($technician)->create([
            'customer_id' => $customer->id,
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'comment_text' => 'Public update for customer',
            'is_internal_note' => false,
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
            'comment_text' => 'Secret internal diagnostic note',
            'is_internal_note' => true,
        ]);

        $response = $this->actingAs($customer)->get(route('tickets.show', $ticket));

        $response->assertOk();

        $this->actingAs($customer)
            ->getJson(route('tickets.comments.feed', $ticket))
            ->assertOk()
            ->assertJsonCount(1, 'comments')
            ->assertJsonFragment(['body' => 'Public update for customer'])
            ->assertJsonMissing(['body' => 'Secret internal diagnostic note']);
    }

    public function test_technician_can_quick_update_status(): void
    {
        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->assignedTo($technician)->create([
            'customer_id' => $customer->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($technician)
            ->patch(route('tickets.status.update', $ticket), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_admin_can_delete_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::factory()->create();

        $this->actingAs($admin)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_profile_can_be_updated(): void
    {
        $customer = User::factory()->customer()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $this->actingAs($customer)
            ->put(route('profile.update'), [
                'name' => 'New Name',
                'email' => 'new@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        $customer = User::factory()->customer()->create([
            'email' => 'reset@example.com',
        ]);

        $this->post(route('password.email'), ['email' => 'reset@example.com'])
            ->assertRedirect();

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset@example.com',
        ]);
    }
}
