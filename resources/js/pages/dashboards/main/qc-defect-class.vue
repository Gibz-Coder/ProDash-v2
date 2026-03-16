<template>
    <AppLayout>
        <template #header>
            <h2 class="flex items-center text-xl font-semibold">
                <ClipboardList class="mr-2 h-5 w-5" />
                QC Defect Class
            </h2>
        </template>

        <div class="container mx-auto px-4 py-6">
            <!-- Header -->
            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold">QC Defect Class</h1>
                    <p class="text-muted-foreground">
                        Manage defect codes, names, and flow classifications
                    </p>
                </div>
                <button
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-primary/90 hover:to-primary hover:shadow-xl hover:shadow-primary/25"
                    @click="openCreateModal"
                >
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Add Defect
                </button>
            </div>

            <!-- Stats -->
            <div class="mb-6 grid gap-6 md:grid-cols-3">
                <div
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 p-6 shadow-lg dark:from-blue-950/30 dark:to-blue-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-blue-700 dark:text-blue-300"
                            >
                                Total Defects
                            </p>
                            <p
                                class="text-3xl font-bold text-blue-900 dark:text-blue-100"
                            >
                                {{ defects.length }}
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-blue-500/10 p-4 ring-1 ring-blue-500/20"
                        >
                            <ClipboardList
                                class="h-7 w-7 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-6 shadow-lg dark:from-emerald-950/30 dark:to-emerald-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-emerald-700 dark:text-emerald-300"
                            >
                                QC Analysis
                            </p>
                            <p
                                class="text-3xl font-bold text-emerald-900 dark:text-emerald-100"
                            >
                                {{ qcAnalysisCount }}
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-emerald-500/10 p-4 ring-1 ring-emerald-500/20"
                        >
                            <CheckCircle
                                class="h-7 w-7 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-amber-50 to-amber-100/50 p-6 shadow-lg dark:from-amber-950/30 dark:to-amber-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-amber-700 dark:text-amber-300"
                            >
                                Tech'l Verification
                            </p>
                            <p
                                class="text-3xl font-bold text-amber-900 dark:text-amber-100"
                            >
                                {{ techVerifCount }}
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-amber-500/10 p-4 ring-1 ring-amber-500/20"
                        >
                            <Wrench
                                class="h-7 w-7 text-amber-600 dark:text-amber-400"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search & Filter -->
            <div
                class="mb-6 rounded-xl border border-border/50 bg-card p-6 shadow-lg"
            >
                <div class="grid gap-4 md:grid-cols-12">
                    <div class="md:col-span-7">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                            placeholder="Search by defect code or name..."
                            @input="fetchDefects"
                        />
                    </div>
                    <div class="md:col-span-3">
                        <select
                            v-model="flowFilter"
                            class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                            @change="fetchDefects"
                        >
                            <option value="">All Flows</option>
                            <option value="QC Analysis">QC Analysis</option>
                            <option value="Tech'l Verfication">
                                Tech'l Verification
                            </option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <button
                            class="inline-flex h-12 w-full items-center justify-center rounded-lg border border-border bg-background px-4 text-sm font-semibold shadow-sm transition-all hover:bg-muted"
                            @click="clearFilters"
                        >
                            <XCircle class="mr-2 h-4 w-4" />Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border border-border/50 bg-card shadow-lg">
                <div class="p-6">
                    <div v-if="loading" class="flex justify-center py-12">
                        <div
                            class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-r-transparent"
                            role="status"
                        >
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <div v-else-if="defects.length > 0" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10"
                            >
                                <tr>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Code
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Defect Name
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Class
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Flow
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Remarks
                                    </th>
                                    <th
                                        class="px-4 py-4 text-center font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/30">
                                <tr
                                    v-for="defect in defects"
                                    :key="defect.id"
                                    class="transition-colors hover:bg-muted/20"
                                >
                                    <td
                                        class="px-4 py-3 font-mono font-semibold text-primary"
                                    >
                                        {{ defect.defect_code }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ defect.defect_name }}
                                    </td>
                                    <td class="px-4 py-3 text-muted-foreground">
                                        {{ defect.defect_class || '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                            :class="
                                                defect.defect_flow ===
                                                'QC Analysis'
                                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                    : 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400'
                                            "
                                        >
                                            {{ defect.defect_flow }}
                                        </span>
                                    </td>
                                    <td
                                        class="max-w-xs truncate px-4 py-3 text-muted-foreground"
                                    >
                                        {{ defect.remarks || '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <button
                                                class="flex items-center gap-1.5 rounded-lg border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary transition-all hover:bg-primary/10"
                                                @click="editDefect(defect)"
                                            >
                                                <Pencil
                                                    class="h-3.5 w-3.5"
                                                />Edit
                                            </button>
                                            <button
                                                class="flex items-center gap-1.5 rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-1.5 text-xs font-semibold text-destructive transition-all hover:bg-destructive/10"
                                                @click="confirmDelete(defect)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="py-12 text-center text-muted-foreground">
                        <ClipboardList class="mx-auto h-16 w-16 opacity-30" />
                        <p class="mt-3">No defects found</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click="closeModal"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-lg animate-in flex-col rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 duration-300 fade-in-0 zoom-in-95"
                @click.stop
            >
                <div
                    class="flex items-center justify-between rounded-t-2xl border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10 px-6 py-5"
                >
                    <div>
                        <h3 class="text-xl font-bold text-foreground">
                            {{ isEditMode ? 'Edit Defect' : 'Add Defect' }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                isEditMode
                                    ? 'Update defect details'
                                    : 'Enter new defect information'
                            }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="closeModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form
                    class="flex flex-1 flex-col overflow-hidden"
                    @submit.prevent="saveDefect"
                >
                    <div class="space-y-5 overflow-y-auto p-6">
                        <!-- Defect Code -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-foreground"
                                >Defect Code</label
                            >
                            <input
                                v-model="form.defect_code"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none disabled:opacity-50"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive':
                                        errors.defect_code,
                                }"
                                placeholder="e.g., AC"
                                :disabled="isEditMode"
                                required
                            />
                            <p
                                v-if="errors.defect_code"
                                class="mt-1.5 text-sm font-medium text-destructive"
                            >
                                {{ errors.defect_code }}
                            </p>
                        </div>

                        <!-- Defect Name -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-foreground"
                                >Defect Name</label
                            >
                            <input
                                v-model="form.defect_name"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive':
                                        errors.defect_name,
                                }"
                                placeholder="e.g., Active Cover Crack"
                                required
                            />
                            <p
                                v-if="errors.defect_name"
                                class="mt-1.5 text-sm font-medium text-destructive"
                            >
                                {{ errors.defect_name }}
                            </p>
                        </div>

                        <!-- Defect Class -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-foreground"
                                >Defect Class
                                <span class="font-normal text-muted-foreground"
                                    >(optional)</span
                                ></label
                            >
                            <input
                                v-model="form.defect_class"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                                placeholder="e.g., Critical"
                            />
                        </div>

                        <!-- Defect Flow -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-foreground"
                                >Defect Flow</label
                            >
                            <select
                                v-model="form.defect_flow"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive':
                                        errors.defect_flow,
                                }"
                                required
                            >
                                <option value="" disabled>
                                    Select flow...
                                </option>
                                <option value="QC Analysis">QC Analysis</option>
                                <option value="Tech'l Verfication">
                                    Tech'l Verification
                                </option>
                            </select>
                            <p
                                v-if="errors.defect_flow"
                                class="mt-1.5 text-sm font-medium text-destructive"
                            >
                                {{ errors.defect_flow }}
                            </p>
                        </div>

                        <!-- Remarks -->
                        <div>
                            <label
                                class="mb-2 block text-sm font-semibold text-foreground"
                                >Remarks
                                <span class="font-normal text-muted-foreground"
                                    >(optional)</span
                                ></label
                            >
                            <textarea
                                v-model="form.remarks"
                                rows="3"
                                class="flex w-full resize-none rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                                placeholder="Additional notes..."
                            ></textarea>
                        </div>
                    </div>

                    <div
                        class="flex flex-shrink-0 justify-end gap-3 rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-4"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted"
                            @click="closeModal"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-2.5 text-sm font-semibold text-primary-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50"
                            :disabled="submitting"
                        >
                            <span
                                v-if="submitting"
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                            ></span>
                            {{ isEditMode ? 'Update Defect' : 'Add Defect' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click="closeDeleteModal"
        >
            <div
                class="w-full max-w-md animate-in rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 duration-300 fade-in-0 zoom-in-95"
                @click.stop
            >
                <div
                    class="flex items-center justify-between rounded-t-2xl border-b border-border/50 bg-gradient-to-r from-destructive/5 to-destructive/10 px-6 py-5"
                >
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-destructive/10 p-2">
                            <Trash2 class="h-5 w-5 text-destructive" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-foreground">
                                Confirm Delete
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                This action cannot be undone
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="closeDeleteModal"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-6">
                    <div
                        class="rounded-lg border border-destructive/20 bg-destructive/5 p-4"
                    >
                        <p class="text-sm text-foreground">
                            Are you sure you want to delete defect
                            <span class="font-bold text-destructive"
                                >{{ defectToDelete?.defect_code }} —
                                {{ defectToDelete?.defect_name }}</span
                            >?
                        </p>
                        <p class="mt-2 text-sm font-medium text-destructive">
                            This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div
                    class="flex justify-end gap-3 rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-gradient-to-r from-destructive to-destructive/90 px-6 py-2.5 text-sm font-semibold text-destructive-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-destructive/25 disabled:pointer-events-none disabled:opacity-50"
                        :disabled="deleting"
                        @click="deleteDefect"
                    >
                        <span
                            v-if="deleting"
                            class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                        ></span>
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import {
    CheckCircle,
    ClipboardList,
    Pencil,
    PlusCircle,
    Trash2,
    Wrench,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Defect {
    id: number;
    defect_code: string;
    defect_name: string;
    defect_class: string;
    defect_flow: string;
    created_by: string;
    remarks: string | null;
}

interface DefectForm {
    id: number | null;
    defect_code: string;
    defect_name: string;
    defect_class: string;
    defect_flow: string;
    remarks: string;
}

const defects = ref<Defect[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const flowFilter = ref('');

const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const errors = ref<Record<string, string>>({});
const form = ref<DefectForm>({
    id: null,
    defect_code: '',
    defect_name: '',
    defect_class: '',
    defect_flow: '',
    remarks: '',
});

const showDeleteModal = ref(false);
const defectToDelete = ref<Defect | null>(null);
const deleting = ref(false);

const qcAnalysisCount = computed(
    () => defects.value.filter((d) => d.defect_flow === 'QC Analysis').length,
);
const techVerifCount = computed(
    () => defects.value.filter((d) => d.defect_flow !== 'QC Analysis').length,
);

onMounted(fetchDefects);

async function fetchDefects() {
    loading.value = true;
    try {
        const { data } = await axios.get<Defect[]>('/api/qc-defect-class', {
            params: {
                search: searchQuery.value || undefined,
                defect_flow: flowFilter.value || undefined,
            },
        });
        defects.value = data;
    } catch {
        showToast('Failed to load defects.');
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    searchQuery.value = '';
    flowFilter.value = '';
    fetchDefects();
}

function openCreateModal() {
    isEditMode.value = false;
    form.value = {
        id: null,
        defect_code: '',
        defect_name: '',
        defect_class: '',
        defect_flow: '',
        remarks: '',
    };
    errors.value = {};
    showModal.value = true;
}

function editDefect(defect: Defect) {
    isEditMode.value = true;
    form.value = {
        id: defect.id,
        defect_code: defect.defect_code,
        defect_name: defect.defect_name,
        defect_class: defect.defect_class ?? '',
        defect_flow: defect.defect_flow,
        remarks: defect.remarks ?? '',
    };
    errors.value = {};
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
}

async function saveDefect() {
    submitting.value = true;
    errors.value = {};
    try {
        if (isEditMode.value && form.value.id) {
            await axios.put(
                `/api/qc-defect-class/${form.value.id}`,
                form.value,
            );
            showToast('Defect updated successfully.', 'success');
        } else {
            await axios.post('/api/qc-defect-class', form.value);
            showToast('Defect added successfully.', 'success');
        }
        closeModal();
        fetchDefects();
    } catch (err: any) {
        if (err.response?.status === 422) {
            const raw = err.response.data.errors as Record<string, string[]>;
            Object.keys(raw).forEach((k) => (errors.value[k] = raw[k][0]));
        } else {
            showToast('An error occurred. Please try again.');
        }
    } finally {
        submitting.value = false;
    }
}

function confirmDelete(defect: Defect) {
    defectToDelete.value = defect;
    showDeleteModal.value = true;
}

function closeDeleteModal() {
    showDeleteModal.value = false;
    defectToDelete.value = null;
}

async function deleteDefect() {
    if (!defectToDelete.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/api/qc-defect-class/${defectToDelete.value.id}`);
        showToast('Defect deleted.', 'success');
        closeDeleteModal();
        fetchDefects();
    } catch {
        showToast('Failed to delete defect.');
    } finally {
        deleting.value = false;
    }
}

// Simple toast using browser alert as fallback — replace with your toast system if available
function showToast(
    message: string,
    _type: 'success' | 'danger' | string = 'danger',
) {
    console.log(`[${_type}] ${message}`);
}
</script>
