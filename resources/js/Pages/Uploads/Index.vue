<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const props = defineProps({
    uploads: Object,
});

const MAX_KB = 5120;
const ACCEPT = ['.csv', 'text/csv', 'application/vnd.ms-excel'];

const form = useForm({ file: null });
const dragOver = ref(false);
const clientError = ref('');
const fileInput = ref(null);

const selectedFile = computed(() => form.file);

function humanSize(bytes) {
    if (!bytes) return '';
    const units = ['B', 'KB', 'MB'];
    let i = 0;
    let n = bytes;
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024;
        i++;
    }
    return `${n.toFixed(n < 10 && i > 0 ? 1 : 0)} ${units[i]}`;
}

function validateFile(file) {
    clientError.value = '';
    if (!file) return false;

    const name = file.name.toLowerCase();
    const isCsv = name.endsWith('.csv') || ACCEPT.includes(file.type);
    if (!isCsv) {
        clientError.value = 'Please select a .csv file.';
        return false;
    }
    if (file.size === 0) {
        clientError.value = 'The file is empty.';
        return false;
    }
    if (file.size > MAX_KB * 1024) {
        clientError.value = `The file is too large (max ${MAX_KB / 1024} MB).`;
        return false;
    }
    return true;
}

function pickFile(file) {
    if (validateFile(file)) {
        form.file = file;
    } else {
        form.file = null;
    }
}

function onDrop(e) {
    dragOver.value = false;
    const file = e.dataTransfer?.files?.[0];
    pickFile(file);
}

function onSelect(e) {
    pickFile(e.target.files?.[0]);
}

function clearFile() {
    form.file = null;
    clientError.value = '';
    if (fileInput.value) fileInput.value.value = '';
}

function submit() {
    if (!form.file) {
        clientError.value = 'Please choose a CSV file to upload.';
        return;
    }
    form.post('/uploads', {
        forceFormData: true,
        onSuccess: () => clearFile(),
    });
}

function statusSeverity(status) {
    return {
        pending: 'secondary',
        processing: 'info',
        completed: 'success',
        completed_with_errors: 'warn',
        failed: 'danger',
    }[status] ?? 'secondary';
}
</script>

