<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-white dark:bg-coolgray-100 text-card-foreground flex flex-col gap-6 rounded-sm border border-neutral-200 dark:border-coolgray-300 py-6 shadow-sm',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
