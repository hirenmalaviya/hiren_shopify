<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';

defineProps({
    appName: String,
    laravelVersion: String,
    phpVersion: String,
});

const isDark = ref(false);

function applyTheme() {
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}

function toggleTheme() {
    isDark.value = !isDark.value;
    applyTheme();
}

onMounted(() => {
    isDark.value = localStorage.getItem('theme') === 'dark';
    applyTheme();
});
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-full bg-surface-50 dark:bg-surface-950 text-surface-900 dark:text-surface-0 transition-colors">
        <div class="mx-auto max-w-3xl px-6 py-16">
            <div class="mb-8 flex items-center justify-between">
                <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 dark:bg-indigo-950 px-3 py-1 text-sm font-medium text-indigo-700 dark:text-indigo-300">
                    <i class="pi pi-check-circle" /> Setup OK
                </span>
                <Button
                    :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
                    :label="isDark ? 'Light' : 'Dark'"
                    severity="secondary"
                    outlined
                    size="small"
                    @click="toggleTheme"
                />
            </div>

            <Card>
                <template #title>{{ appName }}</template>
                <template #subtitle>CSV → Shopify Product Import System</template>
                <template #content>
                    <p class="mb-4 text-surface-600 dark:text-surface-300">
                        The Laravel + Inertia + Vue 3 + PrimeVue + Tailwind stack is wired up and rendering.
                    </p>
                    <ul class="space-y-1 text-sm text-surface-500 dark:text-surface-400">
                        <li><strong>Laravel:</strong> {{ laravelVersion }}</li>
                        <li><strong>PHP:</strong> {{ phpVersion }}</li>
                    </ul>
                </template>
            </Card>
        </div>
    </div>
</template>
