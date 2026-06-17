<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TicketController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    if (auth()->user()->role === 'agent') {
        return redirect()->route('agent.tickets.index');
    }
    return redirect()->route('tickets.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Client tickets routes
    Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/comments', [TicketController::class, 'storeComment'])->name('tickets.comments.store');

    // Agent tickets routes
    Route::get('/agent/tickets', [TicketController::class, 'agentIndex'])->name('agent.tickets.index');
    Route::get('/agent/tickets/{ticket}', [TicketController::class, 'agentShow'])->name('agent.tickets.show');
    Route::patch('/agent/tickets/{ticket}', [TicketController::class, 'agentUpdate'])->name('agent.tickets.update');
    Route::post('/agent/tickets/{ticket}/comments', [TicketController::class, 'storeAgentComment'])->name('agent.tickets.comments.store');

    // Admin routes for dynamic RBAC & Entities
    Route::middleware(['role:super-admin|admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
        Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class);
        Route::resource('organizations', \App\Http\Controllers\Admin\OrganizationController::class);
        Route::resource('agents', \App\Http\Controllers\Admin\AgentController::class);
    });
});

require __DIR__.'/auth.php';
