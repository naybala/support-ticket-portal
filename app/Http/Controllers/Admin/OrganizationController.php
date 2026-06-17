<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Organization;

class OrganizationController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => Organization::paginate(10)
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Organizations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255'
        ]);

        Organization::create($validated);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization created successfully.');
    }

    public function show(string $id)
    {
        $organization = Organization::findOrFail($id);
        return Inertia::render('Admin/Organizations/Show', ['organization' => $organization]);
    }

    public function edit(string $id)
    {
        $organization = Organization::findOrFail($id);
        return Inertia::render('Admin/Organizations/Edit', ['organization' => $organization]);
    }

    public function update(Request $request, string $id)
    {
        $organization = Organization::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255'
        ]);

        $organization->update($validated);

        return redirect()->route('admin.organizations.index')->with('success', 'Organization updated successfully.');
    }

    public function destroy(string $id)
    {
        $organization = Organization::findOrFail($id);
        $organization->delete();
        return redirect()->route('admin.organizations.index')->with('success', 'Organization deleted successfully.');
    }
}
