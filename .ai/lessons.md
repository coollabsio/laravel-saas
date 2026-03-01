# Lessons Learned

## Svelte
- `<svelte:head>` MUST be at the top level of a component, never nested inside other elements or components. Place it before/after `<AppLayout>`, not inside it.
- Store subscriptions (`$store`) can only be used at the top level of a component, not inside functions. For one-shot requests inside functions, use `router.post()`/`router.get()` instead of creating a scoped `useForm` store.
