<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import type { ButtonVariants } from "."
import { reactiveOmit } from "@vueuse/core"
import { Primitive } from "reka-ui"
import { cn } from "@/lib/utils"
import { buttonVariants } from "."

// Only the handful of native <button> attrs actually used at call sites in
// this codebase are declared below, each with a plain, simple type. Do NOT
// extend `ButtonHTMLAttributes` (or any type built on Vue's
// `EventHandlers<Events>` mapped type) here: confirmed empirically that
// `@vue/compiler-sfc`'s macro resolver cannot statically resolve it when
// extracting runtime props from `defineProps`, either throwing a hard
// "Failed to resolve extends base type" build error, or (when paired with a
// certain type-only-ignore comment directive some suggest for this exact
// failure) silently discarding every declared prop, not just the
// unresolvable one, ending up with an unusably empty runtime props list —
// vue-tsc alone will not catch either failure mode since it uses a
// different, more capable type resolver than the actual Vite/Vitest build.
interface Props extends PrimitiveProps {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  class?: HTMLAttributes["class"]
  type?: "button" | "submit" | "reset"
  disabled?: boolean
  tabindex?: number | string
  "aria-label"?: string
}

const props = withDefaults(defineProps<Props>(), {
  as: "button",
})

const delegatedProps = reactiveOmit(props, "class", "variant", "size")
</script>

<template>
  <Primitive
    data-slot="button"
    v-bind="delegatedProps"
    :class="cn(buttonVariants({ variant, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>
