<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
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
    public function index()
    {
        if (auth()->user()->role === 'agent') {
            return redirect()->route('agent.tickets.index');
        }

        $tickets = Ticket::where('organization_id', auth()->user()->organization_id)
            ->with(['creator', 'assignedAgent'])
            ->latest()
            ->get();

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets
        ]);
    }

    /**
     * Store a newly created ticket.
     */
    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create([
            'organization_id' => auth()->user()->organization_id,
            'created_by_user_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'priority' => $request->priority,
            'status' => 'open',
            'sla_due_at' => $this->slaService->calculate($request->priority),
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

        $comments = $ticket->comments()
            ->where('is_internal', false)
            ->with('user')
            ->latest()
            ->get();

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
            'comments' => $comments
        ]);
    }

    /**
     * Add a comment to the ticket (client view).
     */
    public function storeComment(Request $request, Ticket $ticket)
    {
        Gate::authorize('comment', $ticket);

        $request->validate([
            'body' => ['required', 'string'],
        ]);

        $ticket->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
            'is_internal' => false,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }

    /**
     * Display a listing of all tickets for agents.
     */
    public function agentIndex(Request $request)
    {
        if (auth()->user()->role !== 'agent') {
            abort(403);
        }

        $tickets = Ticket::with(['creator', 'assignedAgent', 'organization'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                return $query->where('priority', $priority);
            })
            ->when($request->organization_id, function ($query, $orgId) {
                return $query->where('organization_id', $orgId);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        $agents = User::where('role', 'agent')->get();
        $organizations = \App\Models\Organization::all();

        return Inertia::render('Agent/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'priority', 'organization_id', 'search']),
            'agents' => $agents,
            'organizations' => $organizations,
        ]);
    }

    /**
     * Display the specified ticket (agent view).
     */
    public function agentShow(Ticket $ticket)
    {
        if (auth()->user()->role !== 'agent') {
            abort(403);
        }

        $ticket->load(['creator', 'assignedAgent', 'organization']);

        $comments = $ticket->comments()
            ->with('user')
            ->latest()
            ->get();

        $agents = User::where('role', 'agent')->get();

        return Inertia::render('Agent/Tickets/Show', [
            'ticket' => $ticket,
            'comments' => $comments,
            'agents' => $agents
        ]);
    }

    /**
     * Update the ticket status, priority, or assignee (agent support actions).
     */
    public function agentUpdate(Request $request, Ticket $ticket)
    {
        if (auth()->user()->role !== 'agent') {
            abort(403);
        }

        $request->validate([
            'status' => ['nullable', 'in:open,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $data = [];
        if ($request->has('status')) {
            $data['status'] = $request->status;
        }
        if ($request->has('priority')) {
            $data['priority'] = $request->priority;
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
    public function storeAgentComment(Request $request, Ticket $ticket)
    {
        if (auth()->user()->role !== 'agent') {
            abort(403);
        }

        $request->validate([
            'body' => ['required', 'string'],
            'is_internal' => ['required', 'boolean'],
        ]);

        $ticket->comments()->create([
            'user_id' => auth()->id(),
            'body' => $request->body,
            'is_internal' => $request->is_internal,
        ]);

        return back()->with('success', 'Comment added successfully.');
    }
}
