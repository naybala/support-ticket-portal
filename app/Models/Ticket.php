<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'assigned_to_user_id',
        'title',
        'description',
        'status',
        'priority',
        'sla_due_at',
    ];

    protected $casts = [
        'sla_due_at' => 'datetime',
    ];

    protected $appends = [
        'sla_state',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    public function assignedAgent()
    {
        return $this->belongsTo(
            User::class,
            'assigned_to_user_id'
        );
    }

    public function getSlaStateAttribute()
    {
        if (now()->greaterThan($this->sla_due_at)) {
            return 'overdue';
        }

        if (now()->diffInHours($this->sla_due_at) <= 2) {
            return 'due_soon';
        }

        return 'on_track';
    }
}
