# Lessons Learned

## Svelte
- `<svelte:head>` MUST be at the top level of a component, never nested inside other elements or components. Place it before/after `<AppLayout>`, not inside it.
