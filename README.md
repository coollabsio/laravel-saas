# Laravel SaaS

Teams, Stripe billing, and self-hosted mode for Laravel applications. Built on top of Laravel Cashier and Inertia.js.

Used for upcoming apps at coolLabs. 

> Andras: It is opinionated, so maybe it is not useful for you. 

## Features

- **Teams** — create, manage, switch teams; invite members via email
- **Billing** — tiered plans (Free/Pro/Enterprise) or dynamic usage-based billing via Stripe
- **Self-hosted mode** — disable billing entirely, unlock all features
- **Plan gating** — middleware and model methods to restrict features by plan
- **Inertia integration** — shared props middleware + publishable Vue components

## Requirements

- PHP 8.2+
- Laravel 12
- Laravel Cashier 16+
- Inertia.js v2 (with Vue 3)

## Installation

```bash
composer require coollabsio/laravel-saas
php artisan saas:install
php artisan migrate
```

The install command will:
- Publish `config/saas.php`
- Publish all Vue components and route files
- Register the package test suite in `phpunit.xml` and `tests/Pest.php`

### Updating after a package upgrade

```bash
composer update coollabsio/laravel-saas
php artisan saas:install --update
php artisan migrate
```

The `--update` flag will:
- Force-update all managed Vue stubs (see [Published files](#published-files))
- Force-update `config/saas.php` with new keys
- Publish any new route files that don't exist yet

### Publish the Plan enum (optional)

```bash
php artisan vendor:publish --tag=saas-plan
```

Copies a customizable `Plan` enum to `app/Enums/Plan.php`. Update `saas.plan_enum` in your config to point to it.

### Published files

The install command publishes files into your app. These are split into two categories:

**Managed stubs** — overwritten on every `saas:install --update`. Do not customize these directly; extend or wrap them instead.

| File | Location |
|------|----------|
| Team settings page | `resources/js/pages/settings/Team.vue` |
| Billing settings page | `resources/js/pages/settings/Billing.vue` |
| Instance settings page | `resources/js/pages/settings/Instance.vue` |
| Team invitation page | `resources/js/pages/TeamInvitation.vue` |
| Team switcher component | `resources/js/components/TeamSwitcher.vue` |
| Checkbox component | `resources/js/components/NativeCheckbox.vue` |

**User-owned files** — published once, never overwritten. Safe to customize.

| File | Location |
|------|----------|
| Configuration | `config/saas.php` (keys are merged on `--update`) |
| Team routes | `routes/saas-teams.php` |
| Billing routes | `routes/saas-billing.php` |
| Instance routes | `routes/saas-instance.php` |
| Plan enum | `app/Enums/Plan.php` (via `--tag=saas-plan`) |

### Other publishable groups

| Tag | Description |
|-----|-------------|
| `saas-config` | Configuration file |
| `saas-vue` | Vue components (stubs) |
| `saas-plan` | Plan enum (stub) |
| `saas-routes` | Route files for full override |
| `saas-migrations` | Migration files |
| `saas-views` | Mail views |

## Setup

### 1. User model

Add the `HasTeams` trait to your User model:

```php
use Coollabsio\LaravelSaas\Concerns\HasTeams;

class User extends Authenticatable
{
    use HasTeams;
}
```

Your `users` table does not need a `current_team_id` column — the package migration adds it automatically.

### 2. Registration

Add the `CreatesPersonalTeam` trait to your Fortify `CreateNewUser` action:

```php
use Coollabsio\LaravelSaas\Concerns\CreatesPersonalTeam;

class CreateNewUser implements CreatesNewUsers
{
    use CreatesPersonalTeam;

    public function create(array $input): User
    {
        // ... validate and create user ...

        return DB::transaction(function () use ($input) {
            $user = User::create([...]);
            $this->createPersonalTeam($user);
            return $user;
        });
    }
}
```

### 3. Middleware

Add `ShareSaasProps` to your web middleware stack in `bootstrap/app.php`:

```php
use Coollabsio\LaravelSaas\Http\Middleware\ShareSaasProps;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        ShareSaasProps::class,
    ]);
})
```

This shares `currentTeam`, `teams`, and `billing` props with every Inertia page.

### 4. Environment variables

```env
# Deployment mode
SELF_HOSTED=false

# Stripe (not needed when SELF_HOSTED=true)
STRIPE_KEY=pk_...
STRIPE_SECRET=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Tiered billing prices
STRIPE_PRO_MONTHLY_PRICE_ID=price_...
STRIPE_PRO_YEARLY_PRICE_ID=price_...
STRIPE_ENTERPRISE_MONTHLY_PRICE_ID=price_...
STRIPE_ENTERPRISE_YEARLY_PRICE_ID=price_...

# OR dynamic billing (set this instead of tiered prices)
STRIPE_DYNAMIC_PRICE_ID=price_...

# Require active subscription to access the app
REQUIRE_SUBSCRIPTION=false
```

## Configuration

All configuration lives in `config/saas.php`:

```php
return [
    'self_hosted' => env('SELF_HOSTED', false),
    'require_subscription' => env('REQUIRE_SUBSCRIPTION', false),

    'models' => [
        'team' => \App\Models\Team::class,
        'team_invitation' => \App\Models\TeamInvitation::class,
        'user' => \App\Models\User::class,
        'instance_settings' => \Coollabsio\LaravelSaas\Models\InstanceSettings::class,
    ],

    'plan_enum' => \Coollabsio\LaravelSaas\Enums\Plan::class,

    'stripe' => [
        'prices' => [
            'pro' => [
                'monthly' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
                'yearly' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
            ],
            'enterprise' => [
                'monthly' => env('STRIPE_ENTERPRISE_MONTHLY_PRICE_ID'),
                'yearly' => env('STRIPE_ENTERPRISE_YEARLY_PRICE_ID'),
            ],
        ],
        'dynamic_price_id' => env('STRIPE_DYNAMIC_PRICE_ID'),
    ],

    'routes' => [
        'teams' => true,
        'billing' => true,
        'instance' => true,
    ],
];
```

## Customization

The package is designed to be extended at every layer. Here's how to customize each part.

### Models

Extend the package models and update `config/saas.php` to point to your subclass:

```php
// app/Models/Team.php
use Coollabsio\LaravelSaas\Models\Team as SaasTeam;

class Team extends SaasTeam
{
    // add relationships, scopes, accessors, etc.
}
```

```php
// config/saas.php
'models' => [
    'team' => \App\Models\Team::class,
    // ...
],
```

The same pattern works for `InstanceSettings` if you need to add custom settings columns:

```php
// app/Models/InstanceSettings.php
use Coollabsio\LaravelSaas\Models\InstanceSettings as SaasInstanceSettings;

class InstanceSettings extends SaasInstanceSettings
{
    protected $fillable = ['registration_enabled', 'your_custom_field'];
}
```

### Plan enum

Publish the stub and customize tiers, Stripe price mappings, and hierarchy:

```bash
php artisan vendor:publish --tag=saas-plan
```

Your enum must implement `Coollabsio\LaravelSaas\Contracts\PlanContract`. Update the config:

```php
'plan_enum' => \App\Enums\Plan::class,
```

### Routes

The package auto-registers routes from `vendor/`. To override them, disable the package routes and publish your own:

```php
// config/saas.php
'routes' => [
    'teams' => false,      // disable package team routes
    'billing' => false,    // disable package billing routes
    'instance' => false,   // disable package instance routes
],
```

```bash
php artisan vendor:publish --tag=saas-routes
```

Then load the published routes in your app's `routes/web.php` or `bootstrap/app.php`.

### Controllers

The package controllers are not directly customizable. To override behavior:

1. Disable the relevant package routes (see above)
2. Create your own controller extending the package controller
3. Register your own routes pointing to your controller

```php
// app/Http/Controllers/TeamController.php
use Coollabsio\LaravelSaas\Http\Controllers\TeamController as SaasTeamController;

class TeamController extends SaasTeamController
{
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        // custom logic before
        $response = parent::store($request);
        // custom logic after
        return $response;
    }
}
```

### Middleware

The package registers these middleware automatically:

| Alias | Class | Purpose |
|-------|-------|---------|
| `plan` | `EnsurePlanAccess` | Gate routes by minimum plan tier |
| `subscribed` | `EnsureSubscribed` | Require active subscription |
| `root` | `EnsureRootUser` | Require root user (self-hosted) |
| — | `CheckRegistrationEnabled` | Global; blocks `/register` when disabled |
| — | `ShareSaasProps` | Must be added manually to web middleware |

To override a middleware, register your own alias with the same name in `bootstrap/app.php` (after the package boots):

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'plan' => \App\Http\Middleware\CustomPlanAccess::class,
    ]);
})
```

### Vue components (managed stubs)

The published Vue stubs are **managed** — they get overwritten on `saas:install --update`. To customize the UI without losing changes on update:

1. **Wrap the component** — create your own component that imports and wraps the managed stub
2. **Use slots/props** — if the stub supports them
3. **Copy and detach** — copy the stub to a new filename and use that instead. The managed stub will still be updated but your copy won't be affected.

```vue
<!-- resources/js/components/MyTeamSwitcher.vue -->
<script setup>
import TeamSwitcher from '@/components/TeamSwitcher.vue';
</script>

