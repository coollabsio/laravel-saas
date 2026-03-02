<script lang="ts">
    import { cn } from '@/lib/utils';
    import Eye from 'lucide-svelte/icons/eye';
    import EyeOff from 'lucide-svelte/icons/eye-off';

    let { class: className = '', type = 'text', ...rest } = $props();

    let isPassword = $derived(type === 'password');
    let showPassword = $state(false);
    let inputType = $derived(isPassword && showPassword ? 'text' : type);
</script>

<div class="relative w-full">
    <input
        type={inputType}
        class={cn(
            'coolify-input block w-full min-w-0 rounded border-0 py-1.5 px-2 text-sm text-black bg-white dark:bg-coolgray-100 dark:text-white placeholder:text-neutral-300 dark:placeholder:text-neutral-700 disabled:bg-neutral-200 disabled:text-neutral-500 dark:disabled:bg-coolgray-100/40 read-only:text-neutral-500 read-only:bg-neutral-200 dark:read-only:text-neutral-500 dark:read-only:bg-coolgray-100/40 focus-visible:outline-none transition-shadow',
            isPassword && 'pr-8',
            className,
        )}
        {...rest}
    />
    {#if isPassword}
        <button
            type="button"
            tabindex={-1}
            class="absolute inset-y-0 right-0 flex items-center pr-2 text-neutral-400 hover:text-neutral-600 dark:text-neutral-500 dark:hover:text-neutral-300"
            onclick={() => (showPassword = !showPassword)}
        >
            {#if showPassword}
                <EyeOff size={16} />
            {:else}
                <Eye size={16} />
            {/if}
        </button>
    {/if}
</div>
