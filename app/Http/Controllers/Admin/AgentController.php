<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class AgentController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Agents/Index', [
            'agents' => \App\Models\User::where('role', 'agent')->orWhereHas('roles', function($q) {
                $q->where('name', 'agent');
            })->paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Agents/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $agent = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'role' => 'agent' // legacy
        ]);

        $agent->assignRole('agent'); // spatie

        return redirect()->route('admin.agents.index')->with('success', 'Agent created successfully.');
    }

    public function show(string $id)
    {
        $agent = User::findOrFail($id);
        return Inertia::render('Admin/Agents/Show', ['agent' => $agent]);
    }

    public function edit(string $id)
    {
        $agent = User::findOrFail($id);
        return Inertia::render('Admin/Agents/Edit', ['agent' => $agent]);
    }

    public function update(Request $request, string $id)
    {
        $agent = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $agent->id
        ]);

        $agent->update($validated);

        return redirect()->route('admin.agents.index')->with('success', 'Agent updated successfully.');
    }

    public function destroy(string $id)
    {
        $agent = User::findOrFail($id);
        $agent->delete();
        return redirect()->route('admin.agents.index')->with('success', 'Agent deleted successfully.');
    }
}