<template>
    <div>
        <TeamSwitcher />
        <!-- your additions here -->
    </div>
</template>
```

### Traits

The package provides traits you add to your models. To customize their behavior, override the trait methods in your model:

```php
class User extends Authenticatable
{
    use HasTeams;

    // Override to customize root user logic
    public function isRootUser(): bool
    {
        // custom logic
        return $this->is_admin && config('saas.self_hosted');
    }
}
```

### Listeners

The package registers a `LockRegistrationAfterRootUser` listener on `Illuminate\Auth\Events\Registered` (self-hosted mode only). To disable or replace it, create your own listener and disable the package's in a service provider:

```php
use Coollabsio\LaravelSaas\Listeners\LockRegistrationAfterRootUser;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

// In a service provider's boot() method:
Event::forget(Registered::class, LockRegistrationAfterRootUser::class);
```

## Usage

### Teams

```php
$user->teams;                    // all teams
$user->currentTeam;              // active team
$user->ownedTeams;               // teams the user owns
$user->switchTeam($team);        // switch active team
$user->teamRole($team);          // TeamRole enum (Owner/Member)
$user->isOwnerOf($team);         // bool

$team->owner;                    // team owner
$team->users;                    // all members
$team->invitations;              // pending invitations
$team->isOwner($user);           // bool
$team->hasUser($user);           // bool
$team->isPersonalTeam();         // bool
```

### Billing

```php
$team->plan();                   // PlanContract enum (Free/Pro/Enterprise)
$team->onPlan('pro');            // exact match
$team->canAccess('pro');         // hierarchical: Pro or higher
$team->subscribed();             // has active subscription (Cashier)
```

### Dynamic billing

```php
$team->hasActiveDynamicSubscription();  // bool
$team->dynamicQuantity();               // current quantity
$team->updateDynamicQuantity(5);        // update on Stripe
$team->reportUsage('event-name', 10);   // report metered usage
```

### Route middleware

```php
// Require minimum plan tier
Route::get('/pro-feature', Controller::class)->middleware('plan:pro');

