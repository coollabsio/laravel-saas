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

{#if ctx.open}
    <div data-slot="collapsible-content" id={ctx.contentId} role="region" {...rest}>
        {@render children?.()}
    </div>
{/if}
