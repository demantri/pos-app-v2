<script setup lang="ts">
import { VisAxis, VisCrosshair, VisLine, VisScatter, VisTooltip, VisXYContainer } from '@unovis/vue';
import { ChartContainer } from '@/components/ui/chart';
import type { ChartPoint } from './ChartPoint';

const props = withDefaults(
    defineProps<{
        data: ChartPoint[];
        /** Pemformat nilai untuk sumbu dan tooltip. */
        format: (value: number) => string;
        color?: string;
        height?: number;
    }>(),
    {
        color: 'var(--chart-1)',
        height: 220,
    },
);

const x = (_: ChartPoint, i: number): number => i;
const y = (d: ChartPoint): number => d.value;

/** Tanda sumbu bisa jatuh di posisi pecahan; jangan tampilkan apa pun di situ. */
function categoryLabel(index: number): string {
    return Number.isInteger(index) ? (props.data[index]?.label ?? '') : '';
}

/**
 * Tooltip dibuat sebagai HTML biasa supaya kelas Tailwind-nya ikut terpindai
 * dan warnanya mengikuti tema, sama seperti komponen lain.
 */
function tooltip(_: unknown, index: number): string {
    const point = Number.isInteger(index) ? props.data[index] : undefined;

    if (! point) {
        return '';
    }

    return `<div class="bg-popover text-popover-foreground rounded-lg border px-3 py-2 text-xs shadow-md">
        <div class="text-muted-foreground">${point.label}</div>
        <div class="font-medium">${props.format(point.value)}</div>
    </div>`;
}
</script>

<template>
    <ChartContainer :config="{}" :style="{ height: `${height}px` }" class="w-full">
        <VisXYContainer :data="data" :height="height" :margin="{ top: 8, right: 8, bottom: 4, left: 8 }">
            <!-- Garis 2px: cukup tegas untuk dibaca, tidak menggemuk data. -->
            <VisLine :x="x" :y="y" :color="color" :line-width="2" />
            <!-- Penanda >=8px supaya titiknya bisa disasar kursor maupun jari. -->
            <VisScatter :x="x" :y="y" :color="color" :size="8" />
            <VisAxis
                type="x"
                :grid-line="false"
                :tick-line="false"
                :domain-line="false"
                :tick-format="categoryLabel"
                :num-ticks="Math.min(data.length, 7)"
            />
            <VisAxis
                type="y"
                :tick-line="false"
                :domain-line="false"
                :num-ticks="4"
                :tick-format="format"
            />
            <VisCrosshair :color="color" :template="tooltip" />
            <VisTooltip />
        </VisXYContainer>
    </ChartContainer>
</template>
