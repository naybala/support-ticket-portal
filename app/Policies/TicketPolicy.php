<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine whether an agent can list all tickets (agent dashboard).
     */
    public function viewAny(User $user): bool
    {
        return $user->role === 'agent';
    }

    /**
     * Determine whether a client can view their own ticket index.
     * Agents are redirected to their own dashboard and must not access this route.
     */
    public function viewClientIndex(User $user): bool
    {
        return $user->role === 'client';
    }

    /**
     * Determine whether a client can open a new ticket.
     * Agents use a separate internal workflow and must not create tickets this way.
     */
    public function create(User $user): bool
    {
        return $user->role === 'client';
    }

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
