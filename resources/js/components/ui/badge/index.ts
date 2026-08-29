import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Badge } from "./Badge.vue"

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
        destructive:
         "border-transparent bg-destructive text-white [a&]:hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        // Nada status: latar lembut + teks pekat, garis tepi senada supaya
        // chip-nya tetap punya batas di atas kartu putih.
        success:
          "border-success-foreground/20 bg-success text-success-foreground [a&]:hover:bg-success/80",
        info:
          "border-info-foreground/20 bg-info text-info-foreground [a&]:hover:bg-info/80",
        warning:
          "border-warning-foreground/20 bg-warning text-warning-foreground [a&]:hover:bg-warning/80",
        danger:
          "border-danger-foreground/20 bg-danger text-danger-foreground [a&]:hover:bg-danger/80",
        neutral:
          "border-neutral-foreground/20 bg-neutral text-neutral-foreground [a&]:hover:bg-neutral/80",
        ink:
          "border-transparent bg-ink text-ink-foreground [a&]:hover:bg-ink/90",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)
export type BadgeVariants = VariantProps<typeof badgeVariants>
