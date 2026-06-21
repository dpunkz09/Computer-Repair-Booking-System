<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\AdminAuditLog;
use App\Support\TicketStatus;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display all users for management.
     */
    public function users()
    {
        $users = User::paginate(15);

        return view('admin.users', compact('users'));
    }

    /**
     * Upgrade a user to technician role.
     */
    public function upgradeTechnician(User $user)
    {
        if ($user->role !== 'customer') {
            return back()->with('error', 'Only customers can be upgraded.');
        }

        $user->update(['role' => 'technician']);

        AdminAuditLog::record('user.upgrade_technician', [
            'target_user_id' => $user->id,
            'target_email' => $user->email,
        ]);

        return back()->with('success', 'User upgraded to technician.');
    }

    /**
     * Downgrade a user back to customer role.
     */
    public function downgradeTechnician(User $user)
    {
        if ($user->role !== 'technician') {
            return back()->with('error', 'Only technicians can be downgraded.');
        }

        $openTickets = Ticket::query()
            ->where('technician_id', $user->id)
            ->notCancelled()
            ->whereNotIn('status', TicketStatus::TERMINAL)
            ->get();

        foreach ($openTickets as $ticket) {
            $ticket->update([
                'technician_id' => null,
                'status' => TicketStatus::NEW,
            ]);

            NotificationService::notifyUnassignedTicket($ticket->fresh());
        }

        $user->update(['role' => 'customer']);

        AdminAuditLog::record('user.downgrade_technician', [
            'target_user_id' => $user->id,
            'target_email' => $user->email,
            'unassigned_ticket_ids' => $openTickets->pluck('id')->all(),
        ]);

        $message = 'User downgraded to customer.';
        if ($openTickets->isNotEmpty()) {
            $message .= ' '.$openTickets->count().' open ticket(s) were unassigned.';
        }

        return back()->with('success', $message);
    }

    /**
     * Promote a user to admin role.
     */
    public function promoteAdmin(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'User is already an admin.');
        }

        $previousRole = $user->role;
        $user->update(['role' => 'admin']);

        AdminAuditLog::record('user.promote_admin', [
            'target_user_id' => $user->id,
            'target_email' => $user->email,
            'previous_role' => $previousRole,
        ]);

        return back()->with('success', 'User promoted to admin.');
    }

    /**
     * Demote an admin to customer role.
     */
    public function demoteAdmin(Request $request, User $user)
    {
        if ($user->role !== 'admin') {
            return back()->with('error', 'Only admins can be demoted.');
        }

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot demote your own account.');
        }

        if (User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Cannot demote the last admin account.');
        }

        $user->update(['role' => 'customer']);

        AdminAuditLog::record('user.demote_admin', [
            'target_user_id' => $user->id,
            'target_email' => $user->email,
        ]);

        return back()->with('success', 'Admin demoted to customer.');
    }

    /**
     * Assign a ticket to a technician.
     */
    public function assignTicket(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'technician_id' => 'required|exists:users,id',
        ]);

        $technician = User::findOrFail($validated['technician_id']);

        if ($technician->role !== 'technician') {
            return back()->with('error', 'Selected user is not a technician.');
        }

        $ticket->update([
            'technician_id' => $technician->id,
            'status' => TicketStatus::ASSIGNED,
        ]);

        NotificationService::notifyTicketAssigned($ticket->fresh(), $technician);

        AdminAuditLog::record('ticket.assign', [
            'ticket_id' => $ticket->id,
            'technician_id' => $technician->id,
            'technician_email' => $technician->email,
        ]);

        return back()->with('success', 'Ticket assigned to '.$technician->name);
    }

    /**
     * Unassign a ticket from a technician.
     */
    public function unassignTicket(Ticket $ticket)
    {
        $previousTechnicianId = $ticket->technician_id;

        $ticket->update([
            'technician_id' => null,
            'status' => TicketStatus::NEW,
        ]);

        NotificationService::notifyUnassignedTicket($ticket->fresh());

        AdminAuditLog::record('ticket.unassign', [
            'ticket_id' => $ticket->id,
            'previous_technician_id' => $previousTechnicianId,
        ]);

        return back()->with('success', 'Ticket unassigned.');
    }

    /**
     * View all unassigned tickets.
     */
    public function unassignedTickets()
    {
        $tickets = Ticket::unassigned()
            ->with('customer')
            ->orderByDesc('priority')
            ->latest()
            ->paginate(10);

        $technicians = User::where('role', 'technician')->orderBy('name')->get();

        return view('admin.unassigned-tickets', compact('tickets', 'technicians'));
    }
}
