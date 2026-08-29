<script setup lang="ts">
import { StackedBar } from '@unovis/ts';
import { VisAxis, VisStackedBar, VisTooltip, VisXYContainer } from '@unovis/vue';
import { computed } from 'vue';
import { ChartContainer } from '@/components/ui/chart';
import type { ChartPoint } from './ChartPoint';

const props = withDefaults(
    defineProps<{
        data: ChartPoint[];
        format: (value: number) => string;
        color?: string;
        height?: number;
        /** Batang mendatar, untuk kategori berlabel panjang seperti nama produk. */
        horizontal?: boolean;
    }>(),
    {
        color: 'var(--chart-2)',
        height: 220,
        horizontal: false,
    },
);

const x = (_: ChartPoint, i: number): number => i;
const y = (d: ChartPoint): number => d.value;

/**
 * Batang mendatar dipakai justru karena labelnya panjang, jadi sisi kirinya
 * diberi ruang tetap dan nama yang kelewat panjang dipangkas — kalau tidak,
 * labelnya membungkus dan saling menabrak.
 */
const LABEL_WIDTH = 150;
const LABEL_MAX = 20;

const margin = computed(() =>
    props.horizontal
        ? { top: 4, right: 24, bottom: 4, left: LABEL_WIDTH }
        : { top: 8, right: 8, bottom: 4, left: 8 },
);

function categoryLabel(index: number): string {
    const label = props.data[index]?.label ?? '';

    if (! props.horizontal || label.length <= LABEL_MAX) {
        return label;
    }

    return `${label.slice(0, LABEL_MAX - 1)}…`;
}

/**
 * Sumbu kategori pada batang tegak hanya menampilkan sebagian tanda: 14 label
 * tanggal dalam satu kartu pasti saling menumpuk. Batang mendatar sebaliknya —
 * tiap batang memang harus bernama.
 */
const categoryTicks = computed(() =>
    props.horizontal ? props.data.length : Math.min(props.data.length, 7),
);

function tooltip(d: ChartPoint): string {
    return `<div class="bg-popover text-popover-foreground rounded-lg border px-3 py-2 text-xs shadow-md">
        <div class="text-muted-foreground">${d.label}</div>
        <div class="font-medium">${props.format(d.value)}</div>
    </div>`;
}
</script>

<template>
    <ChartContainer :config="{}" :style="{ height: `${height}px` }" class="w-full">
        <VisXYContainer
            :data="data"
            :height="height"
            :margin="margin"
        >
            <!--
                barPadding menyisakan celah antar batang, roundedCorners
                membulatkan ujung datanya — keduanya membuat batang terbaca
                sebagai nilai, bukan sebagai blok penuh.
            -->
            <VisStackedBar
                :x="x"
                :y="y"
                :color="color"
                :bar-padding="0.25"
                :rounded-corners="4"
                :orientation="horizontal ? 'horizontal' : 'vertical'"
            />
            <VisAxis
                :type="horizontal ? 'y' : 'x'"
                :grid-line="false"
                :tick-line="false"
                :domain-line="false"
                :tick-format="categoryLabel"
                :num-ticks="categoryTicks"
            />
            <VisAxis
                :type="horizontal ? 'x' : 'y'"
                :tick-line="false"
                :domain-line="false"
                :num-ticks="4"
                :tick-format="format"
            />
            <!-- Selektor diambil dari kelas @unovis/ts; pembungkus Vue-nya
                 tidak mengeksposnya. -->
            <VisTooltip :triggers="{ [StackedBar.selectors.bar]: tooltip }" />
        </VisXYContainer>
    </ChartContainer>
</template>
