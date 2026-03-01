<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import { cn } from '@/lib/utils';
    import { DROPDOWN_MENU_CONTEXT, type DropdownMenuContext } from './context';

    let {
        align = 'start',
        side = 'bottom',
        sideOffset = 0,
        class: className = '',
        children,
    }: {
        align?: 'start' | 'center' | 'end';
        side?: 'top' | 'right' | 'bottom' | 'left';
        sideOffset?: number;
        class?: string;
        children?: Snippet;
    } = $props();

    const { open, setOpen } = getContext<DropdownMenuContext>(DROPDOWN_MENU_CONTEXT);

    const alignClasses: Record<string, string> = {
        start: 'left-0',
        center: 'left-1/2 -translate-x-1/2',
        end: 'right-0',
    };

    const sideClasses: Record<string, string> = {
        bottom: 'top-full',
        top: 'bottom-full',
        left: 'right-full',
        right: 'left-full',
    };

    const close = () => setOpen(false);

    const offsetStyle = () => {
        switch (side) {
            case 'top':
                return `margin-bottom: ${sideOffset}px;`;
            case 'left':
                return `margin-right: ${sideOffset}px;`;
            case 'right':
                return `margin-left: ${sideOffset}px;`;
            default:
                return `margin-top: ${sideOffset}px;`;
        }
    };

    let menuEl: HTMLDivElement | undefined = $state();

    const focusItem = (direction: 'first' | 'last' | 'next' | 'prev') => {
        if (!menuEl) return;
        const items = Array.from(menuEl.querySelectorAll<HTMLElement>('[role="menuitem"]:not([disabled])'));
        if (items.length === 0) return;
        const current = items.indexOf(document.activeElement as HTMLElement);
        let target: HTMLElement;
        if (direction === 'first') target = items[0];
        else if (direction === 'last') target = items[items.length - 1];
        else if (direction === 'next') target = items[(current + 1) % items.length];
        else target = items[(current - 1 + items.length) % items.length];
        target.focus();
    };

    $effect(() => {
        if (!open()) return;

        const onKeydown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            }
        };

        document.addEventListener('keydown', onKeydown);
        requestAnimationFrame(() => focusItem('first'));

        return () => document.removeEventListener('keydown', onKeydown);
    });

    const handleKeydown = (event: KeyboardEvent) => {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                focusItem('next');
                break;
            case 'ArrowUp':
                event.preventDefault();
                focusItem('prev');
                break;
            case 'Home':
                event.preventDefault();
                focusItem('first');
                break;
            case 'End':
                event.preventDefault();
                focusItem('last');
                break;
        }
    };
</script>

{#if open()}
    <div
        bind:this={menuEl}
        class={cn(
            'absolute z-50 min-w-48 rounded-sm border border-neutral-300 dark:border-coolgray-300 bg-white dark:bg-coolgray-200 p-1 text-popover-foreground dark:text-white shadow-sm',
            alignClasses[align] ?? alignClasses.start,
            sideClasses[side] ?? sideClasses.bottom,
            className,
        )}
        style={offsetStyle()}
        role="menu"
        tabindex="-1"
        onkeydown={handleKeydown}
    >
        {@render children?.()}
    </div>
{/if}
