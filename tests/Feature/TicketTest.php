<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Client cannot access a ticket belonging to another organization.
     */
    public function test_client_cannot_access_another_organization_ticket(): void
    {
        // Create two organizations
        $org1 = Organization::factory()->create(['name' => 'Org One']);
        $org2 = Organization::factory()->create(['name' => 'Org Two']);

        // Create client user 1 in org 1
        $client1 = User::factory()->create([
            'role' => 'client',
            'organization_id' => $org1->id,
        ]);

        // Create client user 2 in org 2
        $client2 = User::factory()->create([
            'role' => 'client',
            'organization_id' => $org2->id,
        ]);

        // Create a ticket for org 2 (created by client 2)
        $ticket = Ticket::factory()->create([
            'organization_id' => $org2->id,
            'created_by_user_id' => $client2->id,
        ]);

        // Act as client 1 and attempt to view client 2's ticket
        $response = $this->actingAs($client1)->get(route('tickets.show', $ticket->id));

        // Assert Forbidden status
        $response->assertStatus(403);
    }

    /**
     * Test: Client cannot see internal comments on a ticket.
     */
    public function test_client_cannot_see_internal_comments(): void
    {
        // Create organization and user
        $org = Organization::factory()->create();
        $client = User::factory()->create([
            'role' => 'client',
            'organization_id' => $org->id,
        ]);

        // Create a ticket
        $ticket = Ticket::factory()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $client->id,
        ]);

        // Create public comment
        $publicComment = Comment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $client->id,
            'body' => 'This is a public comment',
            'is_internal' => false,
        ]);

        // Create internal comment (note)
        $internalComment = Comment::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => User::factory()->create(['role' => 'agent'])->id,
            'body' => 'This is a private internal comment',
            'is_internal' => true,
        ]);

        // Act as client and GET the ticket show page
        $response = $this->actingAs($client)->get(route('tickets.show', $ticket->id));

        $response->assertStatus(200);

        // Assert comments collection passed to Vue contains ONLY the public comment
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Tickets/Show')
            ->has('comments', 1)
            ->where('comments.0.id', $publicComment->id)
            ->where('comments.0.body', 'This is a public comment')
        );
    }

    /**
     * Test: High priority ticket creation yields a 4-hour SLA due date.
     */
    public function test_high_priority_creates_4h_sla(): void
    {
        // Create organization and user
        $org = Organization::factory()->create();
        $client = User::factory()->create([
            'role' => 'client',
            'organization_id' => $org->id,
        ]);

        // Act as client and submit a high priority ticket
        $response = $this->actingAs($client)->post(route('tickets.store'), [
            'title' => 'Urgent Server Crash',
            'description' => 'Our main production DB is failing requests.',
            'priority' => 'high',
        ]);

        // Should redirect to the show page of the new ticket
        $response->assertRedirect();

        // Check that the ticket was created in DB with 4-hour SLA
        $ticket = Ticket::first();
        $this->assertNotNull($ticket);
        $this->assertEquals('high', $ticket->priority);
        $this->assertEquals('open', $ticket->status);

        // SLA due time should be approximately now + 4 hours (within 5 seconds tolerance)
        $expectedSlaTime = now()->addHours(4);
        $this->assertTrue(
            $ticket->sla_due_at->between(
                $expectedSlaTime->copy()->subSeconds(5),
                $expectedSlaTime->copy()->addSeconds(5)
            ),
            "SLA due date was not set to 4 hours in future. Got: {$ticket->sla_due_at}, expected: {$expectedSlaTime}"
        );
    }

    /**
     * Test: Agent can filter tickets by organization.
     */
    public function test_agent_can_filter_tickets_by_organization(): void
    {
        $org1 = Organization::factory()->create(['name' => 'Org One']);
        $org2 = Organization::factory()->create(['name' => 'Org Two']);

        $agent = User::factory()->create(['role' => 'agent']);

        // Ticket for Org 1
        $ticket1 = Ticket::factory()->create([
            'organization_id' => $org1->id,
            'created_by_user_id' => User::factory()->create(['organization_id' => $org1->id])->id,
        ]);

        // Ticket for Org 2
        $ticket2 = Ticket::factory()->create([
            'organization_id' => $org2->id,
            'created_by_user_id' => User::factory()->create(['organization_id' => $org2->id])->id,
        ]);

        // Access agent index with filter for Org 1
        $response = $this->actingAs($agent)->get(route('agent.tickets.index', [
            'organization_id' => $org1->id
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Agent/Tickets/Index')
            ->has('tickets', 1)
            ->where('tickets.0.id', $ticket1->id)
        );
    }

    /**
     * Test: Agent can search tickets by title/description.
     */
    public function test_agent_can_search_tickets(): void
    {
        $org = Organization::factory()->create();
        $agent = User::factory()->create(['role' => 'agent']);
        $client = User::factory()->create(['organization_id' => $org->id]);

        // Ticket matching search term
        $ticket1 = Ticket::factory()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $client->id,
            'title' => 'Database connection timeout issue',
            'description' => 'We are seeing random timeout errors from the DB.',
        ]);

        // Ticket NOT matching search term
        $ticket2 = Ticket::factory()->create([
            'organization_id' => $org->id,
            'created_by_user_id' => $client->id,
            'title' => 'New user account request',
            'description' => 'Please create a new account for our sales manager.',
        ]);

        // Search for 'timeout'
        $response = $this->actingAs($agent)->get(route('agent.tickets.index', [
            'search' => 'timeout'
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Agent/Tickets/Index')
            ->has('tickets', 1)
            ->where('tickets.0.id', $ticket1->id)
        );
    }
}
