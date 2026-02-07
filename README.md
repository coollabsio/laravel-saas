# Laravel SaaS

Teams, Stripe billing, and self-hosted mode for Laravel applications. Built on top of Laravel Cashier and Inertia.js.

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
- Register the package test suite in `phpunit.xml` and `tests/Pest.php`

### Publish Vue components

```bash
php artisan vendor:publish --tag=saas-vue
```

This copies Team, Billing, TeamInvitation, and TeamSwitcher Vue components into your app's `resources/js/` directory for customization.

### Publish the Plan enum (optional)

```bash
php artisan vendor:publish --tag=saas-plan
```

Copies a customizable `Plan` enum to `app/Enums/Plan.php`. Update `saas.plan_enum` in your config to point to it.

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
    ],
];
```

### Custom models

Extend the package models and update the config:

```php
// app/Models/Team.php
use Coollabsio\LaravelSaas\Models\Team as SaasTeam;

class Team extends SaasTeam
{
    // your customizations
}
```

### Custom Plan enum

Publish the stub, customize it, and update the config:

```bash
php artisan vendor:publish --tag=saas-plan
```

Your enum must implement `Coollabsio\LaravelSaas\Contracts\PlanContract`.

### Disabling routes

Set `routes.teams` or `routes.billing` to `false` in the config, then publish and customize the route files:

```bash
php artisan vendor:publish --tag=saas-routes
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

Both are bypassed automatically in self-hosted mode.

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
```

## Self-hosted mode

Set `SELF_HOSTED=true` to disable all billing. No Stripe keys needed. All features are unlocked — `Team::plan()` returns `Enterprise`.

| Concern | `SELF_HOSTED=true` | `SELF_HOSTED=false` |
|---------|--------------------|---------------------|
| Billing | Disabled | Enabled via Stripe |
| Features | All unlocked | Plan-based |
| `plan:pro` middleware | Always passes | Checks team plan |

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

## Artisan commands

```bash
# Clear cached Stripe prices
php artisan billing:clear-price-cache

# Install the package
php artisan saas:install
```

## Testing

The package ships its own feature tests. The `saas:install` command registers them in your app's `phpunit.xml` and `tests/Pest.php` so they run alongside your app tests:

```bash
php artisan test
```

## License

MIT
