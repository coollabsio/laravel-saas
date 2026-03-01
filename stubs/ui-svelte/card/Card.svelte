<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    let {
        class: className = '',
        children,
    }: {
        class?: string;
        children?: Snippet;
    } = $props();
</script>

<div data-slot="card" class={cn('bg-white dark:bg-coolgray-100 text-card-foreground flex flex-col gap-6 rounded-sm border border-neutral-200 dark:border-coolgray-300 py-6 shadow-sm', className)}>
    {@render children?.()}
</div>
