<script setup>
import { computed } from 'vue';
import Tag from 'primevue/tag';

const props = defineProps({
    status: { type: String, required: true },
});

const SEVERITY = {
    // upload statuses
    pending: 'secondary',
    processing: 'info',
    completed: 'success',
    completed_with_errors: 'warn',
    failed: 'danger',
    // product statuses
    successful: 'success',
    skipped: 'warn',
};

const LABEL = {
    completed_with_errors: 'Completed with errors',
};

const severity = computed(() => SEVERITY[props.status] ?? 'secondary');
const label = computed(() => LABEL[props.status] ?? props.status.charAt(0).toUpperCase() + props.status.slice(1));
</script>

<template>
    <Tag :value="label" :severity="severity" />
</template>