// Require active subscription
Route::get('/app', Controller::class)->middleware('subscribed');
```

Both are bypassed automatically in self-hosted mode and for root users.

### Frontend (Inertia)

The `ShareSaasProps` middleware shares these props on every page:

```typescript
const page = usePage();

page.props.currentTeam;              // current team object
page.props.teams;                    // all user teams
page.props.billing.enabled;          // boolean
page.props.billing.mode;             // 'tiered' | 'dynamic' | null
page.props.billing.currentPlan;      // 'free' | 'pro' | 'enterprise' | null
page.props.billing.requiresSubscription; // boolean

page.props.instance.selfHosted;          // boolean
page.props.instance.isRootUser;          // boolean
page.props.instance.registrationEnabled; // boolean
```

## Self-hosted mode

Set `SELF_HOSTED=true` to disable all billing. No Stripe keys needed. All features are unlocked — `Team::plan()` returns `Enterprise`.

| Concern | `SELF_HOSTED=true` | `SELF_HOSTED=false` |
|---------|--------------------|---------------------|
| Billing | Disabled | Enabled via Stripe |
| Features | All unlocked | Plan-based |
| `plan:pro` middleware | Always passes | Checks team plan (root bypasses) |
| Root user | First registered user | N/A |
| Registration | Locked after first user | Always open |
| Instance settings | Available at `/settings/instance` | N/A |

### Root user

The first user to register in self-hosted mode becomes the **root user**. Their personal team is marked with `is_root = true`. Any owner of the root team has root privileges.

```php
$user->isRootUser();       // true if owner of root team + self-hosted
$team->isRootTeam();       // true if is_root column is true
```

### Instance settings

Root users can access `/settings/instance` to manage instance-wide settings. Currently available settings:

- **Registration enabled** — toggle whether new users can register. Automatically disabled after the first user registers.

The `CheckRegistrationEnabled` middleware (auto-registered globally) redirects `/register` to `/login` when registration is disabled.

### Shared Inertia props (self-hosted)

The `ShareSaasProps` middleware includes an `instance` prop:

```typescript
page.props.instance.selfHosted;          // boolean
page.props.instance.isRootUser;          // boolean
page.props.instance.registrationEnabled; // boolean
```

## Routes

The package registers these routes automatically:

### Teams
| Method | URI | Name |
|--------|-----|------|
| POST | `/teams` | `teams.store` |
| GET | `/settings/team` | `teams.edit` |
| PATCH | `/teams/{team}` | `teams.update` |
| DELETE | `/teams/{team}` | `teams.destroy` |
| PUT | `/teams/{team}/switch` | `teams.switch` |
| POST | `/teams/{team}/invitations` | `team-invitations.store` |
| DELETE | `/teams/{team}/invitations/{invitation}` | `team-invitations.destroy` |
| GET | `/invitations/{token}` | `team-invitations.accept` |
| POST | `/invitations/{token}` | `team-invitations.process` |

### Billing
| Method | URI | Name |
|--------|-----|------|
| GET | `/settings/billing` | `billing.index` |
| POST | `/billing/checkout` | `billing.checkout` |
| GET | `/billing/portal` | `billing.portal` |
| POST | `/stripe/webhook` | `cashier.webhook` |

### Instance (self-hosted only)
| Method | URI | Name |
|--------|-----|------|
| GET | `/settings/instance` | `instance-settings.edit` |
| PATCH | `/settings/instance` | `instance-settings.update` |

## Artisan commands

```bash
# Install the package
php artisan saas:install

# Update after a package upgrade
php artisan saas:install --update

# Clear cached Stripe prices
php artisan billing:clear-price-cache
```

## Testing

The package ships its own feature tests. The `saas:install` command registers them in your app's `phpunit.xml` and `tests/Pest.php` so they run alongside your app tests:

```bash
php artisan test
```

## License

MIT
