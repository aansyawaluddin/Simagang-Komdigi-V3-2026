<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTemplate extends Model
{
    protected $fillable = [
        'team_id',
        'created_by',
        'title',
        'description',
        'checklist',
        'is_onboarding',
    ];

    protected $casts = [
        'checklist' => 'array',
        'is_onboarding' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
