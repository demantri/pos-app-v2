<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { computed, ref } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  modelModifiers?: { number?: boolean, trim?: boolean }
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const rawModelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

// Native `v-model` modifiers (`.number`, `.trim`) only work out of the box on
// plain elements. Since this component owns the `v-model` binding on the
// inner `<input>`, we have to re-implement them ourselves, mirroring Vue's
// own `.number`/`.trim` semantics (see `@vue/shared`'s `toNumber`), or
// callers using `v-model.number`/`v-model.trim` on `<Input>` would silently
// keep receiving raw strings instead of the converted value.
const modelValue = computed({
  get: () => rawModelValue.value,
  set: (value) => {
    if (typeof value === "string") {
      if (props.modelModifiers?.trim) {
        value = value.trim()
      }
      if (props.modelModifiers?.number) {
        const parsed = Number(value)
        value = Number.isNaN(parsed) ? value : parsed
      }
    }
    rawModelValue.value = value
  },
})

const inputRef = ref<HTMLInputElement | null>(null)

defineExpose({
  focus: () => inputRef.value?.focus(),
})
</script>

<template>
  <input
    ref="inputRef"
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
      'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
      'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
      props.class,
    )"
  >
</template>
