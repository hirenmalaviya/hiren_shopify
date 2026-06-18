<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import InputText from 'primevue/inputtext';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Select from 'primevue/select';
import { FilterMatchMode } from '@primevue/core/api';

const props = defineProps({
    upload: Object,
    products: Array,
    storeHandle: String,
});

const confirm = useConfirm();
const TERMINAL = ['completed', 'completed_with_errors', 'failed'];
const isProcessing = computed(() => !TERMINAL.includes(props.upload.status));
const autoRefresh = ref(true);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const statusOptions = [
    { label: 'All', value: null },
    { label: 'Successful', value: 'successful' },
    { label: 'Failed', value: 'failed' },
    { label: 'Processing', value: 'processing' },
    { label: 'Pending', value: 'pending' },
    { label: 'Skipped', value: 'skipped' },
];

let timer = null;

function poll() {
    if (!autoRefresh.value || !isProcessing.value) return;
    // Include 'notifications' so the bell updates live when an import finishes with errors.
    router.reload({ only: ['upload', 'products', 'notifications'] });
}

onMounted(() => {
    timer = setInterval(poll, 3000);
});
onUnmounted(() => clearInterval(timer));

function shopifyAdminUrl(gid) {
    if (!gid || !props.storeHandle) return null;
    const id = String(gid).split('/').pop();
    return `https://admin.shopify.com/store/${props.storeHandle}/products/${id}`;
}

function confirmDelete() {
    confirm.require({
        header: 'Delete upload',
        message: 'Delete this upload and all its product records? This cannot be undone.',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: { label: 'Cancel', severity: 'secondary', outlined: true },
        acceptProps: { label: 'Delete', severity: 'danger' },
        accept: () => router.delete(`/uploads/${props.upload.id}`),
    });
}

function retry() {
    router.post(`/uploads/${props.upload.id}/retry`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="upload.original_filename" />
    <AppLayout>
        <Link href="/dashboard" class="mb-4 inline-flex items-center gap-1 text-sm text-surface-500 hover:text-indigo-600 dark:text-surface-400">
            <i class="pi pi-arrow-left" /> Back to dashboard
        </Link>

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold">{{ upload.original_filename }}</h1>
                <StatusBadge :status="upload.status" />
            </div>
            <div class="flex items-center gap-2">
                <span v-if="isProcessing" class="flex items-center gap-2 text-sm text-surface-500">
                    <i class="pi pi-spin pi-spinner" /> Processing…
                </span>
                <Button
                    v-if="upload.failed_rows > 0"
                    label="Retry failed" icon="pi pi-refresh" severity="warn" outlined size="small" @click="retry"
                />
                <Button label="Delete" icon="pi pi-trash" severity="danger" outlined size="small" @click="confirmDelete" />
            </div>
        </div>

        <!-- Progress + summary -->
        <div class="mb-6 rounded-2xl border border-surface-200 bg-white p-5 dark:border-surface-800 dark:bg-surface-900">
            <div class="mb-2 flex items-center justify-between text-sm">
                <span class="font-medium">{{ upload.processed_rows }} / {{ upload.total_rows }} processed</span>
                <span class="text-surface-400">{{ upload.progress_percent }}%</span>
            </div>
            <ProgressBar :value="upload.progress_percent" :showValue="false" style="height: 0.6rem" />
            <div class="mt-4 flex flex-wrap gap-4 text-sm">
                <span class="text-green-600 dark:text-green-400"><i class="pi pi-check-circle" /> {{ upload.successful_rows }} successful</span>
                <span class="text-red-600 dark:text-red-400"><i class="pi pi-times-circle" /> {{ upload.failed_rows }} failed</span>
                <span class="text-amber-600 dark:text-amber-400"><i class="pi pi-exclamation-triangle" /> {{ upload.skipped_rows }} skipped</span>
            </div>
        </div>

        <!-- Products table -->
        <div class="rounded-2xl border border-surface-200 bg-white p-2 dark:border-surface-800 dark:bg-surface-900">
            <DataTable
                :value="products"
                :filters="filters"
                :globalFilterFields="['title', 'sku', 'handle']"
                paginator
                :rows="15"
                :rowsPerPageOptions="[15, 30, 50]"
                dataKey="id"
                class="text-sm"
            >
                <template #header>
                    <div class="flex flex-wrap items-center justify-between gap-3 p-2">
                        <div class="flex items-center gap-2">
                            <Select
                                v-model="filters.status.value"
                                :options="statusOptions"
                                optionLabel="label"
                                optionValue="value"
                                placeholder="Status"
                                size="small"
                                class="w-40"
                            />
                            <label class="flex items-center gap-1 text-xs text-surface-500">
                                <input type="checkbox" v-model="autoRefresh" /> Auto-refresh
                            </label>
                        </div>
                        <IconField>
                            <InputIcon class="pi pi-search" />
                            <InputText v-model="filters.global.value" placeholder="Search…" size="small" />
                        </IconField>
                    </div>
                </template>
                <template #empty><div class="py-10 text-center text-surface-400">No products.</div></template>

                <Column field="row_number" header="#" sortable style="width: 4rem" />
                <Column field="title" header="Title" sortable>
                    <template #body="{ data }">{{ data.title || '—' }}</template>
                </Column>
                <Column field="sku" header="SKU" sortable />
                <Column field="price" header="Price" sortable style="width: 6rem">
                    <template #body="{ data }">{{ data.price ?? '—' }}</template>
                </Column>
                <Column field="status" header="Status" sortable :showFilterMenu="false">
                    <template #body="{ data }"><StatusBadge :status="data.status" /></template>
                </Column>
                <Column field="action" header="Action" style="width: 6rem">
                    <template #body="{ data }">
                        <Tag v-if="data.action" :value="data.action" :severity="data.action === 'create' ? 'info' : 'secondary'" />
                        <span v-else class="text-surface-300">—</span>
                    </template>
                </Column>
                <Column header="Detail">
                    <template #body="{ data }">
                        <span v-if="data.error_message" class="text-red-600 dark:text-red-400">{{ data.error_message }}</span>
                        <a
                            v-else-if="data.shopify_product_id"
                            :href="shopifyAdminUrl(data.shopify_product_id)"
                            target="_blank"
                            class="text-indigo-600 hover:underline dark:text-indigo-400"
                        >
                            View in Shopify <i class="pi pi-external-link text-xs" />
                        </a>
                        <span v-else class="text-surface-300">—</span>
                    </template>
                </Column>
            </DataTable>
        </div>
    </AppLayout>
</template>
