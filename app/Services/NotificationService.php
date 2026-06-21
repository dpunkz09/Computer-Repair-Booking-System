<?php

namespace App\Services;

use App\Mail\TicketAlertMail;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\MailSettings;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public const TYPE_CUSTOMER_REGISTERED = 'customer_registered';

    public const TYPE_TICKET_UNASSIGNED = 'ticket_unassigned';

    public const TYPE_TICKET_ASSIGNED = 'ticket_assigned';

    public const TYPE_TICKET_STATUS_UPDATED = 'ticket_status_updated';

    public const TYPE_TICKET_COMMENT = 'ticket_comment';

    public const TYPE_TICKET_CANCELLED = 'ticket_cancelled';

    public const TYPE_TICKET_ETA_UPDATED = 'ticket_eta_updated';

    public static function notify(User $user, string $type, string $title, string $message, array $data = []): UserNotification
    {
        $notification = UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        self::queueEmail($user, $title, $message, $data['url'] ?? null);

        return $notification;
    }

    public static function markReadForTicket(User $user, Ticket $ticket): int
    {
        return $user->userNotifications()
            ->whereNull('read_at')
            ->where('data->ticket_id', $ticket->id)
            ->update(['read_at' => now()]);
    }

    public static function notifyAdmins(string $type, string $title, string $message, array $data = []): void
    {
        User::query()
            ->where('role', 'admin')
            ->each(fn (User $admin) => self::notify($admin, $type, $title, $message, $data));
    }

    public static function notifyCustomerRegistered(User $customer): void
    {
        self::notifyAdmins(
            self::TYPE_CUSTOMER_REGISTERED,
            'New customer registered',
            "{$customer->name} ({$customer->email}) just created an account.",
            [
                'user_id' => $customer->id,
                'url' => route('admin.users'),
            ]
        );
    }

    public static function handleNewTicket(Ticket $ticket): void
    {
        $ticket->refresh();

        if ($ticket->technician_id) {
            self::notifyTicketAssigned($ticket, $ticket->technician);
        } else {
            self::notifyUnassignedTicket($ticket);
        }
    }

    public static function notifyUnassignedTicket(Ticket $ticket): void
    {
        $ticket->loadMissing('customer');

        self::notifyAdmins(
            self::TYPE_TICKET_UNASSIGNED,
            'New ticket needs assignment',
            "Ticket #{$ticket->id}: {$ticket->issue_summary} from {$ticket->customer->name}.",
            [
                'ticket_id' => $ticket->id,
                'url' => route('admin.unassigned-tickets'),
            ]
        );
    }

    public static function notifyTicketAssigned(Ticket $ticket, User $technician): void
    {
        $ticket->loadMissing('customer');

        self::notify(
            $technician,
            self::TYPE_TICKET_ASSIGNED,
            'New ticket assigned to you',
            "Ticket #{$ticket->id}: {$ticket->issue_summary} from {$ticket->customer->name}.",
            [
                'ticket_id' => $ticket->id,
                'url' => route('tickets.show', $ticket),
            ]
        );
    }

    public static function notifyTicketStatusUpdated(Ticket $ticket): void
    {
        $ticket->loadMissing('customer');

        $statusLabel = str_replace('_', ' ', ucfirst($ticket->status));

        self::notify(
            $ticket->customer,
            self::TYPE_TICKET_STATUS_UPDATED,
            'Ticket status updated',
            "Your ticket #{$ticket->id} ({$ticket->issue_summary}) is now {$statusLabel}.",
            [
                'ticket_id' => $ticket->id,
                'url' => route('tickets.show', $ticket),
            ]
        );
    }

    public static function notifyNewComment(TicketComment $comment): void
    {
        $comment->loadMissing(['user', 'ticket.customer', 'ticket.technician']);
        $ticket = $comment->ticket;
        $author = $comment->user;

        if ($comment->is_internal_note) {
            return;
        }

        $preview = \Illuminate\Support\Str::limit($comment->comment_text, 80);
        $data = [
            'ticket_id' => $ticket->id,
            'comment_id' => $comment->id,
            'url' => route('tickets.show', $ticket),
        ];

        if ($author->id !== $ticket->customer_id) {
            self::notify(
                $ticket->customer,
                self::TYPE_TICKET_COMMENT,
                'New message on your ticket',
                "{$author->name} replied on ticket #{$ticket->id}: \"{$preview}\"",
                $data
            );
        }

        if ($ticket->technician && $author->id !== $ticket->technician_id) {
            self::notify(
                $ticket->technician,
                self::TYPE_TICKET_COMMENT,
                'New message on assigned ticket',
                "{$author->name} commented on ticket #{$ticket->id}: \"{$preview}\"",
                $data
            );
        }
    }

    public static function notifyTicketCancelled(Ticket $ticket): void
    {
        $ticket->loadMissing('customer');

        self::notifyAdmins(
            self::TYPE_TICKET_CANCELLED,
            'Ticket cancelled by customer',
            "Ticket #{$ticket->id}: {$ticket->issue_summary} was cancelled by {$ticket->customer->name}.",
            [
                'ticket_id' => $ticket->id,
                'url' => route('tickets.show', $ticket),
            ]
        );

        if ($ticket->technician) {
            self::notify(
                $ticket->technician,
                self::TYPE_TICKET_CANCELLED,
                'Assigned ticket cancelled',
                "Ticket #{$ticket->id} was cancelled by the customer before work started.",
                [
                    'ticket_id' => $ticket->id,
                    'url' => route('tickets.show', $ticket),
                ]
            );
        }
    }

    public static function notifyTicketEtaUpdated(Ticket $ticket): void
    {
        $ticket->loadMissing('customer');

        if ($ticket->estimated_completion_at) {
            $label = $ticket->estimated_completion_at->format('M j, Y g:i A');
            $message = "Your ticket #{$ticket->id} ({$ticket->issue_summary}) has an estimated completion date of {$label}.";
        } else {
            $message = "The estimated completion date for ticket #{$ticket->id} ({$ticket->issue_summary}) was cleared.";
        }

        self::notify(
            $ticket->customer,
            self::TYPE_TICKET_ETA_UPDATED,
            'Estimated completion updated',
            $message,
            [
                'ticket_id' => $ticket->id,
                'url' => route('tickets.show', $ticket),
            ]
        );
    }

    private static function queueEmail(User $user, string $title, string $message, ?string $actionUrl): void
    {
        if (! MailSettings::canSendTransactionalEmail()) {
            return;
        }

        $siteName = SiteSettings::getOrDefault('site_name');

        Mail::to($user->email)->queue(
            new TicketAlertMail(
                emailSubject: "{$siteName} — {$title}",
                heading: $title,
                messageText: $message,
                actionUrl: $actionUrl,
            )
        );
    }
}
