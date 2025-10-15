<?php

namespace App\Providers;

use App\Models\ApplicationForm;
use App\Models\User;
use App\Policies\ApplicationFormPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        ApplicationForm::class => ApplicationFormPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Definir gates adicionales si es necesario
        Gate::define('manage-users', function (User $user) {
            return $user->isAdmin() || $user->isAgent();
        });

        Gate::define('manage-clients-only', function (User $user) {
            return $user->isAgent();
        });

        Gate::define('manage-all-users', function (User $user) {
            return $user->isAdmin();
        });

        Gate::define('createUserType', function (User $user, string $type) {
            return match ($user->type) {
                'admin' => in_array($type, ['admin', 'agent', 'client']),
                'agent' => $type === 'client',
                default => false,
            };
        });
    }
}