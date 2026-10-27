<?php

namespace App\Providers;

use App\Models\Intern;
use App\Models\Mentor;
use App\Observers\InternObserver;
use App\Observers\MentorObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Intern::observe(InternObserver::class);
        Mentor::observe(MentorObserver::class);
    }
}
