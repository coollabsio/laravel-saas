<script lang="ts">
    interface Props {
        id?: string;
        checked: boolean;
        onchange?: (value: boolean) => void;
    }

    let { id, checked = $bindable(), onchange }: Props = $props();

    function handleChange(event: Event) {
        const value = (event.target as HTMLInputElement).checked;
        checked = value;
        onchange?.(value);
    }
</script>

<input
    {id}
    type="checkbox"
    {checked}
    onchange={handleChange}
    class="size-4 shrink-0 appearance-none rounded-[4px] border border-input shadow-xs checked:bg-primary checked:border-primary relative checked:after:content-['✓'] checked:after:absolute checked:after:inset-0 checked:after:flex checked:after:items-center checked:after:justify-center checked:after:text-primary-foreground checked:after:text-xs checked:after:font-bold"
/>