<template>
    <Head title="Upload CSV" />
    <AppLayout>
        <div class="mb-8">
            <h1 class="text-2xl font-bold">Import Products</h1>
            <p class="mt-1 text-surface-500 dark:text-surface-400">
                Upload a Shopify-style product CSV. Products are imported to Shopify in the background.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Upload card -->
            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-800 dark:bg-surface-900">
                    <div
                        class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed px-6 py-12 text-center transition-colors"
                        :class="dragOver
                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/40'
                            : 'border-surface-300 dark:border-surface-700'"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="onDrop"
                    >
                        <i class="pi pi-cloud-upload mb-3 text-4xl text-indigo-500" />
                        <p class="font-medium">Drag &amp; drop your CSV here</p>
                        <p class="mb-4 text-sm text-surface-500 dark:text-surface-400">or</p>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv"
                            class="hidden"
                            @change="onSelect"
                        />
                        <Button label="Browse files" icon="pi pi-folder-open" outlined @click="fileInput?.click()" />
                        <p class="mt-4 text-xs text-surface-400">CSV only · max {{ MAX_KB / 1024 }} MB</p>
                    </div>

                    <!-- Selected file -->
                    <div v-if="selectedFile" class="mt-4 flex items-center justify-between rounded-lg bg-surface-100 px-4 py-3 dark:bg-surface-800">
                        <div class="flex items-center gap-3 overflow-hidden">
                            <i class="pi pi-file text-lg text-indigo-500" />
                            <div class="overflow-hidden">
                                <p class="truncate font-medium">{{ selectedFile.name }}</p>
                                <p class="text-xs text-surface-500 dark:text-surface-400">{{ humanSize(selectedFile.size) }}</p>
                            </div>
                        </div>
                        <Button icon="pi pi-times" severity="secondary" text rounded aria-label="Remove file" @click="clearFile" />
                    </div>

                    <Message v-if="clientError" severity="error" class="mt-4" :closable="false">{{ clientError }}</Message>
                    <Message v-if="form.errors.file" severity="error" class="mt-4" :closable="false">{{ form.errors.file }}</Message>

                    <!-- Progress -->
                    <div v-if="form.progress" class="mt-4">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-200 dark:bg-surface-700">
                            <div class="h-full bg-indigo-600 transition-all" :style="{ width: form.progress.percentage + '%' }" />
                        </div>
                        <p class="mt-1 text-xs text-surface-500">Uploading… {{ form.progress.percentage }}%</p>
                    </div>

                    <div class="mt-6 flex items-center gap-3">
                        <Button
                            label="Import products"
                            icon="pi pi-check"
                            :loading="form.processing"
                            :disabled="!selectedFile || form.processing"
                            @click="submit"
                        />
                        <a :href="'/csv-template'" class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                            <i class="pi pi-download mr-1" /> Download CSV template
                        </a>
                    </div>
                </div>
            </div>

            <!-- Format guide -->
            <div class="rounded-2xl border border-surface-200 bg-white p-6 dark:border-surface-800 dark:bg-surface-900">
                <h2 class="mb-3 font-semibold">CSV format</h2>
                <p class="mb-3 text-sm text-surface-500 dark:text-surface-400">Required columns:</p>
                <ul class="space-y-1 text-sm">
                    <li><code class="rounded bg-surface-100 px-1.5 py-0.5 dark:bg-surface-800">Handle</code></li>
                    <li><code class="rounded bg-surface-100 px-1.5 py-0.5 dark:bg-surface-800">Title</code></li>
                    <li><code class="rounded bg-surface-100 px-1.5 py-0.5 dark:bg-surface-800">Variant SKU</code></li>
                    <li><code class="rounded bg-surface-100 px-1.5 py-0.5 dark:bg-surface-800">Variant Price</code></li>
                </ul>
                <p class="mt-4 text-xs text-surface-400">
                    Plus optional columns: Body HTML, Vendor, Product Type, Tags, Published, inventory, weight and image fields.
                </p>
            </div>
        </div>

        <!-- Recent uploads -->
        <div class="mt-10">
            <h2 class="mb-4 text-lg font-semibold">Recent uploads</h2>
            <div v-if="uploads.data.length === 0" class="rounded-2xl border border-dashed border-surface-300 px-6 py-12 text-center text-surface-400 dark:border-surface-700">
                <i class="pi pi-inbox mb-2 text-3xl" />
                <p>No uploads yet. Import your first CSV above.</p>
            </div>
            <div v-else class="overflow-hidden rounded-2xl border border-surface-200 dark:border-surface-800">
                <table class="w-full text-sm">
                    <thead class="bg-surface-100 text-left dark:bg-surface-800">
                        <tr>
                            <th class="px-4 py-3 font-medium">File</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Rows</th>
                            <th class="px-4 py-3 font-medium">Uploaded</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="u in uploads.data"
                            :key="u.id"
                            class="border-t border-surface-200 bg-white hover:bg-surface-50 dark:border-surface-800 dark:bg-surface-900 dark:hover:bg-surface-800/50"
                        >
                            <td class="px-4 py-3">
                                <Link :href="`/uploads/${u.id}`" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ u.original_filename }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <Tag :value="u.status" :severity="statusSeverity(u.status)" />
                            </td>
                            <td class="px-4 py-3">{{ u.total_rows }}</td>
                            <td class="px-4 py-3 text-surface-500 dark:text-surface-400">{{ new Date(u.created_at).toLocaleString() }}</td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="`/uploads/${u.id}`">
                                    <Button label="View" size="small" text />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
