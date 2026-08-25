<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { ButtonHTMLAttributes, HTMLAttributes } from "vue"
import type { ButtonVariants } from "."
import { reactiveOmit } from "@vueuse/core"
import { Primitive } from "reka-ui"
import { cn } from "@/lib/utils"
import { buttonVariants } from "."

interface Props extends PrimitiveProps, Omit<ButtonHTMLAttributes, "class"> {
  variant?: ButtonVariants["variant"]
  size?: ButtonVariants["size"]
  class?: HTMLAttributes["class"]
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
