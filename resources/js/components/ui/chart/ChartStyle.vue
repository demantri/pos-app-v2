<script setup lang="ts">
import { Primitive } from "reka-ui"
import { computed } from "vue"
import { THEMES, useChart } from "."

// Dideklarasikan secara runtime, bukan lewat tipe: dengan bentuk bertipe,
// vue-tsc membaca props komponen ini sebagai kosong sehingga `:id` di
// ChartContainer dianggap prop tak dikenal dan gerbang types:check merah.
const props = defineProps({
  id: { type: String, default: "" },
})

const { config } = useChart()

const colorConfig = computed(() => {
  return Object.entries(config.value).filter(
    ([, config]) => config.theme || config.color,
  )
})
</script>

<template>
  <Primitive
    v-if="colorConfig.length"
    as="style"
  >
    {{ Object.entries(THEMES)
      .map(
        ([theme, prefix]) => `
${prefix} [data-chart=${props.id}] {
${colorConfig
  .map(([key, itemConfig]) => {
    const color
      = itemConfig.theme?.[theme as keyof typeof itemConfig.theme]
      || itemConfig.color
    return color ? `  --color-${key}: ${color};` : null
  })
        .join("\n")}
}
`,
      )
      .join("\n") }}
  </Primitive>
</template>
