# Lessons Learned

## Svelte
- `<svelte:head>` MUST be at the top level of a component, never nested inside other elements or components. Place it before/after `<AppLayout>`, not inside it.
- Store subscriptions (`$store`) can only be used at the top level of a component, not inside functions. For one-shot requests inside functions, use `router.post()`/`router.get()` instead of creating a scoped `useForm` store.
- shadcn-svelte does NOT export `DialogHeader` — use a plain `<div class="flex flex-col space-y-1.5 text-center sm:text-left">` wrapper instead. Always check the actual `index.ts` exports of shadcn-svelte components before using them.
- Never destructure a variable named `state` in Svelte 5 — `$state` rune conflicts with Svelte's `$` store auto-subscription prefix. Use `const { state: sidebarState } = useSidebar()` instead.
- shadcn-svelte `asChild` pattern requires explicit snippet syntax: `{#snippet children(props)}<Component {...props}>...</Component>{/snippet}`. Without it, trigger props (onclick, aria-expanded) are silently lost.
- Inertia's `useForm` store doesn't reliably support `bind:value={$form.field}` in Svelte 5 — the store may not update on input. Use plain `$state` variables with `router.post()` instead for forms in dialogs.
- shadcn-svelte `Input` doesn't support `bind:value` with Svelte 5 `$state`. Use `value={myVar}` + `oninput={(e) => myVar = e.currentTarget.value}` instead.
