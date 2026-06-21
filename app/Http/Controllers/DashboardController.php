<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->role === 'admin') {
            return $this->adminDashboard();
        } elseif ($user->role === 'technician') {
            return $this->technicianDashboard();
        } else {
            return $this->customerDashboard();
        }
    }

    protected function adminDashboard()
    {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::notCancelled()
            ->whereIn('status', TicketStatus::OPEN)
            ->count();
        $unassignedTickets = Ticket::unassigned()->count();
        $overdueEtaTickets = Ticket::overdueEta()->count();
        $recentTickets = Ticket::with(['customer', 'technician'])->latest()->limit(8)->get();
        $technicians = \App\Models\User::where('role', 'technician')->count();
        $resolvedTickets = Ticket::notCancelled()->where('status', TicketStatus::RESOLVED)->count();
        $closedTickets = Ticket::notCancelled()->where('status', TicketStatus::CLOSED)->count();
        $cancelledTickets = Ticket::cancelled()->count();

        return view('dashboards.admin', compact(
            'totalTickets',
            'openTickets',
            'unassignedTickets',
            'overdueEtaTickets',
            'recentTickets',
            'technicians',
            'resolvedTickets',
            'closedTickets',
            'cancelledTickets'
        ));
    }

    protected function technicianDashboard()
    {
        $assignedTickets = Ticket::where('technician_id', Auth::id())
            ->with('customer')
            ->orderByDesc('priority')
            ->latest()
            ->get();

        $activeTickets = $assignedTickets->filter(fn (Ticket $ticket) => ! $ticket->isCancelled());
        $assignedCount = $activeTickets->count();
        $inProgressCount = $activeTickets->where('status', TicketStatus::IN_PROGRESS)->count();
        $resolvedCount = $activeTickets->where('status', TicketStatus::RESOLVED)->count();
        $awaitingPartsCount = $activeTickets->where('status', TicketStatus::AWAITING_PARTS)->count();
        $overdueEtaCount = Ticket::where('technician_id', Auth::id())->overdueEta()->count();

        return view('dashboards.technician', compact(
            'assignedTickets',
            'assignedCount',
            'inProgressCount',
            'resolvedCount',
            'awaitingPartsCount',
            'overdueEtaCount'
        ));
    }

    protected function customerDashboard()
    {
        $tickets = Ticket::where('customer_id', Auth::id())
            ->with('technician')
            ->orderByDesc('priority')
            ->latest()
            ->get();

        $totalTickets = $tickets->count();
        $activeTickets = $tickets
            ->filter(fn (Ticket $ticket) => ! $ticket->isCancelled() && in_array($ticket->status, TicketStatus::OPEN, true))
            ->count();
        $resolvedTickets = $tickets
            ->filter(fn (Ticket $ticket) => ! $ticket->isCancelled() && $ticket->status === TicketStatus::RESOLVED)
            ->count();
        $closedTickets = $tickets
            ->filter(fn (Ticket $ticket) => ! $ticket->isCancelled() && $ticket->status === TicketStatus::CLOSED)
            ->count();
        $cancelledTickets = $tickets->filter(fn (Ticket $ticket) => $ticket->isCancelled())->count();

        return view('dashboards.customer', compact(
            'tickets',
            'totalTickets',
            'activeTickets',
            'resolvedTickets',
            'closedTickets',
            'cancelledTickets'
        ));
    }
}
