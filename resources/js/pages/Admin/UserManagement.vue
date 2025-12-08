<template>
    <AppLayout>
        <template #header>
            <h2 class="flex items-center text-xl font-semibold">
                <Users class="mr-2 h-5 w-5" />User Management
            </h2>
        </template>

        <div class="container mx-auto px-4 py-6">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h1 class="text-2xl font-bold">User Management</h1>
                <button class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:shadow-xl hover:shadow-primary/25 hover:from-primary/90 hover:to-primary hover:-translate-y-0.5" @click="openCreateModal">
                    <PlusCircle class="mr-2 h-4 w-4" />Create User
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="mb-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                <!-- Total Users -->
                <div class="group relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 p-6 shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/10 dark:from-blue-950/30 dark:to-blue-900/20 dark:hover:shadow-blue-400/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-700 dark:text-blue-300">Total Users</p>
                            <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ userStats.total }}</p>
                        </div>
                        <div class="rounded-full bg-blue-500/10 p-4 ring-1 ring-blue-500/20 transition-all duration-300 group-hover:bg-blue-500/20 group-hover:ring-blue-500/30">
                            <Users class="h-7 w-7 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full bg-blue-500/5"></div>
                </div>

                <!-- Dynamic Role Cards -->
                <div 
                    v-for="(count, roleKey) in roleStats" 
                    :key="roleKey"
                    class="group relative overflow-hidden rounded-xl border border-border/50 p-6 shadow-lg transition-all duration-300 hover:shadow-xl dark:hover:shadow-400/5"
                    :class="getRoleCardClass(roleKey)"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium capitalize" :class="getRoleTextClass(roleKey)">{{ getRoleDisplayName(roleKey) }}</p>
                            <p class="text-3xl font-bold" :class="getRoleBoldTextClass(roleKey)">{{ count }}</p>
                        </div>
                        <div class="rounded-full p-4 ring-1 transition-all duration-300" :class="getRoleIconClass(roleKey)">
                            <component :is="getRoleIcon(roleKey)" class="h-7 w-7" :class="getRoleIconColorClass(roleKey)" />
                        </div>
                    </div>
                    <div class="absolute -right-4 -top-4 h-16 w-16 rounded-full" :class="getRoleAccentClass(roleKey)"></div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="mb-6 rounded-xl border border-border/50 bg-card shadow-lg">
                <div class="p-6">
                    <div class="grid gap-4 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <input
                                v-model="searchQuery"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                placeholder="Search by name or employee ID..."
                                @input="debouncedSearch"
                            />
                        </div>
                        <div class="md:col-span-4">
                            <select v-model="roleFilter" class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50" @change="fetchUsers">
                                <option value="">All Roles</option>
                                <option v-for="role in roles" :key="role.id" :value="role.slug">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <button class="inline-flex h-12 w-full items-center justify-center rounded-lg border border-border bg-background px-4 py-3 text-sm font-semibold shadow-sm transition-all hover:bg-muted hover:shadow-md" @click="clearFilters">
                                <XCircle class="mr-2 h-4 w-4" />Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="rounded-xl border border-border/50 bg-card shadow-lg">
                <div class="p-6">
                    <div v-if="loading" class="flex justify-center py-12">
                        <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-r-transparent" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>

                    <div v-else-if="users.data && users.data.length > 0">
                        <!-- Desktop Table -->
                        <div class="hidden overflow-x-auto md:block">
                            <table class="w-full text-sm">
                                <thead class="border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10">
                                    <tr>
                                        <th class="px-6 py-4 text-left font-bold uppercase tracking-wide text-foreground">Name</th>
                                        <th class="px-6 py-4 text-left font-bold uppercase tracking-wide text-foreground">Employee ID</th>
                                        <th class="px-6 py-4 text-left font-bold uppercase tracking-wide text-foreground">Position</th>
                                        <th class="px-6 py-4 text-left font-bold uppercase tracking-wide text-foreground">Role</th>
                                        <th class="px-6 py-4 text-left font-bold uppercase tracking-wide text-foreground">Created At</th>
                                        <th class="px-6 py-4 text-right font-bold uppercase tracking-wide text-foreground">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr v-for="user in users.data" :key="user.id" class="group transition-all duration-200 hover:bg-muted/30">
                                        <td class="px-6 py-4 font-semibold text-foreground">
                                            <div class="flex items-center gap-2">
                                                {{ user.emp_name || user.name }}
                                                <span v-if="user.emp_no === '21278703'" class="inline-flex items-center rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-2 py-1 text-xs font-semibold text-white shadow-sm">
                                                    <Shield class="mr-1 h-3 w-3" />
                                                    System Admin
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-muted-foreground">{{ user.emp_no }}</td>
                                        <td class="px-6 py-4 text-muted-foreground">{{ user.position || 'N/A' }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="getRoleBadgeClass(user.role)" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize shadow-sm ring-1 ring-inset">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    v-if="isUserActive(user)"
                                                    class="inline-flex items-center rounded-lg border border-emerald-500/20 bg-gradient-to-r from-emerald-500/5 to-emerald-500/10 px-3 py-2 text-xs font-semibold text-emerald-600 shadow-sm transition-all duration-200 hover:border-emerald-500/30 hover:from-emerald-500/10 hover:to-emerald-500/20 hover:shadow-md hover:shadow-emerald-500/10 disabled:pointer-events-none disabled:opacity-50"
                                                    @click="toggleUserStatus(user)"
                                                    :disabled="togglingStatus[user.id] || user.emp_no === '21278703'"
                                                    :aria-label="`Deactivate ${user.emp_name || user.name}`"
                                                    :title="user.emp_no === '21278703' ? 'Cannot deactivate system administrator' : 'Deactivate user account'"
                                                >
                                                    <CheckCircle class="h-3 w-3" />
                                                </button>
                                                <button
                                                    v-else
                                                    class="inline-flex items-center rounded-lg border border-orange-500/20 bg-gradient-to-r from-orange-500/5 to-orange-500/10 px-3 py-2 text-xs font-semibold text-orange-600 shadow-sm transition-all duration-200 hover:border-orange-500/30 hover:from-orange-500/10 hover:to-orange-500/20 hover:shadow-md hover:shadow-orange-500/10 disabled:pointer-events-none disabled:opacity-50"
                                                    @click="toggleUserStatus(user)"
                                                    :disabled="togglingStatus[user.id]"
                                                    :aria-label="`Activate ${user.emp_name || user.name}`"
                                                    title="Activate user account"
                                                >
                                                    <Ban class="h-3 w-3" />
                                                </button>
                                                <button
                                                    class="inline-flex items-center rounded-lg border border-primary/20 bg-gradient-to-r from-primary/5 to-primary/10 px-3 py-2 text-xs font-semibold text-primary shadow-sm transition-all duration-200 hover:border-primary/30 hover:from-primary/10 hover:to-primary/20 hover:shadow-md hover:shadow-primary/10"
                                                    @click="openEditModal(user)"
                                                    :aria-label="`Edit ${user.emp_name || user.name}`"
                                                    :title="user.emp_no === '21278703' ? 'Edit user (role cannot be changed for system admin)' : 'Edit user'"
                                                >
                                                    <Pencil class="h-3 w-3" />
                                                </button>
                                                <button
                                                    class="inline-flex items-center rounded-lg border border-destructive/20 bg-gradient-to-r from-destructive/5 to-destructive/10 px-3 py-2 text-xs font-semibold text-destructive shadow-sm transition-all duration-200 hover:border-destructive/30 hover:from-destructive/10 hover:to-destructive/20 hover:shadow-md hover:shadow-destructive/10 disabled:pointer-events-none disabled:opacity-50"
                                                    @click="confirmDelete(user)"
                                                    :disabled="user.emp_no === '21278703'"
                                                    :aria-label="`Delete ${user.emp_name || user.name}`"
                                                    :title="user.emp_no === '21278703' ? 'Cannot delete system administrator' : 'Delete user'"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile Cards -->
                        <div class="space-y-4 md:hidden">
                            <div v-for="user in users.data" :key="user.id" class="group relative overflow-hidden rounded-xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-primary/5 hover:-translate-y-1">
                                <div class="absolute inset-0 bg-gradient-to-br from-transparent to-muted/20"></div>
                                <div class="relative p-5">
                                    <div class="mb-4 flex items-start justify-between">
                                        <div class="mb-3">
                                            <div class="text-lg font-bold text-foreground group-hover:text-primary transition-colors duration-200">{{ user.emp_name || user.name }}</div>
                                            <span v-if="user.emp_no === '21278703'" class="inline-flex items-center rounded-full bg-gradient-to-r from-amber-500 to-amber-600 px-2 py-1 text-xs font-semibold text-white shadow-sm mt-1">
                                                <Shield class="mr-1 h-3 w-3" />
                                                System Admin
                                            </span>
                                        </div>
                                        <span :class="getRoleBadgeClass(user.role)" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold capitalize shadow-sm ring-1 ring-inset">
                                            {{ user.role }}
                                        </span>
                                    </div>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="w-24 font-semibold text-muted-foreground">Employee ID:</span>
                                            <span class="text-foreground">{{ user.emp_no }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-24 font-semibold text-muted-foreground">Position:</span>
                                            <span class="text-foreground">{{ user.position || 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-24 font-semibold text-muted-foreground">Created:</span>
                                            <span class="text-foreground">{{ formatDate(user.created_at) }}</span>
                                        </div>
                                    </div>
                                    <div class="mt-5 flex gap-3 border-t border-border/50 pt-4">
                                        <button
                                            v-if="isUserActive(user)"
                                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-emerald-500/20 bg-gradient-to-r from-emerald-500/5 to-emerald-500/10 px-4 py-2.5 text-sm font-semibold text-emerald-600 shadow-sm transition-all duration-200 hover:border-emerald-500/30 hover:from-emerald-500/10 hover:to-emerald-500/20 hover:shadow-md hover:shadow-emerald-500/10 disabled:pointer-events-none disabled:opacity-50"
                                            @click="toggleUserStatus(user)"
                                            :disabled="togglingStatus[user.id] || user.emp_no === '21278703'"
                                            :title="user.emp_no === '21278703' ? 'Cannot deactivate system administrator' : 'Deactivate user account'"
                                        >
                                            <CheckCircle class="h-4 w-4" />
                                            Active
                                        </button>
                                        <button
                                            v-else
                                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-orange-500/20 bg-gradient-to-r from-orange-500/5 to-orange-500/10 px-4 py-2.5 text-sm font-semibold text-orange-600 shadow-sm transition-all duration-200 hover:border-orange-500/30 hover:from-orange-500/10 hover:to-orange-500/20 hover:shadow-md hover:shadow-orange-500/10 disabled:pointer-events-none disabled:opacity-50"
                                            @click="toggleUserStatus(user)"
                                            :disabled="togglingStatus[user.id]"
                                            title="Activate user account"
                                        >
                                            <Ban class="h-4 w-4" />
                                            Inactive
                                        </button>
                                        <button
                                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-primary/20 bg-gradient-to-r from-primary/5 to-primary/10 px-4 py-2.5 text-sm font-semibold text-primary shadow-sm transition-all duration-200 hover:border-primary/30 hover:from-primary/10 hover:to-primary/20 hover:shadow-md hover:shadow-primary/10"
                                            @click="openEditModal(user)"
                                        >
                                            <Pencil class="h-4 w-4" />
                                            Edit
                                        </button>
                                        <button
                                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-destructive/20 bg-gradient-to-r from-destructive/5 to-destructive/10 px-4 py-2.5 text-sm font-semibold text-destructive shadow-sm transition-all duration-200 hover:border-destructive/30 hover:from-destructive/10 hover:to-destructive/20 hover:shadow-md hover:shadow-destructive/10 disabled:pointer-events-none disabled:opacity-50"
                                            @click="confirmDelete(user)"
                                            :disabled="user.emp_no === '21278703'"
                                            :title="user.emp_no === '21278703' ? 'Cannot delete system administrator' : 'Delete user'"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <nav v-if="users.last_page > 1" class="mt-6">
                            <ul class="flex flex-wrap justify-center gap-1">
                                <li>
                                    <button 
                                        class="inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
                                        :disabled="users.current_page === 1"
                                        @click="changePage(users.current_page - 1)"
                                    >
                                        Previous
                                    </button>
                                </li>
                                <li v-for="page in paginationPages" :key="page">
                                    <button 
                                        class="inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
                                        :class="page === users.current_page ? 'bg-primary text-primary-foreground hover:bg-primary/90' : ''"
                                        @click="changePage(page)"
                                    >
                                        {{ page }}
                                    </button>
                                </li>
                                <li>
                                    <button 
                                        class="inline-flex h-9 items-center justify-center rounded-md px-4 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground disabled:pointer-events-none disabled:opacity-50"
                                        :disabled="users.current_page === users.last_page"
                                        @click="changePage(users.current_page + 1)"
                                    >
                                        Next
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    </div>

                    <div v-else class="py-12 text-center text-muted-foreground">
                        <Inbox class="mx-auto h-16 w-16" />
                        <p class="mt-3">No users found</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit User Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
            @click="closeModal"
        >
            <div class="w-full max-w-lg rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 animate-in fade-in-0 zoom-in-95 duration-300" @click.stop>
                <div class="flex items-center justify-between border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10 px-6 py-5 rounded-t-2xl">
                    <div>
                        <h3 class="text-xl font-bold text-foreground">{{ isEditMode ? 'Edit User' : 'Create User' }}</h3>
                        <p class="text-sm text-muted-foreground mt-1">{{ isEditMode ? 'Update user details and permissions' : 'Add a new user to the system' }}</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground" @click="closeModal">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="space-y-6 p-6">
                        <div>
                            <label for="emp_name" class="mb-3 block text-sm font-semibold text-foreground">Full Name</label>
                            <input
                                id="emp_name"
                                v-model="form.emp_name"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive focus-visible:ring-destructive': errors.emp_name }"
                                placeholder="Surname, First Name, Middle Name"
                                required
                            />
                            <p v-if="errors.emp_name" class="mt-2 text-sm text-destructive font-medium">
                                {{ errors.emp_name }}
                            </p>
                        </div>

                        <div>
                            <label for="emp_no" class="mb-3 block text-sm font-semibold text-foreground">Employee ID Number</label>
                            <input
                                id="emp_no"
                                v-model="form.emp_no"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive focus-visible:ring-destructive': errors.emp_no }"
                                :disabled="isEditMode && form.emp_no === '21278703'"
                                placeholder="Enter employee ID number"
                                required
                            />
                            <p v-if="errors.emp_no" class="mt-2 text-sm text-destructive font-medium">
                                {{ errors.emp_no }}
                            </p>
                            <p v-if="isEditMode && form.emp_no === '21278703'" class="mt-1 text-xs text-muted-foreground">
                                Employee ID cannot be changed for system administrator
                            </p>
                        </div>

                        <div>
                            <label for="position" class="mb-3 block text-sm font-semibold text-foreground">Position</label>
                            <input
                                id="position"
                                v-model="form.position"
                                type="text"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive focus-visible:ring-destructive': errors.position }"
                                placeholder="Enter job position"
                            />
                            <p v-if="errors.position" class="mt-2 text-sm text-destructive font-medium">
                                {{ errors.position }}
                            </p>
                        </div>

                        <div>
                            <label for="role" class="mb-3 block text-sm font-semibold text-foreground">Role</label>
                            <select
                                id="role"
                                v-model="form.role"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive focus-visible:ring-destructive': errors.role }"
                                :disabled="isEditMode && form.emp_no === '21278703'"
                                required
                            >
                                <option value="">Select Role</option>
                                <option v-for="role in roles" :key="role.id" :value="role.slug">
                                    {{ role.name }}
                                </option>
                            </select>
                            <p v-if="errors.role" class="mt-2 text-sm text-destructive font-medium">
                                {{ errors.role }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                <strong>Note:</strong> 
                                <span v-if="isEditMode && form.emp_no === '21278703'">
                                    Role cannot be changed for system administrator (21278703)
                                </span>
                                <span v-else>
                                    Admin role can only be assigned to employee ID 21278703
                                </span>
                            </p>
                        </div>

                        <div v-if="!isEditMode">
                            <label for="password" class="mb-3 block text-sm font-semibold text-foreground">
                                Password
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                :class="{ 'border-destructive focus-visible:ring-destructive': errors.password }"
                                required
                                placeholder="Enter password"
                            />
                            <p v-if="errors.password" class="mt-2 text-sm text-destructive font-medium">
                                {{ errors.password }}
                            </p>
                        </div>

                        <div v-if="!isEditMode">
                            <label for="password_confirmation" class="mb-3 block text-sm font-semibold text-foreground">
                                Confirm Password
                            </label>
                            <input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50"
                                required
                                placeholder="Confirm the password"
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-border/50 bg-muted/10 px-6 py-5 rounded-b-2xl">
                        <button type="button" class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md" @click="closeModal">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-2.5 text-sm font-semibold text-primary-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50" :disabled="submitting">
                            <span v-if="submitting" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"></span>
                            {{ isEditMode ? 'Update User' : 'Create User' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
            @click="closeDeleteModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 animate-in fade-in-0 zoom-in-95 duration-300" @click.stop>
                <div class="flex items-center justify-between border-b border-border/50 bg-gradient-to-r from-destructive/5 to-destructive/10 px-6 py-5 rounded-t-2xl">
                    <div class="flex items-center gap-3">
                        <div class="rounded-full bg-destructive/10 p-2">
                            <Trash2 class="h-5 w-5 text-destructive" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-foreground">Confirm Delete</h3>
                            <p class="text-sm text-muted-foreground">This action cannot be undone</p>
                        </div>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground" @click="closeDeleteModal">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-6">
                    <div class="rounded-lg border border-destructive/20 bg-destructive/5 p-4">
                        <p class="text-sm text-foreground">Are you sure you want to delete user <span class="font-bold text-destructive">{{ userToDelete?.emp_name || userToDelete?.name }}</span>?</p>
                        <p class="mt-2 text-sm text-destructive font-medium">This action cannot be undone and will permanently remove this user.</p>
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t border-border/50 bg-muted/10 px-6 py-5 rounded-b-2xl">
                    <button type="button" class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md" @click="closeDeleteModal">
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-gradient-to-r from-destructive to-destructive/90 px-6 py-2.5 text-sm font-semibold text-destructive-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-destructive/25 disabled:pointer-events-none disabled:opacity-50"
                        :disabled="deleting"
                        @click="deleteUser"
                    >
                        <span v-if="deleting" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"></span>
                        Delete User
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import { Users, PlusCircle, XCircle, Pencil, Trash2, Inbox, X, Shield, UserCheck, User, CheckCircle, Ban } from 'lucide-vue-next';

const users = ref({ data: [], current_page: 1, last_page: 1, stats: { total: 0, admins: 0, managers: 0, moderators: 0, regular_users: 0 } });
const roles = ref([]);
const loading = ref(false);
const searchQuery = ref('');
const roleFilter = ref('');
const currentPage = ref(1);

const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const form = ref({
    id: null,
    emp_name: '',
    emp_no: '',
    position: '',
    role: '',
    password: '',
    password_confirmation: '',
});
const errors = ref({});

const showDeleteModal = ref(false);
const userToDelete = ref(null);
const deleting = ref(false);

const togglingStatus = ref({});

let searchTimeout = null;

// Computed statistics
const userStats = computed(() => {
    return {
        total: users.value.stats?.total || 0
    };
});

// Dynamic role statistics
const roleStats = computed(() => {
    const stats = users.value.stats || {};
    const roleData = {};
    
    // Map all role stats dynamically
    Object.keys(stats).forEach(key => {
        if (key !== 'total') {
            roleData[key] = stats[key];
        }
    });
    
    return roleData;
});

onMounted(() => {
    fetchUsers();
    fetchRoles();
});



const fetchUsers = async () => {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            search: searchQuery.value,
            role: roleFilter.value,
        };
        const response = await axios.get('/admin/api/users', { params });
        users.value = response.data;
    } catch (error) {
        console.error('Error fetching users:', error);
        showToast('Error loading users', 'danger');
    } finally {
        loading.value = false;
    }
};

const fetchRoles = async () => {
    try {
        const response = await axios.get('/admin/api/roles');
        roles.value = response.data;
    } catch (error) {
        console.error('Error fetching roles:', error);
        showToast('Error loading roles', 'danger');
    }
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchUsers();
    }, 500);
};

const clearFilters = () => {
    searchQuery.value = '';
    roleFilter.value = '';
    currentPage.value = 1;
    fetchUsers();
};

const changePage = (page) => {
    if (page >= 1 && page <= users.value.last_page) {
        currentPage.value = page;
        fetchUsers();
    }
};

const paginationPages = computed(() => {
    const pages = [];
    const current = users.value.current_page;
    const last = users.value.last_page;
    
    let start = Math.max(1, current - 2);
    let end = Math.min(last, current + 2);
    
    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    
    return pages;
});

const openCreateModal = () => {
    isEditMode.value = false;
    form.value = {
        id: null,
        emp_name: '',
        emp_no: '',
        position: '',
        role: '',
        password: '',
        password_confirmation: '',
    };
    errors.value = {};
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditMode.value = true;
    form.value = {
        id: user.id,
        emp_name: user.emp_name || user.name,
        emp_no: user.emp_no,
        position: user.position || '',
        role: user.role,
        password: '',
        password_confirmation: '',
    };
    errors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.value = {
        id: null,
        emp_name: '',
        emp_no: '',
        position: '',
        role: '',
        password: '',
        password_confirmation: '',
    };
    errors.value = {};
};

const submitForm = async () => {
    submitting.value = true;
    errors.value = {};

    try {
        let response;
        if (isEditMode.value) {
            // For edit mode, exclude password fields
            const { password, password_confirmation, ...updateData } = form.value;
            response = await axios.put(`/admin/api/users/${form.value.id}`, updateData);
            showToast('User updated successfully', 'success');
        } else {
            response = await axios.post('/admin/api/users', form.value);
            showToast('User created successfully', 'success');
        }
        
        closeModal();
        
        // Check if the user updated their own role and needs to logout
        if (response.data?.logout) {
            showToast(response.data.message || 'Your role has been updated. Please log in again.', 'warning');
            // Redirect to login after a short delay
            setTimeout(() => {
                window.location.href = '/login';
            }, 2000);
        } else {
            fetchUsers();
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
            Object.keys(errors.value).forEach(key => {
                errors.value[key] = errors.value[key][0];
            });
        } else {
            showToast(error.response?.data?.message || 'An error occurred', 'danger');
        }
    } finally {
        submitting.value = false;
    }
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    userToDelete.value = null;
};

const deleteUser = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/admin/api/users/${userToDelete.value.id}`);
        showToast('User deleted successfully', 'success');
        closeDeleteModal();
        fetchUsers();
    } catch (error) {
        showToast(error.response?.data?.message || 'Error deleting user', 'danger');
    } finally {
        deleting.value = false;
    }
};

const getRoleBadgeClass = (role) => {
    const classes = {
        admin: 'bg-gradient-to-r from-red-500 to-red-600 text-white ring-red-500/30',
        manager: 'bg-gradient-to-r from-amber-500 to-orange-500 text-white ring-amber-500/30',
        moderator: 'bg-gradient-to-r from-purple-500 to-purple-600 text-white ring-purple-500/30',
        user: 'bg-gradient-to-r from-slate-500 to-slate-600 text-white ring-slate-500/30',
    };
    return classes[role] || 'bg-gradient-to-r from-blue-500 to-blue-600 text-white ring-blue-500/30';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const isUserActive = (user) => {
    return user.emp_verified_at !== null && user.emp_verified_at !== '';
};

const toggleUserStatus = async (user) => {
    togglingStatus.value[user.id] = true;
    try {
        const response = await axios.patch(`/admin/api/users/${user.id}/toggle-status`);
        showToast(response.data.message || 'User status updated successfully', 'success');
        fetchUsers();
    } catch (error) {
        showToast(error.response?.data?.message || 'Error updating user status', 'danger');
    } finally {
        togglingStatus.value[user.id] = false;
    }
};

const showToast = (message, type = 'info') => {
    const toast = document.createElement('div');
    const bgColors = {
        success: 'bg-green-600',
        danger: 'bg-red-600',
        info: 'bg-blue-600',
        warning: 'bg-amber-600',
    };
    toast.className = `fixed top-4 right-4 ${bgColors[type] || bgColors.info} text-white px-6 py-3 rounded-lg shadow-lg z-[9999]`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
};

// Helper methods for dynamic role cards
const getRoleDisplayName = (roleKey) => {
    const names = {
        admins: 'Admins',
        managers: 'Managers',
        moderators: 'Moderators',
        regular_users: 'Regular Users',
        super_users: 'Super Users',
        'super-users': 'Super Users'
    };
    // Convert underscores and hyphens to spaces and capitalize each word
    return names[roleKey] || roleKey.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const getRoleCardClass = (roleKey) => {
    // Normalize key (handle both underscore and hyphen)
    const normalizedKey = roleKey.replace(/-/g, '_');
    const classes = {
        admins: 'bg-gradient-to-br from-red-50 to-red-100/50 hover:shadow-red-500/10 dark:from-red-950/30 dark:to-red-900/20',
        managers: 'bg-gradient-to-br from-amber-50 to-amber-100/50 hover:shadow-amber-500/10 dark:from-amber-950/30 dark:to-amber-900/20',
        moderators: 'bg-gradient-to-br from-purple-50 to-purple-100/50 hover:shadow-purple-500/10 dark:from-purple-950/30 dark:to-purple-900/20',
        regular_users: 'bg-gradient-to-br from-emerald-50 to-emerald-100/50 hover:shadow-emerald-500/10 dark:from-emerald-950/30 dark:to-emerald-900/20',
        super_users: 'bg-gradient-to-br from-indigo-50 to-indigo-100/50 hover:shadow-indigo-500/10 dark:from-indigo-950/30 dark:to-indigo-900/20'
    };
    return classes[normalizedKey] || 'bg-gradient-to-br from-slate-50 to-slate-100/50 hover:shadow-slate-500/10 dark:from-slate-950/30 dark:to-slate-900/20';
};

const getRoleTextClass = (roleKey) => {
    const normalizedKey = roleKey.replace(/-/g, '_');
    const classes = {
        admins: 'text-red-700 dark:text-red-300',
        managers: 'text-amber-700 dark:text-amber-300',
        moderators: 'text-purple-700 dark:text-purple-300',
        regular_users: 'text-emerald-700 dark:text-emerald-300',
        super_users: 'text-indigo-700 dark:text-indigo-300'
    };
    return classes[normalizedKey] || 'text-slate-700 dark:text-slate-300';
};

const getRoleBoldTextClass = (roleKey) => {
    const normalizedKey = roleKey.replace(/-/g, '_');
    const classes = {
        admins: 'text-red-900 dark:text-red-100',
        managers: 'text-amber-900 dark:text-amber-100',
        moderators: 'text-purple-900 dark:text-purple-100',
        regular_users: 'text-emerald-900 dark:text-emerald-100',
        super_users: 'text-indigo-900 dark:text-indigo-100'
    };
    return classes[normalizedKey] || 'text-slate-900 dark:text-slate-100';
};

const getRoleIconClass = (roleKey) => {
    const normalizedKey = roleKey.replace(/-/g, '_');
    const classes = {
        admins: 'bg-red-500/10 ring-red-500/20 group-hover:bg-red-500/20 group-hover:ring-red-500/30',
        managers: 'bg-amber-500/10 ring-amber-500/20 group-hover:bg-amber-500/20 group-hover:ring-amber-500/30',
        moderators: 'bg-purple-500/10 ring-purple-500/20 group-hover:bg-purple-500/20 group-hover:ring-purple-500/30',
        regular_users: 'bg-emerald-500/10 ring-emerald-500/20 group-hover:bg-emerald-500/20 group-hover:ring-emerald-500/30',
        super_users: 'bg-indigo-500/10 ring-indigo-500/20 group-hover:bg-indigo-500/20 group-hover:ring-indigo-500/30'
    };
    return classes[normalizedKey] || 'bg-slate-500/10 ring-slate-500/20 group-hover:bg-slate-500/20 group-hover:ring-slate-500/30';
};

const getRoleIconColorClass = (roleKey) => {
    const normalizedKey = roleKey.replace(/-/g, '_');
    const classes = {
        admins: 'text-red-600 dark:text-red-400',
        managers: 'text-amber-600 dark:text-amber-400',
        moderators: 'text-purple-600 dark:text-purple-400',
        regular_users: 'text-emerald-600 dark:text-emerald-400',
        super_users: 'text-indigo-600 dark:text-indigo-400'
    };
    return classes[normalizedKey] || 'text-slate-600 dark:text-slate-400';
};

const getRoleAccentClass = (roleKey) => {
    const normalizedKey = roleKey.replace(/-/g, '_');
    const classes = {
        admins: 'bg-red-500/5',
        managers: 'bg-amber-500/5',
        moderators: 'bg-purple-500/5',
        regular_users: 'bg-emerald-500/5',
        super_users: 'bg-indigo-500/5'
    };
    return classes[normalizedKey] || 'bg-slate-500/5';
};

const getRoleIcon = (roleKey) => {
    const normalizedKey = roleKey.replace(/-/g, '_');
    const icons = {
        admins: Shield,
        managers: UserCheck,
        moderators: UserCheck,
        regular_users: User,
        super_users: Shield
    };
    return icons[normalizedKey] || User;
};
</script>

<style scoped>
/* Minimal custom styles - Tailwind handles most styling */
</style>
