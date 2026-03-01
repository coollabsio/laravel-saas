<script lang="ts">
    import type { Snippet } from 'svelte';
    import { setContext } from 'svelte';

    let {
        open = $bindable(false),
        disabled = false,
        children,
    }: {
        open?: boolean;
        disabled?: boolean;
        children?: Snippet;
    } = $props();

    const contentId = `collapsible-${Math.random().toString(36).slice(2, 9)}`;

    setContext('collapsible', {
        get open() { return open; },
        set open(v: boolean) { open = v; },
        get disabled() { return disabled; },
        contentId,
    });
</script>

<div data-slot="collapsible" data-state={open ? 'open' : 'closed'} data-disabled={disabled || undefined}>
    {@render children?.()}
</div>
