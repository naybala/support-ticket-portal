<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentCommentRequest;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TicketController extends Controller
{
    protected SlaService $slaService;

    public function __construct(SlaService $slaService)
    {
        $this->slaService = $slaService;
    }

    /**
     * Display a listing of the client's tickets.
     */
    public function index(Request $request)
    {
        if (auth()->user()->role === 'agent') {
            return redirect()->route('agent.tickets.index');
        }

        $tickets = Ticket::where('organization_id', auth()->user()->organization_id)
            ->with(['creator', 'assignedAgent'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->search, fn($q, $s) => $q->where(fn($sub) =>
                $sub->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create([
            'organization_id'   => auth()->user()->organization_id,
            'created_by_user_id' => auth()->id(),
            'title'             => $request->title,
            'description'       => $request->description,
            'priority'          => $request->priority,
            'status'            => 'open',
            'sla_due_at'        => $this->slaService->calculate($request->priority),
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket created successfully.');
    }

    /**
     * Display the specified ticket (client view).
     */
    public function show(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['creator', 'assignedAgent', 'organization']);

        // Clients never see internal agent notes.
        $comments = $ticket->comments()
            ->where('is_internal', false)
            ->with('user')
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Tickets/Show', [
            'ticket'   => $ticket,
            'comments' => $comments,
        ]);
    }

    /**
     * Add a public comment to the ticket (client view).
     */
    public function storeComment(StoreCommentRequest $request, Ticket $ticket)
    {
        Gate::authorize('comment', $ticket);

        $ticket->comments()->create([
            'user_id'     => auth()->id(),
            'body'        => $request->body,
            'is_internal' => false,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    /**
     * Display a listing of all tickets for agents (with filters).
     */
    public function agentIndex(Request $request)
    {
        Gate::authorize('viewAny', Ticket::class);

        $tickets = Ticket::with(['creator', 'assignedAgent', 'organization'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->when($request->organization_id, fn($q, $o) => $q->where('organization_id', $o))
            ->when($request->search, fn($q, $s) => $q->where(fn($sub) =>
                $sub->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
            ))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $agents        = User::where('role', 'agent')->get();
        $organizations = Organization::all();

        return Inertia::render('Agent/Tickets/Index', [
            'tickets'       => $tickets,
            'filters'       => $request->only(['status', 'priority', 'organization_id', 'search']),
            'agents'        => $agents,
            'organizations' => $organizations,
        ]);
    }

    /**
     * Display the specified ticket (agent view — includes internal notes).
     */
    public function agentShow(Ticket $ticket)
    {
        Gate::authorize('view', $ticket);

        $ticket->load(['creator', 'assignedAgent', 'organization']);

        // Agents see all comments including internal notes.
        $comments = $ticket->comments()
            ->with('user')
            ->orderBy('id', 'asc')
            ->get();

        $agents = User::where('role', 'agent')->get();

        return Inertia::render('Agent/Tickets/Show', [
            'ticket'   => $ticket,
            'comments' => $comments,
            'agents'   => $agents,
        ]);
    }

    /**
     * Update the ticket status, priority, or assignee (agent support actions).
     */
    public function agentUpdate(UpdateTicketRequest $request, Ticket $ticket)
    {
        Gate::authorize('update', $ticket);

        $data = [];

        if ($request->has('status')) {
            $data['status'] = $request->status;
        }

        if ($request->has('priority')) {
            $data['priority']    = $request->priority;
            // Recalculate SLA deadline when priority changes.
            $data['sla_due_at'] = $this->slaService->calculate($request->priority);
        }

        if ($request->has('assigned_to_user_id')) {
            $data['assigned_to_user_id'] = $request->assigned_to_user_id;
        }

        $ticket->update($data);

        return back()->with('success', 'Ticket updated successfully.');
    }

    /**
     * Add a comment or internal note to the ticket (agent view).
     */
    public function storeAgentComment(StoreAgentCommentRequest $request, Ticket $ticket)
    {
        Gate::authorize('comment', $ticket);

        $ticket->comments()->create([
            'user_id'     => auth()->id(),
            'body'        => $request->body,
            'is_internal' => $request->is_internal,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }
}
