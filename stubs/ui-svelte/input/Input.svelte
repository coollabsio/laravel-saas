<script lang="ts">
    import { cn } from '@/lib/utils';

    let { class: className = '', type = 'text', ...rest } = $props();
</script>

<input
    type={type}
    class={cn(
        'coolify-input block w-full min-w-0 rounded-sm border-0 py-1.5 px-2 text-sm text-black bg-white dark:bg-coolgray-100 dark:text-white placeholder:text-neutral-300 dark:placeholder:text-neutral-700 disabled:bg-neutral-200 disabled:text-neutral-500 dark:disabled:bg-coolgray-100/40 read-only:text-neutral-500 read-only:bg-neutral-200 dark:read-only:text-neutral-500 dark:read-only:bg-coolgray-100/40 focus-visible:outline-none transition-shadow',
        className,
    )}
    {...rest}
/>
