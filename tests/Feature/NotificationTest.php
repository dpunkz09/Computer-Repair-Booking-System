<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_are_redirected_from_home_to_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = User::factory()->technician()->create();

        $this->actingAs($admin)->get(route('home'))->assertRedirect(route('dashboard'));
        $this->actingAs($technician)->get(route('home'))->assertRedirect(route('dashboard'));
    }

    public function test_customer_can_view_home_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Why Book With');
    }

    public function test_guest_can_view_home_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Get Started');
    }

    public function test_admin_is_notified_when_customer_registers(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('register'), [
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_CUSTOMER_REGISTERED,
        ]);
    }

    public function test_admin_is_notified_of_unassigned_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->post(route('tickets.store'), [
                'device_type' => 'Laptop',
                'brand' => 'Dell',
                'os' => 'Windows 11',
                'issue_summary' => 'Broken screen',
                'description' => 'Screen cracked.',
                'priority' => 3,
            ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->id,
            'type' => NotificationService::TYPE_TICKET_UNASSIGNED,
        ]);
    }

    public function test_technician_is_notified_when_ticket_is_assigned(): void
    {
        $admin = User::factory()->admin()->create();
        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'status' => 'new',
            'technician_id' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.assign-ticket', $ticket), [
                'technician_id' => $technician->id,
            ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $technician->id,
            'type' => NotificationService::TYPE_TICKET_ASSIGNED,
        ]);
    }

    public function test_customer_is_notified_on_status_update(): void
    {
        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($technician)
            ->patch(route('tickets.status.update', $ticket), [
                'status' => 'in_progress',
            ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->id,
            'type' => NotificationService::TYPE_TICKET_STATUS_UPDATED,
        ]);
    }

    public function test_customer_is_notified_on_technician_comment(): void
    {
        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($technician)
            ->post(route('tickets.comments.store', $ticket), [
                'comment_text' => 'We received your device and started diagnostics.',
            ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->id,
            'type' => NotificationService::TYPE_TICKET_COMMENT,
        ]);
    }

    public function test_technician_is_notified_on_customer_comment(): void
    {
        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        $this->actingAs($customer)
            ->post(route('tickets.comments.store', $ticket), [
                'comment_text' => 'Any update on my laptop?',
            ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $technician->id,
            'type' => NotificationService::TYPE_TICKET_COMMENT,
        ]);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $customer = User::factory()->customer()->create();
        $notification = UserNotification::create([
            'user_id' => $customer->id,
            'type' => NotificationService::TYPE_TICKET_STATUS_UPDATED,
            'title' => 'Test',
            'message' => 'Test message',
            'data' => ['url' => route('dashboard')],
        ]);

        $this->actingAs($customer)
            ->post(route('notifications.read', $notification))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
