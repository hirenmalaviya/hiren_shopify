<script setup>
defineProps({
    label: String,
    value: [String, Number],
    icon: String,
    accent: { type: String, default: 'indigo' },
});

const ACCENTS = {
    indigo: 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300',
    green: 'bg-green-50 text-green-600 dark:bg-green-950 dark:text-green-300',
    red: 'bg-red-50 text-red-600 dark:bg-red-950 dark:text-red-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-300',
};
</script>

<template>
    <div class="rounded-2xl border border-surface-200 bg-white p-5 dark:border-surface-800 dark:bg-surface-900">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-surface-500 dark:text-surface-400">{{ label }}</span>
            <span class="grid size-9 place-items-center rounded-lg" :class="ACCENTS[accent]">
                <i :class="icon" />
            </span>
        </div>
        <p class="mt-3 text-3xl font-bold tracking-tight">{{ value }}</p>
    </div>
</template>
