# Plan Gating

## Route Middleware

Gate routes by minimum plan tier:

```php
Route::get('/pro-feature', [FeatureController::class, 'index'])
    ->middleware('plan:pro');
```

Self-hosted mode and dynamic billing mode both bypass all plan checks automatically.

## Model Methods

```php
$team = $user->currentTeam;

$team->plan();              // Returns Plan enum (PlanContract)
$team->onPlan('pro');       // Exact match
$team->onPlan(Plan::Pro);   // Exact match (enum)
$team->canAccess('pro');    // Hierarchical: Pro or Enterprise
$team->canAccess('free');   // Always true
```

## Dynamic Billing Helpers

```php
// Quantity-based
$team->dynamicQuantity();              // Current subscription quantity
$team->updateDynamicQuantity(5);       // Update quantity on Stripe

// Metered
$team->reportUsage('event-name', 10); // Report metered usage event

// Status
$team->hasActiveDynamicSubscription(); // true if dynamic + subscribed
```

## Blade / Controller Checks

```php
if ($team->canAccess(Plan::Pro)) {
    // Pro+ feature
}
```

## Frontend

Shared Inertia props available on every page (via `ShareSaasProps` middleware):

```typescript
const page = usePage();
page.props.billing.enabled;     // boolean
page.props.billing.mode;        // 'tiered' | 'dynamic' | null
page.props.billing.currentPlan; // 'free' | 'pro' | 'enterprise' | null
```

## Adding a New Plan

1. Publish and customize the Plan enum: `php artisan vendor:publish --tag=saas-plan`
2. Add case to `app/Enums/Plan.php`
3. Update `config/saas.php` stripe prices section
4. Update `stripePriceId()` and `tier()` methods
5. Point `saas.plan_enum` to your custom enum
6. Create the product/price in Stripe Dashboard
