<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { computed, ref } from "vue"
import { useVModel } from "@vueuse/core"
import { Eye, EyeOff } from "lucide-vue-next"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  type?: string
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const isPassword = computed(() => props.type === "password")
const showPassword = ref(false)
const inputType = computed(() =>
  isPassword.value && showPassword.value ? "text" : (props.type ?? "text"),
)
</script>

<template>
  <div class="relative w-full">
    <input
      v-model="modelValue"
      :type="inputType"
      data-slot="input"
      :class="cn(
        'coolify-input block w-full min-w-0 rounded border-0 py-1.5 px-2 text-sm text-black bg-white dark:bg-coolgray-100 dark:text-white placeholder:text-neutral-300 dark:placeholder:text-neutral-700 disabled:bg-neutral-200 disabled:text-neutral-500 dark:disabled:bg-coolgray-100/40 read-only:text-neutral-500 read-only:bg-neutral-200 dark:read-only:text-neutral-500 dark:read-only:bg-coolgray-100/40 focus-visible:outline-none transition-shadow',
        isPassword && 'pr-8',
        props.class,
      )"
    >
    <button
      v-if="isPassword"
      type="button"
      tabindex="-1"
      class="absolute top-1/2 -translate-y-1/2 right-0 flex items-center pr-2 text-neutral-400 hover:text-neutral-600 dark:text-neutral-500 dark:hover:text-neutral-300"
      @click="showPassword = !showPassword"
    >
      <EyeOff v-if="showPassword" :size="16" />
      <Eye v-else :size="16" />
    </button>
  </div>
</template>
