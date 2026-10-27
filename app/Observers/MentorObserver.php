<?php

namespace App\Observers;

use App\Models\Mentor;
use App\Services\WorkspaceProvisioner;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class MentorObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private WorkspaceProvisioner $provisioner)
    {
    }

    public function created(Mentor $mentor): void
    {
        $this->sync($mentor);
    }

    public function updated(Mentor $mentor): void
    {
        if ($mentor->wasChanged(['is_active', 'team_id', 'user_id'])) {
            $this->sync($mentor);
        }
    }

    /** Kegagalan tidak boleh menggagalkan penyimpanan mentor; perbaiki lewat `workspace:backfill`. */
    private function sync(Mentor $mentor): void
    {
        try {
            $this->provisioner->syncMentor($mentor);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
