<?php

namespace App\Providers;

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
        // Aquí irían mappings si usas Policies personalizadas
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // 🔑 Definimos un Gate para verificar si el usuario es Admin
        Gate::define('isAdmin', function ($user) {
            return $user->role === 'Admin';
        });

        // Configurar la duración del "remember me" token
        $this->configureRememberMeDuration();
    }

    /**
     * Configure the duration of the "remember me" session.
     */
    protected function configureRememberMeDuration(): void
    {
        // Establecer la duración del token "remember me" desde la configuración
        config(['session.lifetime' => config('auth.remember_duration', 43200)]);
    }
}
