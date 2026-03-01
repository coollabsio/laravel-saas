<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Variant =
        | 'default'
        | 'secondary'
        | 'ghost'
        | 'destructive'
        | 'outline'
        | 'link'
        | 'highlighted';
    type Size = 'default' | 'sm' | 'lg' | 'icon' | 'icon-sm' | 'icon-lg';
    type AsChildProps = {
        class?: string;
        onClick?: (event: MouseEvent) => void;
        [key: string]: any;
    };

    const base =
        'inline-flex items-center justify-center gap-2 rounded-sm border-2 text-sm font-medium min-w-fit whitespace-nowrap transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-coollabs dark:focus-visible:ring-warning focus-visible:ring-offset-2 dark:focus-visible:ring-offset-base disabled:pointer-events-none disabled:opacity-50';

    const variants: Record<Variant, string> = {
        default: 'bg-white text-black border-neutral-200 hover:bg-neutral-100 dark:bg-coolgray-100 dark:text-white dark:border-coolgray-300 dark:hover:bg-coolgray-200 disabled:border-transparent dark:disabled:text-neutral-600',
        destructive: 'text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-800 hover:bg-red-300 hover:text-white dark:hover:bg-red-800',
        outline: 'bg-white text-black border-neutral-200 hover:bg-neutral-100 dark:bg-coolgray-100 dark:text-white dark:border-coolgray-300 dark:hover:bg-coolgray-200',
        secondary: 'bg-neutral-100 text-black border-neutral-200 hover:bg-neutral-200 dark:bg-coolgray-200 dark:text-white dark:border-coolgray-300 dark:hover:bg-coolgray-300',
        ghost: 'border-transparent bg-transparent hover:bg-neutral-100 dark:hover:bg-coolgray-200 dark:hover:text-white',
        link: 'border-transparent text-coollabs dark:text-warning underline-offset-4 hover:underline',
        highlighted: 'text-coollabs-200 dark:text-white bg-coollabs-50 dark:bg-coollabs/20 border-coollabs dark:border-coollabs-100 hover:bg-coollabs hover:text-white dark:hover:bg-coollabs-100',
    };

    const sizes: Record<Size, string> = {
        default: 'h-8 px-2',
        sm: 'h-7 px-2 text-xs',
        lg: 'h-10 px-4',
        icon: 'h-8 w-8',
        'icon-sm': 'h-6 w-6',
        'icon-lg': 'h-10 w-10',
    };

    let {
        children,
        asChild = false,
        variant = 'default',
        size = 'default',
        class: className = '',
        type = 'button',
        ...rest
    }: {
        children?: Snippet<[AsChildProps]>;
        asChild?: boolean;
        variant?: Variant;
        size?: Size;
        class?: string;
        type?: 'button' | 'submit' | 'reset';
        [key: string]: unknown;
    } = $props();

    const classes = () => cn(base, variants[variant], sizes[size], className);
</script>

{#if asChild}
    {@render children?.({ class: classes(), ...rest })}
{:else}
    <button class={classes()} type={type} {...rest}>
        {@render children?.({})}
    </button>
{/if}
