<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Attendance;
use App\Models\Channel;
use App\Models\FinalReport;
use App\Models\Logbook;
use App\Models\Message;
use App\Models\MicroSkillSubmission;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\Workspace;
use App\Policies\AttachmentPolicy;
use App\Policies\AttendancePolicy;
use App\Policies\ChannelPolicy;
use App\Policies\MessagePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TaskTemplatePolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Attendance::class => AttendancePolicy::class,
        Logbook::class => 'App\\Policies\\LogbookPolicy',
        MicroSkillSubmission::class => 'App\\Policies\\MicroSkillSubmissionPolicy',
        FinalReport::class => 'App\\Policies\\FinalReportPolicy',
        Workspace::class => WorkspacePolicy::class,
        Task::class => TaskPolicy::class,
        TaskTemplate::class => TaskTemplatePolicy::class,
        Channel::class => ChannelPolicy::class,
        Message::class => MessagePolicy::class,
        Attachment::class => AttachmentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user) {
            if ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            return null;
        });
    }
}
