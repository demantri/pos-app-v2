<script setup lang="ts">
import type { ListboxGroupProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { ListboxGroup, ListboxGroupLabel, useId } from "reka-ui"
import { computed, onMounted, onUnmounted } from "vue"
import { cn } from "@/lib/utils"
import { provideCommandGroupContext, useCommand } from "."

const props = defineProps<ListboxGroupProps & {
  class?: HTMLAttributes["class"]
  heading?: string
}>()

const delegatedProps = reactiveOmit(props, "class")

const { allGroups, filterState } = useCommand()
const id = useId()

const isRender = computed(() => !filterState.search ? true : filterState.filtered.groups.has(id))

provideCommandGroupContext({ id })
onMounted(() => {
  if (!allGroups.value.has(id))
    allGroups.value.set(id, new Set())
})
onUnmounted(() => {
  allGroups.value.delete(id)
})
</script>

<template>
  <!--
    reka-ui's ListboxGroup doesn't declare `id`/`hidden` in ListboxGroupProps,
    even though they do reach the underlying element at runtime. Merging them
    into the same v-bind object as the spread `delegatedProps` (rather than
    `:id=`/`:hidden=` directives) keeps this from tripping strictTemplates'
    excess-property check on that upstream gap.
  -->
  <ListboxGroup
    v-bind="{ ...delegatedProps, id, hidden: isRender ? undefined : true }"
    data-slot="command-group"
    :class="cn('text-foreground overflow-hidden p-1', props.class)"
  >
    <ListboxGroupLabel v-if="heading" data-slot="command-group-heading" class="px-2 py-1.5 text-xs font-medium text-muted-foreground">
      {{ heading }}
    </ListboxGroupLabel>
    <slot />
  </ListboxGroup>
</template>
