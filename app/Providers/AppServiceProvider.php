<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\KsAgentRepositoryInterface;
use App\Repositories\KsAgentRepository;
use App\Models\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(KsAgentRepositoryInterface::class, KsAgentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
{
    Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
}
}
