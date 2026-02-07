# Billing Architecture

Billing is powered by Laravel Cashier (Stripe) and is bound to the **Team** model, not individual Users.

## Deployment Modes

- **Self-hosted** (`SELF_HOSTED=true`): Billing disabled. All features unlocked. No Stripe keys needed.
- **Cloud** (`SELF_HOSTED=false`): Billing enabled. Team plan determines feature access.

## Billing Modes

When billing is enabled, it operates in one of two modes:

### Tiered Mode (default)

Traditional plan-based billing with Free/Pro/Enterprise tiers. Active when `STRIPE_DYNAMIC_PRICE_ID` is **not** set.

### Dynamic Mode

Usage-based billing. Active when `STRIPE_DYNAMIC_PRICE_ID` is set. Tier-based plan gating is bypassed (all teams get `Plan::Free`). Supports two pricing models (determined by how the Stripe Price is configured):

- **Quantity-based** (e.g. Coolify model): `$team->updateDynamicQuantity(5)` — pay per N resources
- **Metered** (e.g. tweet scheduler): `$team->reportUsage('event-name', 10)` — report usage events, Stripe aggregates and bills

Use `Billing::isDynamic()` / `Billing::isTiered()` / `Billing::mode()` to check.

## Plans

Defined in the plan enum (default: `Coollabsio\LaravelSaas\Enums\Plan`, customizable via `config('saas.plan_enum')`):

| Plan       | Tier | Stripe Price                   |
|------------|------|--------------------------------|
| Free       | 0    | None (no subscription)         |
| Pro        | 1    | `STRIPE_PRO_MONTHLY_PRICE_ID` / `STRIPE_PRO_YEARLY_PRICE_ID`                |
| Enterprise | 2    | `STRIPE_ENTERPRISE_MONTHLY_PRICE_ID` / `STRIPE_ENTERPRISE_YEARLY_PRICE_ID`  |

Each paid plan has both a monthly and yearly Stripe price. `Plan::stripePriceId(string $interval = 'monthly')` returns the correct price ID. The billing page shows a monthly/yearly toggle; checkout sends the selected interval.

Plans are hierarchical: Enterprise > Pro > Free. In dynamic mode, `plan()` always returns `Free`.

## Stripe Price Caching

`StripePrices::all()` fetches prices from the Stripe API and caches them for 1 month. Returns formatted prices, raw amounts, and yearly savings percentages. Used by `BillingController::index()` to pass prices to the frontend.

Clear the cache after changing prices in Stripe:

```bash
php artisan billing:clear-price-cache
```

## Require Subscription

Set `REQUIRE_SUBSCRIPTION=true` to require an active subscription to access the app. Unsubscribed teams are redirected to the billing page. Self-hosted mode bypasses this. Uses the `subscribed` middleware alias (`EnsureSubscribed`).

## Environment Variables

```env
STRIPE_KEY=pk_...
STRIPE_SECRET=sk_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRO_MONTHLY_PRICE_ID=price_...
STRIPE_PRO_YEARLY_PRICE_ID=price_...
STRIPE_ENTERPRISE_MONTHLY_PRICE_ID=price_...
STRIPE_ENTERPRISE_YEARLY_PRICE_ID=price_...
STRIPE_DYNAMIC_PRICE_ID=price_...
REQUIRE_SUBSCRIPTION=false
```

## Key Files

- `src/Enums/Plan.php` — default plan definitions and price ID mapping
- `src/Contracts/PlanContract.php` — interface for custom plan enums
- `src/Support/Billing.php` — `enabled()`, `isDynamic()`, `isTiered()`, `mode()`, `requiresSubscription()`
- `src/Support/StripePrices.php` — cached Stripe price fetching, `all()`, `clearCache()`
- `src/Console/BillingClearPriceCache.php` — `billing:clear-price-cache` artisan command
- `src/Concerns/HasBilling.php` — trait for Team model: `plan()`, `onPlan()`, `canAccess()`, `dynamicQuantity()`, `updateDynamicQuantity()`, `reportUsage()`
- `src/Http/Middleware/EnsurePlanAccess.php` — route middleware (`plan:pro`), bypassed in dynamic mode
- `src/Http/Middleware/EnsureSubscribed.php` — route middleware (`subscribed`), redirects to billing if unsubscribed
- `src/Http/Controllers/BillingController.php` — billing UI, checkout, portal
- `routes/billing.php` — billing routes

## Stripe Setup

1. Create Products + Prices in Stripe Dashboard
2. Set price IDs in `.env`
3. Configure webhook endpoint to `POST /stripe/webhook`
4. Set `STRIPE_WEBHOOK_SECRET`

## Local Stripe Webhook Setup

The Stripe CLI forwards webhook events from Stripe to your local development server.

### 1. Install Stripe CLI

```bash
# macOS
brew install stripe/stripe-cli/stripe

# Linux
# See https://docs.stripe.com/stripe-cli#install
```

### 2. Login

```bash
stripe login
```

Follow the browser prompt to authenticate with your Stripe account.

### 3. Forward webhooks

```bash
stripe listen --forward-to http://localhost:8000/stripe/webhook
```

The CLI will output a webhook signing secret (starts with `whsec_`). Copy it and set it in `.env`:

```env
STRIPE_WEBHOOK_SECRET=whsec_...
```

Keep this terminal running while developing. The secret changes each time `stripe listen` is restarted.

### 4. Trigger test events (optional)

```bash
# Trigger a specific event
stripe trigger checkout.session.completed

# Trigger a subscription lifecycle
stripe trigger customer.subscription.created
```

### Recommended events

Enable these in your Stripe Dashboard webhook settings for production:

- `checkout.session.completed`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`
- `invoice.payment_succeeded`
- `invoice.payment_failed`
