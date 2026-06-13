<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 2 organizations
        $org1 = Organization::create(['name' => 'Acme Corp']);
        $org2 = Organization::create(['name' => 'Stark Industries']);

        // 2 client users
        $client1 = User::create([
            'name' => 'Client One',
            'email' => 'client1@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'organization_id' => $org1->id,
        ]);

        $client2 = User::create([
            'name' => 'Client Two',
            'email' => 'client2@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'organization_id' => $org2->id,
        ]);

        // 2 agents
        $agent1 = User::create([
            'name' => 'Agent One',
            'email' => 'agent1@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'organization_id' => null,
        ]);

        $agent2 = User::create([
            'name' => 'Agent Two',
            'email' => 'agent2@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
            'organization_id' => null,
        ]);

        // Sample tickets
        $ticket1 = Ticket::create([
            'organization_id' => $org1->id,
            'created_by_user_id' => $client1->id,
            'assigned_to_user_id' => $agent1->id,
            'title' => 'Cannot log into client dashboard',
            'description' => 'I am getting a 500 error when clicking on the dashboard link.',
            'status' => 'open',
            'priority' => 'high',
            'sla_due_at' => now()->addHours(4),
        ]);

        $ticket2 = Ticket::create([
            'organization_id' => $org1->id,
            'created_by_user_id' => $client1->id,
            'assigned_to_user_id' => null,
            'title' => 'Request for pricing sheet',
            'description' => 'Please send us the Q3 pricing sheet for enterprise accounts.',
            'status' => 'open',
            'priority' => 'low',
            'sla_due_at' => now()->addDays(3),
        ]);

        $ticket3 = Ticket::create([
            'organization_id' => $org2->id,
            'created_by_user_id' => $client2->id,
            'assigned_to_user_id' => $agent2->id,
            'title' => 'Arc reactor output fluctuation',
            'description' => 'Power levels are dropping by 12% periodically.',
            'status' => 'in_progress',
            'priority' => 'high',
            'sla_due_at' => now()->subHours(1), // Overdue SLA
        ]);

        $ticket4 = Ticket::create([
            'organization_id' => $org2->id,
            'created_by_user_id' => $client2->id,
            'assigned_to_user_id' => $agent1->id,
            'title' => 'General invoice inquiry',
            'description' => 'Need clarification on last month\'s support hours billing.',
            'status' => 'resolved',
            'priority' => 'normal',
            'sla_due_at' => now()->addHours(24),
        ]);

        // Some comments
        Comment::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $client1->id,
            'body' => 'I tried clearing my browser cache but the issue persists.',
            'is_internal' => false,
        ]);

        Comment::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agent1->id,
            'body' => 'Checking logs right now. Internal note: Server is run out of memory.',
            'is_internal' => true,
        ]);

        Comment::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agent1->id,
            'body' => 'We have deployed a hotfix. Can you verify now?',
            'is_internal' => false,
        ]);

        Comment::create([
            'ticket_id' => $ticket3->id,
            'user_id' => $agent2->id,
            'body' => 'Internal note: Requires Jarvis upgrade.',
            'is_internal' => true,
        ]);
    }
}
