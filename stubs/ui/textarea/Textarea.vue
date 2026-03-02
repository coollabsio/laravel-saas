<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = defineProps<{
    defaultValue?: string;
    modelValue?: string;
    class?: HTMLAttributes['class'];
}>();

const emits = defineEmits<{
    (e: 'update:modelValue', payload: string): void;
}>();

const modelValue = useVModel(props, 'modelValue', emits, {
    passive: true,
    defaultValue: props.defaultValue,
});
</script>

<template>
    <textarea
        v-model="modelValue"
        data-slot="textarea"
        :class="
            cn(
                'block w-full min-h-[80px] min-w-0 rounded border border-neutral-200 dark:border-coolgray-300 bg-white dark:bg-coolgray-100 text-black dark:text-white px-3 py-2 text-sm placeholder:text-neutral-300 dark:placeholder:text-neutral-700 outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 transition-shadow focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-coollabs dark:focus-visible:ring-warning focus-visible:ring-offset-2 dark:focus-visible:ring-offset-base',
                props.class
            )
        "
    />
</template>
