<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->role === 'agent') {
            return true;
        }

        return $user->role === 'client' && $user->organization_id === $ticket->organization_id;
    }

    /**
     * Determine whether the user can update the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->role === 'agent') {
            return true;
        }

        return $user->role === 'client' && $user->organization_id === $ticket->organization_id;
    }

    /**
     * Determine whether the user can add a comment to the ticket.
     */
    public function comment(User $user, Ticket $ticket): bool
    {
        if ($user->role === 'agent') {
            return true;
        }

        return $user->role === 'client' && $user->organization_id === $ticket->organization_id;
    }
}
