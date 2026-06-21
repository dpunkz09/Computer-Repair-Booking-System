<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_tickets_by_issue_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'issue_summary' => 'Broken screen hinge',
        ]);

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'issue_summary' => 'Battery replacement',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['q' => 'hinge']))
            ->assertOk()
            ->assertSee('Broken screen hinge')
            ->assertDontSee('Battery replacement');
    }

    public function test_admin_can_filter_tickets_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'new',
            'issue_summary' => 'Open ticket',
        ]);

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'resolved',
            'issue_summary' => 'Closed ticket',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['status' => 'new']))
            ->assertOk()
            ->assertSee('Open ticket')
            ->assertDontSee('Closed ticket');
    }

    public function test_admin_can_filter_tickets_by_technician(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();
        $techA = User::factory()->technician()->create(['name' => 'Tech Alpha']);
        $techB = User::factory()->technician()->create(['name' => 'Tech Beta']);

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $techA->id,
            'issue_summary' => 'Alpha assignment',
        ]);

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $techB->id,
            'issue_summary' => 'Beta assignment',
        ]);

        $this->actingAs($admin)
            ->get(route('tickets.index', ['technician_id' => $techA->id]))
            ->assertOk()
            ->assertSee('Alpha assignment')
            ->assertDontSee('Beta assignment');
    }

    public function test_customer_only_sees_own_filtered_tickets(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        Ticket::factory()->create([
            'customer_id' => $customer->id,
            'issue_summary' => 'My laptop fan',
        ]);

        Ticket::factory()->create([
            'customer_id' => $other->id,
            'issue_summary' => 'Someone else phone',
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.index', ['q' => 'phone']))
            ->assertOk()
            ->assertDontSee('Someone else phone');
    }
}
