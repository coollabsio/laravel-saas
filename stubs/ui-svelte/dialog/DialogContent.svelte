<script lang="ts">
    import type { Snippet } from 'svelte';
    import { getContext } from 'svelte';
    import X from 'lucide-svelte/icons/x';
    import { cn } from '@/lib/utils';
    import { DIALOG_CONTEXT, type DialogContext } from './context';

    let {
        showCloseButton = true,
        class: className = '',
        children,
    }: {
        showCloseButton?: boolean;
        class?: string;
        children?: Snippet;
    } = $props();

    const { open, setOpen } = getContext<DialogContext>(DIALOG_CONTEXT);

    const close = () => setOpen(false);

    let previouslyFocused: HTMLElement | null = null;
    let dialogEl: HTMLDivElement | undefined = $state();

    // Global ESC listener — works regardless of focus
    $effect(() => {
        if (!open()) return;

        previouslyFocused = document.activeElement as HTMLElement;

        const onKeydown = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                close();
            }
            // Focus trap: Tab/Shift+Tab cycles within the dialog
            if (e.key === 'Tab' && dialogEl) {
                const focusable = dialogEl.querySelectorAll<HTMLElement>(
                    'a[href], button:not([disabled]), textarea, input:not([disabled]), select, [tabindex]:not([tabindex="-1"])',
                );
                if (focusable.length === 0) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (e.shiftKey && document.activeElement === first) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && document.activeElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        };

        document.addEventListener('keydown', onKeydown);

        // Auto-focus [autofocus] element if present, otherwise the dialog panel
        requestAnimationFrame(() => {
            const autofocusEl = dialogEl?.querySelector<HTMLElement>('[autofocus]');
            (autofocusEl ?? dialogEl)?.focus();
        });

        return () => {
            document.removeEventListener('keydown', onKeydown);
            // Restore focus to previously focused element
            previouslyFocused?.focus();
        };
    });
</script>

{#if open()}
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <button
            type="button"
            class="fixed inset-0 bg-black/50"
            aria-label="Close"
            onclick={close}
        ></button>
        <div
            bind:this={dialogEl}
            class={cn(
                'relative z-10 w-full max-w-lg rounded-sm border border-neutral-200 dark:border-coolgray-300 bg-white dark:bg-base p-6 shadow-lg',
                className,
            )}
            role="dialog"
            aria-modal="true"
            tabindex="-1"
        >
            {#if showCloseButton}
                <button
                    type="button"
                    class="ring-offset-background focus-visible:ring-ring absolute top-4 right-4 rounded-xs opacity-70 transition-opacity hover:opacity-100 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden disabled:pointer-events-none"
                    aria-label="Close"
                    onclick={close}
                >
                    <X class="size-4" />
                    <span class="sr-only">Close</span>
                </button>
            {/if}
            {@render children?.()}
        </div>
    </div>
{/if}
