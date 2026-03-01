<script lang="ts">
    import type { Snippet } from 'svelte';
    import { Tooltip } from 'bits-ui';
    import { cn } from '@/lib/utils';

    let {
        class: className,
        sideOffset = 4,
        children,
        ...restProps
    }: {
        class?: string;
        sideOffset?: number;
        children?: Snippet;
        [key: string]: unknown;
    } = $props();
</script>

<Tooltip.Portal>
    <Tooltip.Content
        {sideOffset}
        class={cn(
            'bg-neutral-200 dark:bg-coolgray-400 text-neutral-700 dark:text-neutral-300 animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 z-50 w-fit rounded-sm px-3 py-1.5 text-xs text-balance',
            className,
        )}
        {...restProps}
    >
        {@render children?.()}
        <Tooltip.Arrow class="bg-neutral-200 dark:bg-coolgray-400 fill-neutral-200 dark:fill-coolgray-400 size-2.5 translate-y-[calc(-50%_-_2px)] rotate-45 rounded-[2px]" />
    </Tooltip.Content>
</Tooltip.Portal>
