import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-sm text-sm font-medium transition-all disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-coollabs dark:focus-visible:ring-warning focus-visible:ring-offset-2 dark:focus-visible:ring-offset-base min-w-fit border-2",
  {
    variants: {
      variant: {
        default:
          "bg-white text-black border-neutral-200 hover:bg-neutral-100 hover:text-black dark:bg-coolgray-100 dark:text-white dark:border-coolgray-300 dark:hover:bg-coolgray-200 dark:hover:text-white disabled:border-transparent disabled:bg-transparent disabled:text-neutral-300 dark:disabled:text-neutral-600",
        destructive:
          "text-red-800 dark:text-red-300 bg-red-50 dark:bg-red-900/30 border-red-300 dark:border-red-800 hover:bg-red-300 hover:text-white dark:hover:bg-red-800 dark:hover:text-white",
        outline:
          "bg-white text-black border-neutral-200 hover:bg-neutral-100 dark:bg-coolgray-100 dark:text-white dark:border-coolgray-300 dark:hover:bg-coolgray-200",
        secondary:
          "bg-neutral-100 text-black border-neutral-200 hover:bg-neutral-200 dark:bg-coolgray-200 dark:text-white dark:border-coolgray-300 dark:hover:bg-coolgray-300",
        ghost:
          "border-transparent bg-transparent hover:bg-neutral-100 hover:text-black dark:hover:bg-coolgray-200 dark:hover:text-white",
        link: "border-transparent text-coollabs dark:text-warning underline-offset-4 hover:underline",
        highlighted:
          "text-coollabs-200 dark:text-white bg-coollabs-50 dark:bg-coollabs/20 border-coollabs dark:border-coollabs-100 hover:bg-coollabs hover:text-white dark:hover:bg-coollabs-100 dark:hover:text-white",
      },
      size: {
        "default": "h-8 px-2",
        "sm": "h-7 px-2 gap-1.5 text-xs",
        "lg": "h-10 px-4",
        "icon": "size-8",
        "icon-sm": "size-7",
        "icon-lg": "size-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
