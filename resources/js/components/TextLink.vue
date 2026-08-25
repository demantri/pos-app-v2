<script setup lang="ts">
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

type Props = {
    href: LinkComponentBaseProps['href'];
    tabindex?: number;
    method?: Method;
    as?: string;
};

defineProps<Props>();
</script>

<template>
    <!--
      Inertia's Link component doesn't declare `tabindex` in its own prop
      type, even though it genuinely falls through to the rendered <a>/
      <button> at runtime (Link doesn't set inheritAttrs: false). Binding it
      via a whole-object v-bind (always compiled as a spread) sidesteps
      TypeScript's excess-property check for that one gap without losing
      type-checking on href/method/as at their own declaration site above.
    -->
    <Link
        v-bind="{ href, tabindex, method, as }"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
    >
        <slot />
    </Link>
</template>
