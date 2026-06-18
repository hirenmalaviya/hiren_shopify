<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import Button from 'primevue/button';
import Popover from 'primevue/popover';
import Badge from 'primevue/badge';
import { useDarkMode } from '@/composables/useDarkMode';

const { isDark, init, toggle } = useDarkMode();
const toast = useToast();
const page = usePage();
const bell = ref(null);

const nav = [
    { label: 'Upload', href: '/uploads', icon: 'pi pi-cloud-upload' },
    { label: 'Dashboard', href: '/dashboard', icon: 'pi pi-chart-bar' },
    { label: 'Logs', href: '/logs', icon: 'pi pi-list' },
];

const notifications = computed(() => page.props.notifications?.items ?? []);
const unread = computed(() => page.props.notifications?.unread ?? 0);

function isActive(href) {
    return page.url === href || page.url.startsWith(href + '/');
}

// Track the last shown messages so polling/partial reloads don't re-toast.
let lastSuccess = null;
let lastError = null;

function flashToasts(flash) {
    if (!flash) return;

    if (flash.success && flash.success !== lastSuccess) {
        toast.add({ severity: 'success', summary: 'Success', detail: flash.success, life: 4000 });
    }
    lastSuccess = flash.success ?? null;

    if (flash.error && flash.error !== lastError) {
        toast.add({ severity: 'error', summary: 'Error', detail: flash.error, life: 6000 });
    }
    lastError = flash.error ?? null;
}

function toggleBell(event) {
    bell.value.toggle(event);
}

function markAllRead() {
    router.post('/notifications/read', {}, { preserveScroll: true, preserveState: false });
}

function fmtDate(value) {
    return value ? new Date(value).toLocaleString() : '';
}

onMounted(() => {
    init();
    flashToasts(page.props.flash);
});

watch(
    () => page.props.flash,
    (flash) => flashToasts(flash),
    { deep: true }
);
</script>

<template>
    <div class="min-h-screen bg-surface-50 text-surface-900 dark:bg-surface-950 dark:text-surface-0 transition-colors">
        <Toast position="top-right" />
        <ConfirmDialog />

        <header class="sticky top-0 z-10 border-b border-surface-200 bg-white/80 backdrop-blur dark:border-surface-800 dark:bg-surface-900/80">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <Link href="/" class="flex items-center gap-2 font-semibold">
                    <span class="grid size-8 place-items-center rounded-lg bg-indigo-600 text-white">
                        <i class="pi pi-shopping-bag text-sm" />
                    </span>
                    <span>{{ page.props.appName || 'Shopify Importer' }}</span>
                </Link>

                <nav class="flex items-center gap-1">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                        :class="isActive(item.href)
                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                            : 'text-surface-600 hover:bg-surface-100 dark:text-surface-300 dark:hover:bg-surface-800'"
                    >
                        <i :class="item.icon" />
                        <span class="hidden sm:inline">{{ item.label }}</span>
                    </Link>

                    <!-- Notification bell -->
                    <button
                        class="relative grid size-9 place-items-center rounded-full text-surface-600 transition-colors hover:bg-surface-100 dark:text-surface-300 dark:hover:bg-surface-800"
                        aria-label="Notifications"
                        @click="toggleBell"
                    >
                        <i class="pi pi-bell" />
                        <Badge v-if="unread > 0" :value="unread" severity="danger" class="!absolute -right-0.5 -top-0.5" />
                    </button>
                    <Popover ref="bell">
                        <div class="w-80">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="font-semibold">Notifications</span>
                                <button v-if="unread > 0" class="text-xs text-indigo-600 hover:underline dark:text-indigo-400" @click="markAllRead">
                                    Mark all read
                                </button>
                            </div>
                            <div v-if="notifications.length === 0" class="py-6 text-center text-sm text-surface-400">
                                No notifications.
                            </div>
                            <ul v-else class="max-h-80 space-y-1 overflow-y-auto">
                                <li
                                    v-for="n in notifications"
                                    :key="n.id"
                                    class="rounded-lg p-2 text-sm"
                                    :class="n.read_at ? 'opacity-60' : 'bg-red-50 dark:bg-red-950/40'"
                                >
                                    <Link :href="n.data.upload_id ? `/uploads/${n.data.upload_id}` : '#'" class="block">
                                        <span class="flex items-center gap-2 font-medium text-red-600 dark:text-red-400">
                                            <i class="pi pi-exclamation-circle" /> Import failed
                                        </span>
                                        <span class="mt-0.5 block text-surface-600 dark:text-surface-300">{{ n.data.message }}</span>
                                        <span class="mt-0.5 block text-xs text-surface-400">{{ fmtDate(n.created_at) }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </Popover>

                    <Button
                        :icon="isDark ? 'pi pi-sun' : 'pi pi-moon'"
                        severity="secondary"
                        text
                        rounded
                        aria-label="Toggle dark mode"
                        @click="toggle"
                    />
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
