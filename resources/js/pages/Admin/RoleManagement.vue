<template>
    <AppLayout>
        <template #header>
            <h2 class="flex items-center text-xl font-semibold">
                <Shield class="mr-2 h-5 w-5" />
                Role Management
            </h2>
        </template>

        <div class="container mx-auto px-4 py-6">
            <div
                class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold">Roles & Permissions</h1>
                    <p class="text-muted-foreground">
                        Manage system roles and their permissions
                    </p>
                </div>
                <button
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-primary/90 hover:to-primary hover:shadow-xl hover:shadow-primary/25"
                    @click="openCreateModal"
                >
                    <PlusCircle class="mr-2 h-4 w-4" />
                    Add Role
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="mb-6 grid gap-6 md:grid-cols-3">
                <!-- Total Roles -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 p-6 shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/10 dark:from-blue-950/30 dark:to-blue-900/20 dark:hover:shadow-blue-400/5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-blue-700 dark:text-blue-300"
                            >
                                Total Roles
                            </p>
                            <p
                                class="text-3xl font-bold text-blue-900 dark:text-blue-100"
                            >
                                {{ roles.length }}
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-blue-500/10 p-4 ring-1 ring-blue-500/20 transition-all duration-300 group-hover:bg-blue-500/20 group-hover:ring-blue-500/30"
                        >
                            <Shield
                                class="h-7 w-7 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                    </div>
                    <div
                        class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-blue-500/5"
                    ></div>
                </div>

                <!-- System Roles -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-purple-50 to-purple-100/50 p-6 shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-purple-500/10 dark:from-purple-950/30 dark:to-purple-900/20 dark:hover:shadow-purple-400/5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-purple-700 dark:text-purple-300"
                            >
                                System Roles
                            </p>
                            <p
                                class="text-3xl font-bold text-purple-900 dark:text-purple-100"
                            >
                                {{ systemRolesCount }}
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-purple-500/10 p-4 ring-1 ring-purple-500/20 transition-all duration-300 group-hover:bg-purple-500/20 group-hover:ring-purple-500/30"
                        >
                            <Settings
                                class="h-7 w-7 text-purple-600 dark:text-purple-400"
                            />
                        </div>
                    </div>
                    <div
                        class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-purple-500/5"
                    ></div>
                </div>

                <!-- Custom Roles -->
                <div
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-6 shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-emerald-500/10 dark:from-emerald-950/30 dark:to-emerald-900/20 dark:hover:shadow-emerald-400/5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-sm font-medium text-emerald-700 dark:text-emerald-300"
                            >
                                Custom Roles
                            </p>
                            <p
                                class="text-3xl font-bold text-emerald-900 dark:text-emerald-100"
                            >
                                {{ customRolesCount }}
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-emerald-500/10 p-4 ring-1 ring-emerald-500/20 transition-all duration-300 group-hover:bg-emerald-500/20 group-hover:ring-emerald-500/30"
                        >
                            <UserPlus
                                class="h-7 w-7 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                    </div>
                    <div
                        class="absolute -top-4 -right-4 h-16 w-16 rounded-full bg-emerald-500/5"
                    ></div>
                </div>
            </div>

            <!-- Roles Grid -->
            <div v-if="loading" class="flex justify-center py-12">
                <div
                    class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-r-transparent"
                    role="status"
                >
                    <span class="sr-only">Loading...</span>
                </div>
            </div>

            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="role in roles"
                    :key="role.id"
                    class="group relative overflow-hidden rounded-xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/5"
                >
                    <div
                        class="absolute inset-0 bg-gradient-to-br from-transparent to-muted/20"
                    ></div>
                    <div class="relative p-6">
                        <div class="mb-4 flex items-start justify-between">
                            <div>
                                <h3
                                    class="text-lg font-bold text-foreground capitalize transition-colors duration-200 group-hover:text-primary"
                                >
                                    {{ role.name }}
                                </h3>
                                <div class="mt-1 flex items-center gap-2">
                                    <div class="flex items-center gap-1">
                                        <div
                                            class="h-2 w-2 rounded-full bg-emerald-500"
                                        ></div>
                                        <p
                                            class="text-sm font-medium text-muted-foreground"
                                        >
                                            {{ role.users_count || 0 }}
                                            employees
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <span
                                :class="getRoleBadgeClass(role.name)"
                                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize shadow-sm ring-1 ring-inset"
                            >
                                {{ role.name }}
                            </span>
                        </div>

                        <div class="mb-6">
                            <p
                                class="mb-3 text-sm font-semibold text-foreground"
                            >
                                Permissions
                            </p>
                            <div
                                v-if="
                                    getDisplayPermissions(role.permissions)
                                        .length > 0
                                "
                                class="flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="permission in getDisplayPermissions(
                                        role.permissions,
                                    )"
                                    :key="permission"
                                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-muted to-muted/80 px-3 py-1.5 text-xs font-medium text-foreground shadow-sm ring-1 ring-border/50 transition-all duration-200 hover:shadow-md hover:ring-border"
                                >
                                    {{ formatPermission(permission) }}
                                </span>
                            </div>
                            <div
                                v-else
                                class="flex items-center gap-2 text-sm text-muted-foreground"
                            >
                                <div
                                    class="h-1.5 w-1.5 rounded-full bg-muted-foreground/50"
                                ></div>
                                No permissions assigned
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button
                                class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-primary/20 bg-gradient-to-r from-primary/5 to-primary/10 px-4 py-2.5 text-sm font-semibold text-primary shadow-sm transition-all duration-200 hover:border-primary/30 hover:from-primary/10 hover:to-primary/20 hover:shadow-md hover:shadow-primary/10"
                                @click="editRole(role)"
                            >
                                <Pencil class="h-4 w-4" />
                                Edit
                            </button>
                            <button
                                v-if="
                                    ![
                                        'Admin',
                                        'Manager',
                                        'Moderator',
                                        'User',
                                    ].includes(role.name)
                                "
                                class="flex items-center justify-center rounded-lg border border-destructive/20 bg-gradient-to-r from-destructive/5 to-destructive/10 px-4 py-2.5 text-sm font-semibold text-destructive shadow-sm transition-all duration-200 hover:border-destructive/30 hover:from-destructive/10 hover:to-destructive/20 hover:shadow-md hover:shadow-destructive/10"
                                @click="confirmDeleteRole(role)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="!loading && roles.length === 0"
                class="py-12 text-center text-muted-foreground"
            >
                <Shield class="mx-auto h-16 w-16" />
                <p class="mt-3">No roles found</p>
            </div>
        </div>

        <!-- Add/Edit Role Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click="closeModal"
        >
            <div
                class="flex max-h-[90vh] w-full max-w-2xl animate-in flex-col rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 duration-300 fade-in-0 zoom-in-95"
                @click.stop
            >
                <div
                    class="flex items-center justify-between rounded-t-2xl border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10 px-6 py-5"
                >
                    <div>
                        <h3 class="text-xl font-bold text-foreground">
                            {{ isEditMode ? 'Edit Role' : 'Create Role' }}
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                isEditMode
                                    ? 'Update role details and permissions'
                                    : 'Define a new role with specific permissions'
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
                    @submit.prevent="saveRole"
                    class="flex flex-1 flex-col overflow-hidden"
                >
                    <div class="space-y-6 overflow-y-auto p-6">
                        <div>
                            <label
                                for="roleName"
                                class="mb-3 block text-sm font-semibold text-foreground"
                                >Role Name</label
                            >
                            <input
                                id="roleName"
                                v-model="form.name"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive':
                                        errors.name,
                                }"
                                placeholder="e.g., Department Head"
                                @input="updateSlug"
                                required
                            />
                            <p
                                v-if="errors.name"
                                class="mt-2 text-sm font-medium text-destructive"
                            >
                                {{ errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="roleSlug"
                                class="mb-3 block text-sm font-semibold text-foreground"
                                >Role Slug</label
                            >
                            <input
                                id="roleSlug"
                                v-model="form.slug"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive':
                                        errors.slug,
                                }"
                                placeholder="e.g., department-head"
                                :disabled="isEditMode"
                                required
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Lowercase letters, numbers, and hyphens only.
                                Used for system identification.
                            </p>
                            <p
                                v-if="errors.slug"
                                class="mt-2 text-sm font-medium text-destructive"
                            >
                                {{ errors.slug }}
                            </p>
                        </div>

                        <div>
                            <label
                                for="roleDescription"
                                class="mb-3 block text-sm font-semibold text-foreground"
                                >Description (Optional)</label
                            >
                            <textarea
                                id="roleDescription"
                                v-model="form.description"
                                rows="3"
                                class="flex w-full resize-none rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{
                                    'border-destructive focus-visible:ring-destructive':
                                        errors.description,
                                }"
                                placeholder="Brief description of this role's responsibilities and access level..."
                            ></textarea>
                            <p
                                v-if="errors.description"
                                class="mt-2 text-sm font-medium text-destructive"
                            >
                                {{ errors.description }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-3 block text-sm font-semibold text-foreground"
                                >Permissions</label
                            >
                            <div
                                class="rounded-lg border border-border/50 bg-muted/20 p-4"
                            >
                                <div
                                    class="grid grid-cols-1 gap-3 md:grid-cols-2"
                                >
                                    <div
                                        v-for="permission in allPermissions"
                                        :key="permission.value"
                                        class="flex items-center space-x-3 rounded-md p-2 transition-colors hover:bg-muted/50"
                                    >
                                        <input
                                            :id="permission.value"
                                            v-model="form.permissions"
                                            type="checkbox"
                                            :value="permission.value"
                                            class="h-4 w-4 rounded border-2 border-muted-foreground/30 text-primary shadow-sm transition-all focus:ring-2 focus:ring-primary focus:ring-offset-2"
                                        />
                                        <label
                                            :for="permission.value"
                                            class="cursor-pointer text-sm leading-none font-medium text-foreground peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                        >
                                            {{ permission.label }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="flex flex-shrink-0 justify-end gap-3 rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-4"
                    >
                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md"
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
                            {{ isEditMode ? 'Update Role' : 'Create Role' }}
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
                            Are you sure you want to delete role
                            <span class="font-bold text-destructive">{{
                                roleToDelete?.name
                            }}</span
                            >?
                        </p>
                        <p class="mt-2 text-sm font-medium text-destructive">
                            This action cannot be undone and will permanently
                            remove this role.
                        </p>
                    </div>
                </div>
                <div
                    class="flex justify-end gap-3 rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md"
                        @click="closeDeleteModal"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-gradient-to-r from-destructive to-destructive/90 px-6 py-2.5 text-sm font-semibold text-destructive-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-destructive/25 disabled:pointer-events-none disabled:opacity-50"
                        :disabled="deleting"
                        @click="deleteRole"
                    >
                        <span
                            v-if="deleting"
                            class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                        ></span>
                        Delete Role
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
    Pencil,
    PlusCircle,
    Settings,
    Shield,
    Trash2,
    UserPlus,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Role {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    permissions: string[];
    users_count?: number;
}

interface RoleForm {
    id: number | null;
    name: string;
    slug: string;
    description: string;
    permissions: string[];
}

interface Permission {
    value: string;
    label: string;
}

const roles = ref<Role[]>([]);
const loading = ref(false);
const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const form = ref<RoleForm>({
    id: null,
    name: '',
    slug: '',
    description: '',
    permissions: [],
});
const errors = ref<Record<string, string>>({});

const showDeleteModal = ref(false);
const roleToDelete = ref<Role | null>(null);
const deleting = ref(false);

const allPermissions: Permission[] = [
    { value: 'Employees Manage', label: 'Manage Users' },
    { value: 'Roles Manage', label: 'Manage Roles & Permissions' },
    { value: 'Settings Manage', label: 'Manage System Settings' },
    { value: 'MC Allocation Edit', label: 'Edit MC Allocation' },
    { value: 'Endtime Manage', label: 'Manage Endtime Records' },
    { value: 'Data Entry Access', label: 'Access Data Entry' },
    { value: 'MC Allocation Delete', label: 'Delete MC Allocation' },
    { value: 'Endtime Delete', label: 'Delete Endtime Records' },
    { value: 'Manage Endline', label: 'Manage Endline' },
    { value: 'Delete Endline', label: 'Delete Endline' },
];

// Computed statistics
const systemRolesCount = computed(() => {
    return roles.value.filter((role) =>
        ['Admin', 'Manager', 'Moderator', 'User'].includes(role.name),
    ).length;
});

const customRolesCount = computed(() => {
    return roles.value.filter(
        (role) =>
            !['Admin', 'Manager', 'Moderator', 'User'].includes(role.name),
    ).length;
});

onMounted(() => {
    loadRoles();
});

const loadRoles = async () => {
    loading.value = true;
    try {
        const response = await axios.get<Role[]>('/admin/api/roles');
        roles.value = response.data;
    } catch (error) {
        console.error('Error fetching roles:', error);
        showToast('Error loading roles', 'danger');
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    isEditMode.value = false;
    form.value = {
        id: null,
        name: '',
        slug: '',
        description: '',
        permissions: [],
    };
    errors.value = {};
    showModal.value = true;
};

const editRole = (role: Role) => {
    isEditMode.value = true;
    form.value = {
        id: role.id,
        name: role.name,
        slug: role.slug,
        description: role.description || '',
        permissions: role.permissions || [],
    };
    errors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.value = {
        id: null,
        name: '',
        slug: '',
        description: '',
        permissions: [],
    };
    errors.value = {};
};

const saveRole = async () => {
    submitting.value = true;
    errors.value = {};

    try {
        if (isEditMode.value) {
            await axios.put(`/admin/api/roles/${form.value.id}`, form.value);
            showToast('Role updated successfully', 'success');
        } else {
            await axios.post('/admin/api/roles', form.value);
            showToast('Role created successfully', 'success');
        }
        closeModal();
        loadRoles();
    } catch (error: any) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
            Object.keys(errors.value).forEach((key) => {
                errors.value[key] = errors.value[key][0];
            });
        } else {
            showToast(
                error.response?.data?.message || 'An error occurred',
                'danger',
            );
        }
    } finally {
        submitting.value = false;
    }
};

const confirmDeleteRole = (role: Role) => {
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    roleToDelete.value = null;
};

const deleteRole = async () => {
    if (!roleToDelete.value) return;

    deleting.value = true;
    try {
        await axios.delete(`/admin/api/roles/${roleToDelete.value.id}`);
        showToast('Role deleted successfully', 'success');
        closeDeleteModal();
        loadRoles();
    } catch (error: any) {
        showToast(
            error.response?.data?.message || 'Error deleting role',
            'danger',
        );
    } finally {
        deleting.value = false;
    }
};

const getRoleBadgeClass = (roleName: string) => {
    const classes: Record<string, string> = {
        Admin: 'bg-gradient-to-r from-red-500 to-red-600 text-white ring-red-500/30',
        Manager:
            'bg-gradient-to-r from-amber-500 to-orange-500 text-white ring-amber-500/30',
        Moderator:
            'bg-gradient-to-r from-purple-500 to-purple-600 text-white ring-purple-500/30',
        User: 'bg-gradient-to-r from-slate-500 to-slate-600 text-white ring-slate-500/30',
    };
    return (
        classes[roleName] ||
        'bg-gradient-to-r from-blue-500 to-blue-600 text-white ring-blue-500/30'
    );
};

const getDisplayPermissions = (permissions: string[] | null | undefined) => {
    if (!permissions || !Array.isArray(permissions)) return [];

    // Get list of valid permission values from allPermissions
    const validPermissions = allPermissions.map((p) => p.value);

    // Filter to only show permissions that are in our allPermissions list
    return permissions.filter((permission) =>
        validPermissions.includes(permission),
    );
};

const formatPermission = (permission: string) => {
    // Find the permission in allPermissions to get its label
    const permissionObj = allPermissions.find((p) => p.value === permission);
    return permissionObj ? permissionObj.label : permission;
};

const generateSlug = (name: string) => {
    return name
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
        .replace(/\s+/g, '-') // Replace spaces with hyphens
        .replace(/-+/g, '-') // Replace multiple hyphens with single hyphen
        .replace(/^-+|-+$/g, ''); // Remove leading/trailing hyphens
};

const updateSlug = () => {
    if (!isEditMode.value) {
        form.value.slug = generateSlug(form.value.name);
    }
};

const showToast = (
    message: string,
    type: 'success' | 'danger' | 'info' = 'info',
) => {
    const toast = document.createElement('div');
    const bgColors: Record<string, string> = {
        success: 'bg-green-600',
        danger: 'bg-red-600',
        info: 'bg-blue-600',
    };
    toast.className = `fixed top-4 right-4 ${bgColors[type] || bgColors.info} text-white px-6 py-3 rounded-lg shadow-lg z-[9999]`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
};
</script>
