<script setup lang="ts">
const model = defineModel<boolean>({ required: true });

defineProps<{
    id?: string;
}>();

const emit = defineEmits<{
    change: [value: boolean];
}>();

function onChanged(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    model.value = checked;
    emit('change', checked);
}
</script>

<template>
    <input
        :id="id"
        type="checkbox"
        :checked="model"
        @change="onChanged"
        class="size-4 shrink-0 appearance-none rounded-[4px] border border-input shadow-xs checked:bg-primary checked:border-primary relative checked:after:content-['✓'] checked:after:absolute checked:after:inset-0 checked:after:flex checked:after:items-center checked:after:justify-center checked:after:text-primary-foreground checked:after:text-xs checked:after:font-bold"
    />
</template>
