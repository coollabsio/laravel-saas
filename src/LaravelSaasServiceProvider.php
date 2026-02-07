<?php

namespace Coollabsio\LaravelSaas;

use Coollabsio\LaravelSaas\Console\BillingClearPriceCache;
use Coollabsio\LaravelSaas\Http\Middleware\EnsurePlanAccess;
use Coollabsio\LaravelSaas\Http\Middleware\EnsureSubscribed;
use Coollabsio\LaravelSaas\Policies\TeamPolicy;
use Coollabsio\LaravelSaas\Support\Billing;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class LaravelSaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/saas.php', 'saas');

        $this->app->booted(function () {
            if (! Billing::enabled()) {
                Cashier::ignoreRoutes();
            }
        });
    }

    public function boot(): void
    {
        $this->configurePublishing();
        $this->configureRoutes();
        $this->configureMigrations();
        $this->configureMiddleware();
        $this->configurePolicies();
        $this->configureCommands();
        $this->configureCashier();
        $this->configureViews();
    }

    protected function configurePublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/saas.php' => config_path('saas.php'),
        ], 'saas-config');

        $this->publishes([
            __DIR__.'/../stubs/Plan.php' => app_path('Enums/Plan.php'),
        ], 'saas-plan');

        $this->publishes([
            __DIR__.'/../stubs/Team.vue' => resource_path('js/pages/settings/Team.vue'),
            __DIR__.'/../stubs/Billing.vue' => resource_path('js/pages/settings/Billing.vue'),
            __DIR__.'/../stubs/TeamInvitation.vue' => resource_path('js/pages/TeamInvitation.vue'),
            __DIR__.'/../stubs/TeamSwitcher.vue' => resource_path('js/components/TeamSwitcher.vue'),
        ], 'saas-vue');

        $this->publishes([
            __DIR__.'/../routes/teams.php' => base_path('routes/saas-teams.php'),
            __DIR__.'/../routes/billing.php' => base_path('routes/saas-billing.php'),
        ], 'saas-routes');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'saas-migrations');
    }

    protected function configureRoutes(): void
    {
        if (config('saas.routes.teams', true)) {
            Route::group([], __DIR__.'/../routes/teams.php');
        }

        if (config('saas.routes.billing', true)) {
            Route::group([], __DIR__.'/../routes/billing.php');
        }
    }

    protected function configureMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function configureMiddleware(): void
    {
        Route::aliasMiddleware('plan', EnsurePlanAccess::class);
        Route::aliasMiddleware('subscribed', EnsureSubscribed::class);
    }

    protected function configurePolicies(): void
    {
        Gate::policy(Billing::teamModel(), TeamPolicy::class);
    }

    protected function configureCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                BillingClearPriceCache::class,
                Console\InstallCommand::class,
            ]);
        }
    }

    protected function configureCashier(): void
    {
        if (Billing::enabled()) {
            Cashier::useCustomerModel(Billing::teamModel());
        }
    }

    protected function configureViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-saas');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-saas'),
        ], 'saas-views');
    }
}
