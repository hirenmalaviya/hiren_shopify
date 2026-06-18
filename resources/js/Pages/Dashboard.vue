<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Chart from 'primevue/chart';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import { FilterMatchMode } from '@primevue/core/api';
import { useDarkMode } from '@/composables/useDarkMode';

const props = defineProps({
    stats: Object,
    uploads: Array,
});

const confirm = useConfirm();
const { isDark } = useDarkMode();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const chartData = computed(() => ({
    labels: ['Successful', 'Failed', 'Skipped', 'Pending'],
    datasets: [{
        data: [props.stats.successful, props.stats.failed, props.stats.skipped, props.stats.pending],
        backgroundColor: ['#22c55e', '#ef4444', '#f59e0b', '#94a3b8'],
        borderWidth: 0,
    }],
}));

const chartOptions = computed(() => {
    const text = isDark.value ? '#cbd5e1' : '#475569';
    return {
        plugins: { legend: { position: 'bottom', labels: { color: text, usePointStyle: true } } },
        cutout: '62%',
        responsive: true,
        maintainAspectRatio: false,
    };
});

function fmtDate(value) {
    return value ? new Date(value).toLocaleString() : '—';
}

function confirmDelete(upload) {
    confirm.require({
        header: 'Delete upload',
        message: `Delete "${upload.original_filename}" and all its product records? This cannot be undone.`,
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Delete', severity: 'danger' },
        accept: () => router.delete(`/uploads/${upload.id}`, { preserveScroll: true }),
    });
}

function retry(upload) {
    router.post(`/uploads/${upload.id}/retry`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Dashboard</h1>
                <p class="mt-1 text-surface-500 dark:text-surface-400">Overview of all CSV imports.</p>
            </div>
            <Link href="/uploads">
                <Button label="New import" icon="pi pi-cloud-upload" />
            </Link>
        </div>

        <!-- Stats -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Total uploads" :value="stats.uploads" icon="pi pi-folder" accent="indigo" />
            <StatCard label="Products imported" :value="stats.successful" icon="pi pi-check-circle" accent="green" />
            <StatCard label="Failed" :value="stats.failed" icon="pi pi-times-circle" accent="red" />
            <StatCard label="Success rate" :value="stats.success_rate + '%'" icon="pi pi-chart-line" accent="amber" />
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <!-- Chart -->
            <div class="rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-800 dark:bg-surface-900">
                <h2 class="mb-4 font-semibold">Products by status</h2>
                <div v-if="stats.products > 0" class="h-64">
                    <Chart type="doughnut" :data="chartData" :options="chartOptions" class="h-full" />
                </div>
                <div v-else class="grid h-64 place-items-center text-surface-400">
                    <div class="text-center"><i class="pi pi-chart-pie mb-2 text-3xl" /><p>No data yet</p></div>
                </div>
            </div>

            <!-- Uploads table -->
            <div class="lg:col-span-2 rounded-2xl border border-surface-200 bg-white p-2 dark:border-surface-800 dark:bg-surface-900">
                <DataTable
                    :value="uploads"
                    :filters="filters"
                    :globalFilterFields="['original_filename', 'status']"
                    paginator
                    :rows="8"
                    sortField="created_at"
                    :sortOrder="-1"
                    removableSort
                    dataKey="id"
                    class="text-sm"
                >
                    <template #header>
                        <div class="flex items-center justify-between p-2">
                            <span class="font-semibold">All uploads</span>
                            <IconField>
                                <InputIcon class="pi pi-search" />
                                <InputText v-model="filters.global.value" placeholder="Search…" size="small" />
                            </IconField>
                        </div>
                    </template>
                    <template #empty>
                        <div class="py-10 text-center text-surface-400">
                            <i class="pi pi-inbox mb-2 text-3xl" /><p>No uploads yet.</p>
                        </div>
                    </template>

                    <Column field="original_filename" header="File" sortable>
                        <template #body="{ data }">
                            <Link :href="`/uploads/${data.id}`" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ data.original_filename }}
                            </Link>
                        </template>
                    </Column>
                    <Column field="status" header="Status" sortable>
                        <template #body="{ data }"><StatusBadge :status="data.status" /></template>
                    </Column>
                    <Column header="Progress" style="min-width: 9rem">
                        <template #body="{ data }">
                            <ProgressBar :value="data.progress_percent" :showValue="false" style="height: 0.5rem" />
                            <span class="text-xs text-surface-400">{{ data.successful_rows }}/{{ data.total_rows }}</span>
                        </template>
                    </Column>
                    <Column field="created_at" header="Uploaded" sortable>
                        <template #body="{ data }"><span class="text-surface-500 dark:text-surface-400">{{ fmtDate(data.created_at) }}</span></template>
                    </Column>
                    <Column header="" style="width: 8rem">
                        <template #body="{ data }">
                            <div class="flex justify-end gap-1">
                                <Link :href="`/uploads/${data.id}`"><Button icon="pi pi-eye" text rounded size="small" aria-label="View" /></Link>
                                <Button
                                    v-if="data.failed_rows > 0"
                                    icon="pi pi-refresh" text rounded size="small" severity="warn"
                                    aria-label="Retry failed" @click="retry(data)"
                                />
                                <Button icon="pi pi-trash" text rounded size="small" severity="danger" aria-label="Delete" @click="confirmDelete(data)" />
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </div>
    </AppLayout>
</template>
