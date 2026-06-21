<?php

namespace Tests\Feature;

use App\Mail\TicketAlertMail;
use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TicketEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mail.default' => 'smtp']);
        config(['mail.from.address' => 'noreply@example.com']);
        config(['mail.from.name' => 'ComTech Repair']);
    }

    public function test_customer_receives_email_when_ticket_status_updates(): void
    {
        Mail::fake();

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

        Mail::assertQueued(TicketAlertMail::class, function (TicketAlertMail $mail) use ($customer) {
            return $mail->hasTo($customer->email);
        });
    }

    public function test_technician_receives_email_when_ticket_is_assigned(): void
    {
        Mail::fake();

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

        Mail::assertQueued(TicketAlertMail::class, function (TicketAlertMail $mail) use ($technician) {
            return $mail->hasTo($technician->email);
        });
    }

    public function test_no_email_sent_when_mailer_is_log_driver(): void
    {
        Mail::fake();
        config(['mail.default' => 'log']);

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

        Mail::assertNothingQueued();
    }

    public function test_admin_smtp_settings_enable_transactional_email(): void
    {
        Mail::fake();

        SiteSettings::setMany([
            'mail_enabled' => true,
            'mail_host' => 'smtp.example.com',
            'mail_port' => '587',
            'mail_from_address' => 'noreply@example.com',
            'mail_from_name' => 'ComTech Repair',
        ]);

        $technician = User::factory()->technician()->create();
        $customer = User::factory()->customer()->create();

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'technician_id' => $technician->id,
            'status' => 'assigned',
        ]);

        NotificationService::notifyTicketStatusUpdated($ticket);

        Mail::assertQueued(TicketAlertMail::class);
    }
}
