<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'coolify-input block w-full min-w-0 rounded-sm border-0 py-1.5 px-2 text-sm text-black bg-white dark:bg-coolgray-100 dark:text-white placeholder:text-neutral-300 dark:placeholder:text-neutral-700 disabled:bg-neutral-200 disabled:text-neutral-500 dark:disabled:bg-coolgray-100/40 read-only:text-neutral-500 read-only:bg-neutral-200 dark:read-only:text-neutral-500 dark:read-only:bg-coolgray-100/40 focus-visible:outline-none transition-shadow',
      props.class,
    )"
  >
</template>
