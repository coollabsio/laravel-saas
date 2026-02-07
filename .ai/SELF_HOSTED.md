# Self-Hosted Mode

This package supports two deployment modes controlled by the `SELF_HOSTED` environment variable.

## Configuration

```env
SELF_HOSTED=true   # Self-hosted deployment
SELF_HOSTED=false  # Hosted/SaaS deployment (default)
```

Access in code via `config('saas.self_hosted')`.

## Behavior Differences

| Concern              | `SELF_HOSTED=true`                  | `SELF_HOSTED=false`                  |
|----------------------|-------------------------------------|--------------------------------------|
| Billing / Stripe     | Disabled — no payment integration   | Enabled — Stripe required            |
| Feature restrictions | None — all features unlocked        | Plan-based limits apply              |
| Usage limits         | None                                | Enforced per plan                    |

## Implementation Rules

- Gate self-hosted checks with `config('saas.self_hosted')`.
- Never require Stripe keys, webhooks, or billing routes when self-hosted.
- When adding a new restriction or paid feature, always include a self-hosted bypass.
- Keep the self-hosted path as the simpler code path — avoid unnecessary complexity.
- Billing is fully disabled in self-hosted mode. See [BILLING.md](BILLING.md) for details.
- The `plan:pro` middleware always passes in self-hosted mode. `Team::plan()` returns `Enterprise`.
- Tests should cover both modes where behavior diverges. Use config overrides in tests:
  ```php
  config(['saas.self_hosted' => true]);
  ```
