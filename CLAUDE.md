# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

A Laravel package (`coollabsio/laravel-saas`) providing teams, Stripe billing, and self-hosted mode. Built on Laravel Cashier, Inertia.js (Vue 3). Not a standalone app — tested with Orchestra Testbench.

## Commands

```bash
# Run tests (Pest + Orchestra Testbench, SQLite in-memory)
./vendor/bin/pest
./vendor/bin/pest tests/Feature/TeamTest.php          # single file
./vendor/bin/pest --filter="test name"                 # single test
```

No build step. No linter configured.

## Architecture

- **Namespace**: `Coollabsio\LaravelSaas`
- **Service Provider**: `src/LaravelSaasServiceProvider.php` — registers config, routes, migrations, middleware aliases (`plan`, `subscribed`), policies, Cashier customer model
- **Two deployment modes**: cloud (Stripe billing) vs self-hosted (`SELF_HOSTED=true`, all features unlocked, no Stripe)
- **Two billing modes** (cloud only): tiered (Free/Pro/Enterprise) vs dynamic (usage-based, when `STRIPE_DYNAMIC_PRICE_ID` set)
- **Billing is on Team, not User** — Team is the Cashier customer model

### Key abstractions

- `src/Support/Billing.php` — static helpers: `enabled()`, `isDynamic()`, `isTiered()`, `mode()`, `teamModel()`
- `src/Contracts/PlanContract.php` — interface custom Plan enums must implement
- `src/Enums/Plan.php` — default plan enum with tier hierarchy and Stripe price ID mapping
- `src/Concerns/HasTeams.php` — trait for User model
- `src/Concerns/HasBilling.php` — trait for Team model (`plan()`, `canAccess()`, dynamic billing methods)
- `src/Concerns/CreatesPersonalTeam.php` — trait for registration action

### Routes

Package auto-registers team and billing routes (toggleable via `config('saas.routes.teams')` / `config('saas.routes.billing')`).

### Publishable stubs

`stubs/` contains Vue components and Plan enum for end-user customization.

## Self-Hosted Rules

- Gate with `config('saas.self_hosted')`, never hardcode
- Always include self-hosted bypass when adding paid features
- `plan:pro` middleware and `subscribed` middleware auto-bypass in self-hosted mode
- Tests should cover both modes using `config(['saas.self_hosted' => true])`

## Install Command Rules

When adding new publishable assets (Vue stubs, route files, config keys), always update `src/Console/InstallCommand.php`:
- Add new Vue stubs to `vueStubs()` array
- Add new route files to `routeStubs()` array
- Fresh install (`saas:install`) auto-publishes all stubs via `vendor:publish`
- Update install (`saas:install --update`) only copies files that don't exist yet (won't overwrite user customizations), and force-updates config

## Context Files

`.ai/` directory contains detailed architecture docs: `BILLING.md`, `PLAN_GATING.md`, `SELF_HOSTED.md`. Read these before working on billing or plan-gating features.
