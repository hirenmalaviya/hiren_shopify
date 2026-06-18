<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import { FilterMatchMode } from '@primevue/core/api';

defineProps({
    logs: Array,
});

const expandedRows = ref({});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    level: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const levelOptions = [
    { label: 'All levels', value: null },
    { label: 'Info', value: 'info' },
    { label: 'Warning', value: 'warning' },
    { label: 'Error', value: 'error' },
];

const SEVERITY = { info: 'info', warning: 'warn', error: 'danger' };

function fmtDate(value) {
    return value ? new Date(value).toLocaleString() : '';
}
</script>

<template>
    <Head title="Logs" />
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Import logs</h1>
            <p class="mt-1 text-surface-500 dark:text-surface-400">Most recent 300 import events.</p>
        </div>

        <div class="rounded-2xl border border-surface-200 bg-white p-2 dark:border-surface-800 dark:bg-surface-900">
            <DataTable
                v-model:expandedRows="expandedRows"
                :value="logs"
                :filters="filters"
                :globalFilterFields="['message', 'sku', 'upload']"
                paginator
                :rows="20"
                :rowsPerPageOptions="[20, 50, 100]"
                dataKey="id"
                class="text-sm"
            >
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-3 p-2">
                        <Select
                            v-model="filters.level.value"
                            :options="levelOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Level"
                            size="small"
                            class="w-44"
                        />
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters.global.value" placeholder="Search messages…" size="small" />
                        </IconField>
                    </div>
                </template>
                <template #empty><div class="py-10 text-center text-surface-400">No log entries yet.</div></template>

                <Column expander style="width: 3rem" />
                <Column field="level" header="Level" sortable :showFilterMenu="false" style="width: 7rem">
                    <template #body="{ data }">
                        <Tag :value="data.level" :severity="SEVERITY[data.level] ?? 'secondary'" />
                    </template>
                </Column>
                <Column field="message" header="Message" sortable />
                <Column field="sku" header="SKU" style="width: 8rem">
                    <template #body="{ data }">{{ data.sku || '—' }}</template>
                </Column>
                <Column field="upload" header="Upload" style="width: 12rem">
                    <template #body="{ data }">
                        <Link v-if="data.upload_id" :href="`/uploads/${data.upload_id}`" class="text-indigo-600 hover:underline dark:text-indigo-400">
                            {{ data.upload || `#${data.upload_id}` }}
                        </Link>
                        <span v-else class="text-surface-300">—</span>
                    </template>
                </Column>
                <Column field="created_at" header="Time" sortable style="width: 12rem">
                    <template #body="{ data }"><span class="text-surface-500 dark:text-surface-400">{{ fmtDate(data.created_at) }}</span></template>
                </Column>

                <template #expansion="{ data }">
                    <pre class="overflow-x-auto rounded-lg bg-surface-100 p-3 text-xs dark:bg-surface-800">{{ JSON.stringify(data.context, null, 2) }}</pre>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
