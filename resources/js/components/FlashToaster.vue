<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import { toast } from 'vue-sonner';

type Flash = { success: string | null; error: string | null };

const page = usePage();

watch(
    () => page.props.flash as Flash | undefined,
    (flash) => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <span class="hidden" aria-hidden="true" />
</template>
