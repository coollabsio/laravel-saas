<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';

    let {
        children,
        ...rest
    }: {
        children?: Snippet;
        [key: string]: unknown;
    } = $props();

    const ctx = getContext<{ open: boolean; disabled: boolean; contentId: string }>('collapsible');
</script>

<button
    type="button"
    data-slot="collapsible-trigger"
    aria-expanded={ctx.open}
    aria-controls={ctx.contentId}
    disabled={ctx.disabled || undefined}
    onclick={() => { if (!ctx.disabled) ctx.open = !ctx.open; }}
    {...rest}
>
    {@render children?.()}
</button>
