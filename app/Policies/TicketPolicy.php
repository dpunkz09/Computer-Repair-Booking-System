<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Admin can view all tickets
        if ($user->isAdmin()) {
            return true;
        }
        // Customer can view their own tickets
        if ($user->role === 'customer' && $ticket->customer_id === $user->id) {
            return true;
        }
        // Technician can view tickets assigned to them
        if ($user->role === 'technician' && $ticket->technician_id === $user->id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only customers can create tickets
        return $user->role === 'customer';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($ticket->isCancelled()) {
            return false;
        }

        // Admin can update any ticket
        if ($user->isAdmin()) {
            return true;
        }
        // Technician can update their assigned tickets
        if ($user->role === 'technician' && $ticket->technician_id === $user->id) {
            return true;
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Only admin can delete tickets
        return $user->isFullAdmin();
    }

    /**
     * Determine whether the customer can cancel the ticket before work starts.
     */
    public function cancel(User $user, Ticket $ticket): bool
    {
        return $user->role === 'customer'
            && $ticket->customer_id === $user->id
            && $ticket->canBeCancelledByCustomer();
    }

    /**
     * Determine whether the customer can edit booking details before work starts.
     */
    public function updateDetails(User $user, Ticket $ticket): bool
    {
        return $user->role === 'customer'
            && $ticket->customer_id === $user->id
            && $ticket->status === 'new'
            && ! $ticket->isCancelled()
            && ! $ticket->hasWorkStarted();
    }

    /**
     * Determine whether the user can set an estimated completion date.
     */
    public function setEta(User $user, Ticket $ticket): bool
    {
        if ($ticket->isCancelled()) {
            return false;
        }

        return $user->isAdmin()
            || ($user->role === 'technician' && $ticket->technician_id === $user->id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return false;
    }
}
