<template>
    <AppLayout>
        <template #header>
            <h2 class="flex items-center text-xl font-semibold">
                <Settings class="mr-2 h-5 w-5" />System Settings
            </h2>
        </template>

        <div class="container mx-auto px-4 py-6">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-foreground">
                    System Settings
                </h1>
                <p class="mt-2 text-lg text-muted-foreground">
                    Manage application settings and maintenance tasks
                </p>
            </div>

            <!-- Maintenance Mode Section -->
            <div class="mb-8">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-orange-500/10"
                >
                    <div
                        class="border-b border-border/50 bg-gradient-to-r from-orange-50 to-orange-100/50 px-6 py-5 dark:from-orange-950/30 dark:to-orange-900/20"
                    >
                        <h3
                            class="flex items-center text-xl font-bold text-orange-900 dark:text-orange-100"
                        >
                            <div
                                class="mr-3 rounded-full bg-orange-500/10 p-2 ring-1 ring-orange-500/20"
                            >
                                <Wrench
                                    class="h-6 w-6 text-orange-600 dark:text-orange-400"
                                />
                            </div>
                            Maintenance Mode
                        </h3>
                    </div>
                    <div class="p-6">
                        <div
                            class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="flex-1">
                                <p
                                    class="mb-2 text-base font-medium text-foreground"
                                >
                                    Enable maintenance mode to prevent user
                                    access during updates
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Administrators will still have access to the
                                    system
                                </p>
                            </div>
                            <div class="flex items-center gap-4">
                                <label
                                    class="relative inline-flex cursor-pointer items-center"
                                >
                                    <input
                                        type="checkbox"
                                        id="maintenanceToggle"
                                        v-model="settings.maintenance_mode"
                                        @change="toggleMaintenance"
                                        :disabled="loading.maintenance"
                                        class="peer sr-only"
                                    />
                                    <div
                                        class="peer h-7 w-12 rounded-full bg-gray-200 shadow-inner peer-checked:bg-gradient-to-r peer-checked:from-emerald-500 peer-checked:to-emerald-600 peer-focus:ring-4 peer-focus:ring-emerald-300/50 peer-focus:outline-none peer-disabled:cursor-not-allowed peer-disabled:opacity-50 after:absolute after:top-[3px] after:left-[3px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:shadow-sm after:transition-all after:content-[''] peer-checked:after:translate-x-full peer-checked:after:border-white dark:border-gray-600 dark:bg-gray-700"
                                    ></div>
                                    <span
                                        class="ml-4 text-sm font-semibold text-foreground"
                                    >
                                        {{
                                            settings.maintenance_mode
                                                ? 'Enabled'
                                                : 'Disabled'
                                        }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div
                        class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-orange-500/5"
                    ></div>
                </div>
            </div>

            <!-- Cache Management Section -->
            <div class="mb-8">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-yellow-500/10"
                >
                    <div
                        class="border-b border-border/50 bg-gradient-to-r from-yellow-50 to-yellow-100/50 px-6 py-5 dark:from-yellow-950/30 dark:to-yellow-900/20"
                    >
                        <h3
                            class="flex items-center text-xl font-bold text-yellow-900 dark:text-yellow-100"
                        >
                            <div
                                class="mr-3 rounded-full bg-yellow-500/10 p-2 ring-1 ring-yellow-500/20"
                            >
                                <Zap
                                    class="h-6 w-6 text-yellow-600 dark:text-yellow-400"
                                />
                            </div>
                            Cache Management
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-6 text-base text-muted-foreground">
                            Clear application caches to ensure fresh data and
                            optimal performance
                        </p>
                        <button
                            class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-primary/90 hover:to-primary hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50"
                            @click="clearCache"
                            :disabled="loading.cache"
                        >
                            <span
                                v-if="loading.cache"
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                            ></span>
                            <Trash2 v-else class="mr-2 h-4 w-4" />
                            Clear All Caches
                        </button>
                        <p
                            v-if="settings.last_cache_clear"
                            class="mt-4 text-sm font-medium text-muted-foreground"
                        >
                            Last cleared:
                            {{ formatDate(settings.last_cache_clear) }}
                        </p>
                    </div>
                    <div
                        class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-yellow-500/5"
                    ></div>
                </div>
            </div>

            <!-- Optimization Section -->
            <div class="mb-8">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-emerald-500/10"
                >
                    <div
                        class="border-b border-border/50 bg-gradient-to-r from-emerald-50 to-emerald-100/50 px-6 py-5 dark:from-emerald-950/30 dark:to-emerald-900/20"
                    >
                        <h3
                            class="flex items-center text-xl font-bold text-emerald-900 dark:text-emerald-100"
                        >
                            <div
                                class="mr-3 rounded-full bg-emerald-500/10 p-2 ring-1 ring-emerald-500/20"
                            >
                                <Rocket
                                    class="h-6 w-6 text-emerald-600 dark:text-emerald-400"
                                />
                            </div>
                            Optimization
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-6 text-base text-muted-foreground">
                            Clear all optimization caches including routes,
                            config, and views
                        </p>
                        <button
                            class="inline-flex items-center rounded-lg bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-emerald-700 hover:to-emerald-800 hover:shadow-xl hover:shadow-emerald-500/25 disabled:pointer-events-none disabled:opacity-50"
                            @click="optimize"
                            :disabled="loading.optimize"
                        >
                            <span
                                v-if="loading.optimize"
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                            ></span>
                            <Rocket v-else class="mr-2 h-4 w-4" />
                            Clear Optimization Cache
                        </button>
                        <p
                            v-if="settings.last_optimization"
                            class="mt-4 text-sm font-medium text-muted-foreground"
                        >
                            Last optimized:
                            {{ formatDate(settings.last_optimization) }}
                        </p>
                    </div>
                    <div
                        class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-emerald-500/5"
                    ></div>
                </div>
            </div>

            <!-- Log Management Section -->
            <div class="mb-8">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/10"
                >
                    <div
                        class="border-b border-border/50 bg-gradient-to-r from-blue-50 to-blue-100/50 px-6 py-5 dark:from-blue-950/30 dark:to-blue-900/20"
                    >
                        <h3
                            class="flex items-center text-xl font-bold text-blue-900 dark:text-blue-100"
                        >
                            <div
                                class="mr-3 rounded-full bg-blue-500/10 p-2 ring-1 ring-blue-500/20"
                            >
                                <FileText
                                    class="h-6 w-6 text-blue-600 dark:text-blue-400"
                                />
                            </div>
                            Log Management
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-6 text-base text-muted-foreground">
                            View and manage application log files for debugging
                            and monitoring
                        </p>
                        <div class="mb-6 flex flex-col gap-3 sm:flex-row">
                            <button
                                class="inline-flex items-center rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-blue-700 hover:to-blue-800 hover:shadow-xl hover:shadow-blue-500/25 disabled:pointer-events-none disabled:opacity-50"
                                @click="viewLogs"
                                :disabled="loading.logs"
                            >
                                <span
                                    v-if="loading.logs"
                                    class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                                ></span>
                                <Eye v-else class="mr-2 h-4 w-4" />
                                View Logs
                            </button>
                            <button
                                class="inline-flex items-center rounded-lg bg-gradient-to-r from-destructive to-destructive/90 px-6 py-3 text-sm font-semibold text-destructive-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-destructive/90 hover:to-destructive hover:shadow-xl hover:shadow-destructive/25 disabled:pointer-events-none disabled:opacity-50"
                                @click="confirmClearLogs"
                                :disabled="loading.clearLogs"
                            >
                                <span
                                    v-if="loading.clearLogs"
                                    class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                                ></span>
                                <Trash2 v-else class="mr-2 h-4 w-4" />
                                Clear Logs
                            </button>
                        </div>
                        <p
                            v-if="settings.log_size"
                            class="text-sm font-medium text-muted-foreground"
                        >
                            Total log size:
                            <span class="font-semibold text-foreground">{{
                                settings.log_size
                            }}</span>
                        </p>
                    </div>
                    <div
                        class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-blue-500/5"
                    ></div>
                </div>
            </div>

            <!-- Session Timeout Section -->
            <div class="mb-8">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-purple-500/10"
                >
                    <div
                        class="border-b border-border/50 bg-gradient-to-r from-purple-50 to-purple-100/50 px-6 py-5 dark:from-purple-950/30 dark:to-purple-900/20"
                    >
                        <h3
                            class="flex items-center text-xl font-bold text-purple-900 dark:text-purple-100"
                        >
                            <div
                                class="mr-3 rounded-full bg-purple-500/10 p-2 ring-1 ring-purple-500/20"
                            >
                                <Clock
                                    class="h-6 w-6 text-purple-600 dark:text-purple-400"
                                />
                            </div>
                            Session Timeout
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-6 text-base text-muted-foreground">
                            Configure user session timeout duration (5-1440
                            minutes)
                        </p>
                        <div
                            class="flex flex-col gap-6 md:flex-row md:items-end"
                        >
                            <div class="flex-1 md:max-w-xs">
                                <label
                                    for="sessionTimeout"
                                    class="mb-3 block text-sm font-semibold text-foreground"
                                    >Timeout (minutes)</label
                                >
                                <input
                                    type="number"
                                    class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                    id="sessionTimeout"
                                    v-model.number="sessionTimeout"
                                    min="5"
                                    max="1440"
                                    :disabled="loading.session"
                                />
                            </div>
                            <div>
                                <button
                                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-primary/90 hover:to-primary hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50"
                                    @click="updateSessionTimeout"
                                    :disabled="loading.session"
                                >
                                    <span
                                        v-if="loading.session"
                                        class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                                    ></span>
                                    <Save v-else class="mr-2 h-4 w-4" />
                                    Save
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-purple-500/5"
                    ></div>
                </div>
            </div>

            <!-- Database Backup Section -->
            <div class="mb-8">
                <div
                    class="group relative overflow-hidden rounded-2xl border border-border/50 bg-card shadow-lg transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/10"
                >
                    <div
                        class="border-b border-border/50 bg-gradient-to-r from-indigo-50 to-indigo-100/50 px-6 py-5 dark:from-indigo-950/30 dark:to-indigo-900/20"
                    >
                        <h3
                            class="flex items-center text-xl font-bold text-indigo-900 dark:text-indigo-100"
                        >
                            <div
                                class="mr-3 rounded-full bg-indigo-500/10 p-2 ring-1 ring-indigo-500/20"
                            >
                                <Database
                                    class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                                />
                            </div>
                            Database Backup
                        </h3>
                    </div>
                    <div class="p-6">
                        <p class="mb-6 text-base text-muted-foreground">
                            Create and manage database backups for data
                            protection
                        </p>
                        <button
                            class="mb-6 inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:from-primary/90 hover:to-primary hover:shadow-xl hover:shadow-primary/25 disabled:pointer-events-none disabled:opacity-50"
                            @click="createBackup"
                            :disabled="loading.backup"
                        >
                            <span
                                v-if="loading.backup"
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                            ></span>
                            <CloudDownload v-else class="mr-2 h-4 w-4" />
                            Create Backup
                        </button>
                        <p
                            v-if="settings.last_backup"
                            class="mb-6 text-sm font-medium text-muted-foreground"
                        >
                            Last backup:
                            <span class="font-semibold text-foreground">{{
                                formatDate(settings.last_backup)
                            }}</span>
                        </p>

                        <!-- Backup List -->
                        <div
                            v-if="backups.length > 0"
                            class="overflow-x-auto rounded-lg border border-border/50"
                        >
                            <table class="w-full text-sm">
                                <thead
                                    class="border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10"
                                >
                                    <tr>
                                        <th
                                            class="px-6 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                        >
                                            Filename
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                        >
                                            Size
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                        >
                                            Created
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                        >
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    <tr
                                        v-for="backup in backups"
                                        :key="backup.name"
                                        class="group transition-all duration-200 hover:bg-muted/30"
                                    >
                                        <td
                                            class="px-6 py-4 font-semibold text-foreground"
                                        >
                                            {{ backup.name }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-muted-foreground"
                                        >
                                            {{ backup.size }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-muted-foreground"
                                        >
                                            {{ backup.created }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <a
                                                    :href="`/admin/api/settings/backup/download/${backup.name}`"
                                                    :class="[
                                                        'inline-flex items-center rounded-lg border border-primary/20 bg-gradient-to-r from-primary/5 to-primary/10 px-3 py-2 text-xs font-semibold text-primary shadow-sm transition-all duration-200 hover:border-primary/30 hover:from-primary/10 hover:to-primary/20 hover:shadow-md hover:shadow-primary/10',
                                                        deletingBackups.has(
                                                            backup.name,
                                                        ) &&
                                                            'pointer-events-none opacity-50',
                                                    ]"
                                                    download
                                                    title="Download backup"
                                                >
                                                    <Download class="h-4 w-4" />
                                                </a>
                                                <button
                                                    @click="
                                                        confirmDeleteBackup(
                                                            backup.name,
                                                        )
                                                    "
                                                    :disabled="
                                                        deletingBackups.has(
                                                            backup.name,
                                                        )
                                                    "
                                                    class="inline-flex items-center rounded-lg border border-destructive/20 bg-gradient-to-r from-destructive/5 to-destructive/10 px-3 py-2 text-xs font-semibold text-destructive shadow-sm transition-all duration-200 hover:border-destructive/30 hover:from-destructive/10 hover:to-destructive/20 hover:shadow-md hover:shadow-destructive/10 disabled:pointer-events-none disabled:opacity-50"
                                                    title="Delete backup"
                                                >
                                                    <span
                                                        v-if="
                                                            deletingBackups.has(
                                                                backup.name,
                                                            )
                                                        "
                                                        class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-current border-r-transparent"
                                                    ></span>
                                                    <Trash2
                                                        v-else
                                                        class="h-4 w-4"
                                                    />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div
                            v-else
                            class="rounded-lg border border-border/50 bg-muted/20 p-6 text-center"
                        >
                            <Database
                                class="mx-auto h-12 w-12 text-muted-foreground/50"
                            />
                            <p
                                class="mt-2 text-sm font-medium text-muted-foreground"
                            >
                                No backups available
                            </p>
                        </div>
                    </div>
                    <div
                        class="absolute -top-6 -right-6 h-20 w-20 rounded-full bg-indigo-500/5"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Log Viewer Modal -->
        <div
            v-if="showLogModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click="showLogModal = false"
        >
            <div
                class="w-full max-w-6xl animate-in rounded-2xl bg-card shadow-2xl ring-1 ring-border/50 duration-300 fade-in-0 zoom-in-95"
                @click.stop
            >
                <div
                    class="flex items-center justify-between rounded-t-2xl border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10 px-6 py-5"
                >
                    <div>
                        <h3 class="text-xl font-bold text-foreground">
                            Application Logs
                        </h3>
                        <p class="mt-1 text-sm text-muted-foreground">
                            View and analyze application log files
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="showLogModal = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-6">
                    <div v-if="logFiles.length > 0" class="mb-6">
                        <label
                            for="logFileSelect"
                            class="mb-3 block text-sm font-semibold text-foreground"
                            >Select Log File</label
                        >
                        <select
                            class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm ring-offset-background transition-all focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            id="logFileSelect"
                            v-model="selectedLogFile"
                            @change="loadLogContent"
                        >
                            <option value="">-- Select a log file --</option>
                            <option
                                v-for="log in logFiles"
                                :key="log.name"
                                :value="log.name"
                            >
                                {{ log.name }} ({{ log.size }})
                            </option>
                        </select>
                    </div>
                    <div
                        v-if="logContent"
                        class="max-h-[500px] overflow-y-auto rounded-lg border border-border/50"
                    >
                        <pre
                            class="bg-gray-900 p-6 text-sm text-gray-100 dark:bg-gray-950"
                            >{{ logContent }}</pre
                        >
                    </div>
                    <div
                        v-else-if="selectedLogFile"
                        class="flex justify-center py-12"
                    >
                        <div
                            class="h-8 w-8 animate-spin rounded-full border-4 border-primary border-r-transparent"
                            role="status"
                        >
                            <span class="sr-only">Loading...</span>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-muted-foreground">
                        <FileText
                            class="mx-auto h-16 w-16 text-muted-foreground/50"
                        />
                        <p class="mt-3 text-base font-medium">
                            Select a log file to view its content
                        </p>
                    </div>
                </div>
                <div
                    class="flex justify-end rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md"
                        @click="showLogModal = false"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click="showConfirmModal = false"
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
                                Confirm Action
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                This action requires confirmation
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="showConfirmModal = false"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <div class="p-6">
                    <div
                        class="rounded-lg border border-destructive/20 bg-destructive/5 p-4"
                    >
                        <p class="text-sm text-foreground">
                            {{ confirmMessage }}
                        </p>
                    </div>
                </div>
                <div
                    class="flex justify-end gap-3 rounded-b-2xl border-t border-border/50 bg-muted/10 px-6 py-5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-border bg-background px-5 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-all hover:bg-muted hover:shadow-md"
                        @click="showConfirmModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-gradient-to-r from-destructive to-destructive/90 px-6 py-2.5 text-sm font-semibold text-destructive-foreground shadow-lg transition-all hover:shadow-xl hover:shadow-destructive/25"
                        @click="executeConfirmedAction"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import {
    Clock,
    CloudDownload,
    Database,
    Download,
    Eye,
    FileText,
    RocketIcon as Rocket,
    Save,
    Settings,
    Trash2,
    Wrench,
    X,
    Zap,
} from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

const settings = ref({
    maintenance_mode: false,
    session_timeout: 120,
    last_cache_clear: null,
    last_optimization: null,
    last_backup: null,
    log_size: '0 B',
});

const loading = ref({
    maintenance: false,
    cache: false,
    optimize: false,
    logs: false,
    clearLogs: false,
    session: false,
    backup: false,
});

const deletingBackups = ref(new Set());

const sessionTimeout = ref(120);
const backups = ref([]);
const logFiles = ref([]);
const selectedLogFile = ref('');
const logContent = ref('');
const confirmMessage = ref('');
const confirmedAction = ref(null);

const showLogModal = ref(false);
const showConfirmModal = ref(false);

onMounted(async () => {
    await loadSettings();
    await loadBackups();
});

const loadSettings = async () => {
    try {
        const response = await axios.get('/admin/api/settings');
        settings.value = response.data;
        sessionTimeout.value = settings.value.session_timeout;
    } catch (error) {
        showToast('Failed to load settings', 'error');
    }
};

const toggleMaintenance = async () => {
    loading.value.maintenance = true;
    try {
        const response = await axios.post('/admin/api/settings/maintenance', {
            enabled: settings.value.maintenance_mode,
        });
        showToast(response.data.message, 'success');
    } catch (error) {
        settings.value.maintenance_mode = !settings.value.maintenance_mode;
        showToast('Failed to toggle maintenance mode', 'error');
    } finally {
        loading.value.maintenance = false;
    }
};

const clearCache = async () => {
    loading.value.cache = true;
    try {
        const response = await axios.post('/admin/api/settings/cache-clear');
        settings.value.last_cache_clear = response.data.last_cache_clear;
        showToast(response.data.message, 'success');
    } catch (error) {
        showToast('Failed to clear cache', 'error');
    } finally {
        loading.value.cache = false;
    }
};

const optimize = async () => {
    loading.value.optimize = true;
    try {
        const response = await axios.post('/admin/api/settings/optimize');
        settings.value.last_optimization = response.data.last_optimization;
        showToast(response.data.message, 'success');
    } catch (error) {
        showToast('Failed to optimize application', 'error');
    } finally {
        loading.value.optimize = false;
    }
};

const viewLogs = async () => {
    loading.value.logs = true;
    try {
        const response = await axios.get('/admin/api/settings/logs');
        logFiles.value = response.data.logs;
        showLogModal.value = true;
    } catch (error) {
        showToast('Failed to load logs', 'error');
    } finally {
        loading.value.logs = false;
    }
};

const loadLogContent = async () => {
    if (!selectedLogFile.value) {
        logContent.value = '';
        return;
    }

    try {
        const response = await axios.post('/admin/api/settings/logs/content', {
            filename: selectedLogFile.value,
        });
        logContent.value = response.data.content;
    } catch (error) {
        showToast('Failed to load log content', 'error');
    }
};

const confirmClearLogs = () => {
    confirmMessage.value =
        'Are you sure you want to clear all log files? This action cannot be undone.';
    confirmedAction.value = clearLogs;
    showConfirmModal.value = true;
};

const clearLogs = async () => {
    loading.value.clearLogs = true;
    showConfirmModal.value = false;
    try {
        const response = await axios.post('/admin/api/settings/logs/clear');
        showToast(response.data.message, 'success');
        await loadSettings();
    } catch (error) {
        showToast('Failed to clear logs', 'error');
    } finally {
        loading.value.clearLogs = false;
    }
};

const updateSessionTimeout = async () => {
    if (sessionTimeout.value < 5 || sessionTimeout.value > 1440) {
        showToast(
            'Session timeout must be between 5 and 1440 minutes',
            'error',
        );
        return;
    }

    loading.value.session = true;
    try {
        const response = await axios.put('/admin/api/settings/session', {
            timeout: sessionTimeout.value,
        });
        settings.value.session_timeout = response.data.session_timeout;
        showToast(response.data.message, 'success');
    } catch (error) {
        showToast('Failed to update session timeout', 'error');
    } finally {
        loading.value.session = false;
    }
};

const createBackup = async () => {
    loading.value.backup = true;
    try {
        const response = await axios.post('/admin/api/settings/backup');
        settings.value.last_backup = response.data.last_backup;
        showToast(response.data.message, 'success');
        await loadBackups();
    } catch (error) {
        showToast('Failed to create backup', 'error');
    } finally {
        loading.value.backup = false;
    }
};

const loadBackups = async () => {
    try {
        const response = await axios.get('/admin/api/settings/backups');
        backups.value = response.data.backups;
    } catch (error) {
        console.error('Failed to load backups', error);
    }
};

const confirmDeleteBackup = (backupName) => {
    confirmMessage.value = `Are you sure you want to delete the backup "${backupName}"? This action cannot be undone.`;
    confirmedAction.value = () => deleteBackup(backupName);
    showConfirmModal.value = true;
};

const deleteBackup = async (backupName) => {
    deletingBackups.value.add(backupName);
    showConfirmModal.value = false;
    try {
        const response = await axios.delete(
            `/admin/api/settings/backup/${backupName}`,
        );
        showToast(response.data.message, 'success');
        await loadBackups();
    } catch (error) {
        showToast('Failed to delete backup', 'error');
    } finally {
        deletingBackups.value.delete(backupName);
    }
};

const executeConfirmedAction = () => {
    if (confirmedAction.value) {
        confirmedAction.value();
        confirmedAction.value = null;
    }
};

const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    return date.toLocaleString();
};

const showToast = (message, type = 'info') => {
    // Simple toast implementation - you can enhance this with a toast library
    alert(message);
};
</script>

<style scoped>
/* Mobile optimizations */
@media (max-width: 640px) {
    .max-h-\[500px\] {
        max-height: 300px;
    }
}
</style>
