<script setup lang="ts">
const model = defineModel<boolean>({ required: true });

defineProps<{
    id?: string;
}>();
</script>

<template>
    <input
        :id="id"
        type="checkbox"
        v-model="model"
        class="size-4 shrink-0 appearance-none rounded-[4px] border border-input shadow-xs checked:bg-primary checked:border-primary relative checked:after:content-['✓'] checked:after:absolute checked:after:inset-0 checked:after:flex checked:after:items-center checked:after:justify-center checked:after:text-primary-foreground checked:after:text-xs checked:after:font-bold"
    />
</template>
