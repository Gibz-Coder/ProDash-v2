<template>
    <AppLayout>
        <template #filters>
            <div class="flex items-center gap-3">
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >DATE:</span
                    >
                    <input
                        v-model="filterDate"
                        type="date"
                        @change="fetchRecords"
                        class="cursor-pointer border-0 bg-transparent text-xs font-semibold text-foreground focus:ring-0 focus:outline-none"
                    />
                </div>
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >SHIFT:</span
                    >
                    <select
                        v-model="filterShift"
                        @change="fetchRecords"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
                    >
                        <option value="">ALL</option>
                        <option value="DAY">Day</option>
                        <option value="NIGHT">Night</option>
                    </select>
                </div>
                <!-- Cutoff Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >CUTOFF:</span
                    >
                    <select
                        v-model="filterCutoff"
                        @change="fetchRecords"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="">ALL</option>
                        <option value="00:00~03:59">00:00~03:59</option>
                        <option value="04:00~06:59">04:00~06:59</option>
                        <option value="07:00~11:59">07:00~11:59</option>
                        <option value="12:00~15:59">12:00~15:59</option>
                        <option value="16:00~18:59">16:00~18:59</option>
                        <option value="19:00~23:59">19:00~23:59</option>
                    </select>
                </div>
                <!-- Worktype Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >WORKTYPE:</span
                    >
                    <select
                        v-model="filterWorktype"
                        @change="fetchRecords"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="">ALL</option>
                        <option value="NORMAL">NORMAL</option>
                        <option value="PROCESS RW">PROCESS RW</option>
                        <option value="WH REWORK">WH REWORK</option>
                        <option value="OI REWORK">OI REWORK</option>
                    </select>
                </div>
                <!-- LIPAS Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >LIPAS:</span
                    >
                    <select
                        v-model="filterLipas"
                        @change="fetchRecords"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
                    >
                        <option value="">ALL</option>
                        <option value="Y">Yes</option>
                        <option value="N">No</option>
                    </select>
                </div>
                <!-- Unit Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >UNIT:</span
                    >
                    <select
                        :value="filterUnit"
                        @change="
                            setUnit(
                                ($event.target as HTMLSelectElement).value as
                                    | 'pcs'
                                    | 'Kpcs'
                                    | 'Mpcs',
                            )
                        "
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
                    >
                        <option value="pcs">pcs</option>
                        <option value="Kpcs">Kpcs</option>
                        <option value="Mpcs">Mpcs</option>
                    </select>
                </div>
                <button
                    class="flex items-center gap-1 rounded-lg border border-blue-600 bg-transparent px-3 py-1.5 text-xs font-medium text-blue-600 shadow-sm transition-colors hover:bg-blue-600 hover:text-white"
                    @click="fetchRecords"
                >
                    <RefreshCw class="h-3.5 w-3.5" /> Refresh
                </button>
                <!-- Export -->
                <div class="export-picker-wrapper relative">
                    <button
                        class="flex items-center gap-1 rounded-lg border border-emerald-600 bg-transparent px-3 py-1.5 text-xs font-medium text-emerald-600 shadow-sm transition-colors hover:bg-emerald-600 hover:text-white"
                        @click="showExportPicker = !showExportPicker"
                    >
                        <Download class="h-3.5 w-3.5" /> Export
                    </button>
                    <div
                        v-if="showExportPicker"
                        class="absolute top-full right-0 z-50 mt-1 w-64 rounded-lg border border-border bg-card p-4 shadow-xl"
                        @click.stop
                    >
                        <p class="mb-3 text-xs font-semibold text-foreground">
                            Export Date Range
                        </p>
                        <div class="space-y-2">
                            <div>
                                <label
                                    class="mb-1 block text-xs text-muted-foreground"
                                    >From</label
                                >
                                <input
                                    v-model="exportDateFrom"
                                    type="date"
                                    class="h-8 w-full rounded border border-input bg-background px-2 text-xs text-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                />
                            </div>
                            <div>
                                <label
                                    class="mb-1 block text-xs text-muted-foreground"
                                    >To</label
                                >
                                <input
                                    v-model="exportDateTo"
                                    type="date"
                                    class="h-8 w-full rounded border border-input bg-background px-2 text-xs text-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                                />
                            </div>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button
                                class="flex-1 rounded border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted"
                                @click="showExportPicker = false"
                            >
                                Cancel
                            </button>
                            <button
                                class="flex-1 rounded bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700"
                                @click="triggerExport"
                            >
                                <Download class="mr-1 inline h-3 w-3" />
                                Download
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Title row -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl leading-tight font-bold">
                        QC Analysis Monitoring
                    </h1>
                    <p class="text-[11px] text-muted-foreground">
                        Lots pending QC analysis
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative">
                        <input
                            v-model="tableSearch"
                            type="text"
                            placeholder="Search lot, model..."
                            class="h-8 w-56 rounded-lg border border-border bg-background pr-3 pl-8 text-xs text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        />
                        <Search
                            class="absolute top-1/2 left-2.5 h-3.5 w-3.5 -translate-y-1/2 text-muted-foreground"
                        />
                    </div>
                    <button
                        class="flex h-8 items-center gap-1.5 rounded-lg bg-primary px-3 text-xs font-medium text-primary-foreground hover:bg-primary/90"
                        @click="openAddModal"
                    >
                        <Plus class="h-3.5 w-3.5" /> Update Status
                    </button>
                </div>
            </div>

            <!-- Summary cards row -->
            <div class="flex gap-2">
                <!-- Total -->
                <div
                    class="flex flex-1 items-center gap-2 rounded-lg border border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 px-3 py-2 shadow dark:from-blue-950/30 dark:to-blue-900/20"
                >
                    <div
                        class="rounded-full bg-blue-500/10 p-1.5 ring-1 ring-blue-500/20"
                    >
                        <Package
                            class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-blue-700 uppercase dark:text-blue-300"
                        >
                            Total
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-blue-900 dark:text-blue-100"
                        >
                            {{ summaryStats.totalQty }}
                        </p>
                        <p
                            class="text-[9px] text-blue-600/70 dark:text-blue-400/70"
                        >
                            {{ summaryStats.total }} lots
                        </p>
                    </div>
                </div>
                <!-- Pending -->
                <div
                    class="flex flex-1 items-center gap-2 rounded-lg border border-border/50 bg-gradient-to-br from-slate-50 to-slate-100/50 px-3 py-2 shadow dark:from-slate-950/30 dark:to-slate-900/20"
                >
                    <div
                        class="rounded-full bg-slate-500/10 p-1.5 ring-1 ring-slate-500/20"
                    >
                        <Clock class="h-3.5 w-3.5 text-slate-500" />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-slate-600 uppercase dark:text-slate-400"
                        >
                            Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-slate-800 dark:text-slate-100"
                        >
                            {{ summaryStats.pendingQty }}
                        </p>
                        <p class="text-[9px] text-slate-500/70">
                            {{ summaryStats.pending }} lots
                        </p>
                    </div>
                </div>
                <!-- In Progress -->
                <div
                    class="flex flex-1 items-center gap-2 rounded-lg border border-border/50 bg-gradient-to-br from-amber-50 to-amber-100/50 px-3 py-2 shadow dark:from-amber-950/30 dark:to-amber-900/20"
                >
                    <div
                        class="rounded-full bg-amber-500/10 p-1.5 ring-1 ring-amber-500/20"
                    >
                        <Loader2
                            class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-amber-700 uppercase dark:text-amber-300"
                        >
                            In Progress
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-amber-900 dark:text-amber-100"
                        >
                            {{ summaryStats.inProgressQty }}
                        </p>
                        <p
                            class="text-[9px] text-amber-600/70 dark:text-amber-400/70"
                        >
                            {{ summaryStats.inProgress }} lots
                        </p>
                    </div>
                </div>
                <!-- Completed -->
                <div
                    class="flex flex-1 items-center gap-2 rounded-lg border border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 px-3 py-2 shadow dark:from-emerald-950/30 dark:to-emerald-900/20"
                >
                    <div
                        class="rounded-full bg-emerald-500/10 p-1.5 ring-1 ring-emerald-500/20"
                    >
                        <CheckCircle2
                            class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-emerald-700 uppercase dark:text-emerald-300"
                        >
                            Completed
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-emerald-900 dark:text-emerald-100"
                        >
                            {{ summaryStats.completedQty }}
                        </p>
                        <p
                            class="text-[9px] text-emerald-600/70 dark:text-emerald-400/70"
                        >
                            {{ summaryStats.completed }} lots
                        </p>
                    </div>
                </div>
                <!-- Prev Day -->
                <div
                    class="flex flex-1 items-center gap-2 rounded-lg border border-border/50 bg-gradient-to-br from-rose-50 to-rose-100/50 px-3 py-2 shadow dark:from-rose-950/30 dark:to-rose-900/20"
                >
                    <div
                        class="rounded-full bg-rose-500/10 p-1.5 ring-1 ring-rose-500/20"
                    >
                        <AlertCircle
                            class="h-3.5 w-3.5 text-rose-600 dark:text-rose-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-rose-700 uppercase dark:text-rose-300"
                        >
                            Prev Day
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-rose-900 dark:text-rose-100"
                        >
                            {{ summaryStats.prevDayQty }}
                        </p>
                        <p
                            class="text-[9px] text-rose-600/70 dark:text-rose-400/70"
                        >
                            {{ summaryStats.prevDayPending }} lots
                        </p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid gap-4 md:grid-cols-4 md:grid-rows-1">
                <div
                    class="relative h-[360px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div
                        class="mb-3 flex justify-center border-b border-sidebar-border/50 pb-2"
                    >
                        <h3 class="text-sm font-bold text-foreground">
                            Summary of QC Analysis Delay
                        </h3>
                    </div>
                    <div id="qc-pie-chart" class="h-[300px] w-full"></div>
                </div>
                <div
                    class="relative h-[360px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex justify-center">
                        <h3 class="text-sm font-bold text-foreground">
                            Per Size QC Analysis Delay
                        </h3>
                    </div>
                    <div id="qc-bar-chart" class="h-[300px] w-full"></div>
                </div>
                <div
                    class="relative h-[360px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 md:col-span-2 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-foreground">
                            Detailed QC Analysis Delay
                        </h3>
                        <div
                            class="inline-flex rounded-md shadow-sm"
                            role="group"
                        >
                            <button
                                type="button"
                                :class="[
                                    'rounded-l-md border px-2 py-1 text-xs font-medium transition-colors focus:z-10',
                                    activeWorkType === 'All'
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-500 dark:text-blue-500',
                                ]"
                                @click="setWorkType('All')"
                            >
                                All
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'border border-x-0 px-2 py-1 text-xs font-medium transition-colors focus:z-10',
                                    activeWorkType === 'Mainlot'
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-500 dark:text-blue-500',
                                ]"
                                @click="setWorkType('Mainlot')"
                            >
                                Mainlot
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'border border-x-0 px-2 py-1 text-xs font-medium transition-colors focus:z-10',
                                    activeWorkType === 'R-rework'
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-500 dark:text-blue-500',
                                ]"
                                @click="setWorkType('R-rework')"
                            >
                                R-rework
                            </button>
                            <button
                                type="button"
                                :class="[
                                    'rounded-r-md border px-2 py-1 text-xs font-medium transition-colors focus:z-10',
                                    activeWorkType === 'L-rework'
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-500 dark:text-blue-500',
                                ]"
                                @click="setWorkType('L-rework')"
                            >
                                L-rework
                            </button>
                        </div>
                    </div>
                    <div id="qc-column-chart" class="h-[300px] w-full"></div>
                </div>
            </div>

            <!-- Table -->
            <div
                class="overflow-hidden rounded-xl border border-border/50 bg-card shadow-lg"
            >
                <div
                    v-if="error"
                    class="flex items-center gap-2 bg-red-50 px-4 py-3 text-xs text-red-700 dark:bg-red-950/30 dark:text-red-400"
                >
                    <span class="font-semibold">Error:</span> {{ error }}
                </div>
                <div v-if="loading" class="flex justify-center py-10">
                    <div
                        class="h-7 w-7 animate-spin rounded-full border-4 border-primary border-r-transparent"
                        role="status"
                    >
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div v-else class="max-h-[600px] overflow-auto">
                    <table class="w-full min-w-[900px] table-fixed text-xs">
                        <colgroup>
                            <col class="w-[40px]" />
                            <col class="w-[110px]" />
                            <col class="w-[140px]" />
                            <col class="w-[90px]" />
                            <col class="w-[60px]" />
                            <col class="w-[150px]" />
                            <col class="w-[110px]" />
                            <col class="w-[100px]" />
                            <col class="w-[90px]" />
                            <col class="w-[110px]" />
                            <col class="w-[150px]" />
                        </colgroup>
                        <thead class="sticky top-0 z-10">
                            <tr
                                class="bg-gradient-to-r from-slate-700 to-slate-800 dark:from-slate-800 dark:to-slate-900"
                            >
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-center text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    No
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Lot No
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Model
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Qty
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    LIPAS
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Date Time
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Defect Code
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Status
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Elapsed
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Updated By
                                </th>
                                <th
                                    class="px-2 py-2.5 text-center text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr
                                v-for="(rec, index) in filteredRecords"
                                :key="rec.id"
                                class="cursor-pointer transition-colors"
                                :class="rowClass(rec)"
                                @click="selectedId = rec.id"
                            >
                                <td
                                    class="px-2 py-2 text-center text-xs text-muted-foreground"
                                >
                                    {{ index + 1 }}
                                </td>
                                <td
                                    class="px-2 py-2 font-mono text-xs font-semibold text-primary"
                                >
                                    {{ rec.lot_id }}
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs text-foreground"
                                    :title="rec.model ?? ''"
                                >
                                    {{ rec.model || '—' }}
                                </td>
                                <td
                                    class="px-2 py-2 text-xs font-medium text-foreground"
                                >
                                    {{ formatQty(rec.lot_qty ?? 0) }}
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        v-if="rec.lipas_yn"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            rec.lipas_yn === 'Y'
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                : 'bg-slate-50 text-slate-600 ring-slate-600/20'
                                        "
                                    >
                                        {{ rec.lipas_yn }}
                                    </span>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="px-2 py-2 text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDateTime(rec.created_at) }}
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs text-foreground"
                                    :title="rec.qc_defect ?? ''"
                                >
                                    {{ rec.qc_defect || '—' }}
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="statusBadgeClass(rec)"
                                    >
                                        {{ statusLabel(rec) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-xs whitespace-nowrap">
                                    <span
                                        v-if="rec.qc_ana_result"
                                        class="text-muted-foreground"
                                        >{{ rec.qc_ana_tat ?? '—' }} min</span
                                    >
                                    <ElapsedCell
                                        v-else-if="rec.qc_ana_start"
                                        :start="rec.qc_ana_start"
                                    />
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs text-muted-foreground"
                                    :title="rec.updated_by ?? ''"
                                >
                                    {{ rec.updated_by || '—' }}
                                </td>
                                <td class="px-2 py-2">
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <button
                                            v-if="!rec.qc_ana_start"
                                            class="h-7 rounded border border-blue-500/30 bg-blue-500/10 px-3 text-[10px] font-semibold text-blue-600 hover:bg-blue-500/20 disabled:opacity-50"
                                            :disabled="starting === rec.id"
                                            @click.stop="startAnalysis(rec)"
                                        >
                                            <Play
                                                class="mr-1 inline h-3 w-3"
                                            />Start
                                        </button>
                                        <button
                                            v-else-if="!rec.qc_ana_result"
                                            class="h-7 rounded border border-primary/30 bg-primary/10 px-3 text-[10px] font-semibold text-primary hover:bg-primary/20"
                                            @click.stop="openModal(rec)"
                                        >
                                            <ClipboardCheck
                                                class="mr-1 inline h-3 w-3"
                                            />Submit
                                        </button>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600"
                                        >
                                            <CheckCircle2 class="h-3.5 w-3.5" />
                                            {{ rec.qc_ana_result }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRecords.length === 0">
                                <td
                                    colspan="11"
                                    class="py-12 text-center text-muted-foreground"
                                >
                                    <CheckCircle2
                                        class="mx-auto h-12 w-12 opacity-20"
                                    />
                                    <p class="mt-2 text-xs">
                                        No lots pending QC analysis
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <MonitorResultModal
            :open="showModal"
            :lot="modalLot"
            mode="qc"
            @update:open="showModal = $event"
            @submitted="fetchRecords"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { useElapsedTimer } from '@/composables/useElapsedTimer';
import { useEndlineCharts } from '@/composables/useEndlineCharts';
import {
    useMonitorPage,
    type MonitorRecord,
} from '@/composables/useMonitorPage';
import AppLayout from '@/layouts/AppLayout.vue';
import MonitorResultModal from '@/pages/dashboards/subs/monitor-result-modal.vue';
import axios from 'axios';
import {
    AlertCircle,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    Download,
    Loader2,
    Package,
    Play,
    Plus,
    RefreshCw,
    Search,
} from 'lucide-vue-next';
import {
    computed,
    defineComponent,
    h,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

// Inline elapsed cell component to get per-row reactive timers
const ElapsedCell = defineComponent({
    props: { start: { type: String, required: true } },
    setup(props) {
        const startRef = ref(props.start);
        const { elapsedMinutes } = useElapsedTimer(startRef);
        return () =>
            h(
                'span',
                { class: 'font-medium text-amber-600 dark:text-amber-400' },
                `${elapsedMinutes.value} min`,
            );
    },
});

const filterDate = ref(
    new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' }),
);
const filterShift = ref('');
const filterCutoff = ref('');
const filterWorktype = ref('');
const filterLipas = ref('');
const filterUnit = ref<'pcs' | 'Kpcs' | 'Mpcs'>(
    (localStorage.getItem('endline_unit') as 'pcs' | 'Kpcs' | 'Mpcs') ?? 'Kpcs',
);

function setUnit(u: 'pcs' | 'Kpcs' | 'Mpcs') {
    filterUnit.value = u;
    localStorage.setItem('endline_unit', u);
}

function formatQty(qty: number): string {
    if (filterUnit.value === 'Kpcs')
        return (qty / 1000).toLocaleString(undefined, {
            maximumFractionDigits: 1,
        });
    if (filterUnit.value === 'Mpcs')
        return (qty / 1_000_000).toLocaleString(undefined, {
            maximumFractionDigits: 2,
        });
    return qty.toLocaleString();
}

const { records, loading, error, fetchRecords, summaryStats } = useMonitorPage({
    apiUrl: '/api/endline-delay/qc-monitor',
    mode: 'qc',
    unit: filterUnit,
    params: () => ({
        date: filterDate.value || undefined,
        shift: filterShift.value || undefined,
        cutoff: filterCutoff.value || undefined,
        work_type: filterWorktype.value || undefined,
        lipas_yn: filterLipas.value || undefined,
    }),
});

const selectedId = ref<number | null>(null);
const starting = ref<number | null>(null);
const showModal = ref(false);
const modalLot = ref<MonitorRecord | null>(null);

function rowClass(rec: MonitorRecord) {
    if (rec.qc_ana_result)
        return 'bg-emerald-50/40 hover:bg-emerald-50/60 dark:bg-emerald-950/10';
    if (rec.qc_ana_start)
        return 'bg-amber-50/60 hover:bg-amber-50/80 dark:bg-amber-950/20';
    return 'hover:bg-muted/30';
}

function statusLabel(rec: MonitorRecord) {
    if (rec.qc_ana_result) return 'Done';
    if (rec.qc_ana_start) return 'In Progress';
    return 'Pending';
}

function statusBadgeClass(rec: MonitorRecord) {
    if (rec.qc_ana_result)
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (rec.qc_ana_start)
        return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400';
    return 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-950/30 dark:text-slate-400';
}

function formatDateTime(dt: string | null) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function startAnalysis(rec: MonitorRecord) {
    starting.value = rec.id;
    try {
        await axios.post(`/api/endline-delay/${rec.id}/start-qc`);
        await fetchRecords();
    } finally {
        starting.value = null;
    }
}

function openModal(rec: MonitorRecord) {
    modalLot.value = rec;
    showModal.value = true;
}

const showExportPicker = ref(false);
const tableSearch = ref('');

const filteredRecords = computed(() => {
    const q = tableSearch.value.trim().toLowerCase();
    if (!q) return records.value;
    return records.value.filter(
        (r) =>
            r.lot_id?.toLowerCase().includes(q) ||
            r.model?.toLowerCase().includes(q) ||
            (r as any).defect_name?.toLowerCase().includes(q),
    );
});

function openAddModal() {
    // placeholder — wire to your add-entry modal when ready
}

const today = new Date().toLocaleDateString('en-CA', {
    timeZone: 'Asia/Manila',
});
const exportDateFrom = ref(today);
const exportDateTo = ref(today);

function triggerExport() {
    const p = new URLSearchParams();
    if (exportDateFrom.value) p.set('date_from', exportDateFrom.value);
    if (exportDateTo.value) p.set('date_to', exportDateTo.value);
    p.set('defect_class', 'QC Analysis');
    window.location.href = `/api/endline-delay/export?${p.toString()}`;
    showExportPicker.value = false;
}

function onKeydown(e: KeyboardEvent) {
    if (e.key !== 'F2') return;
    e.preventDefault();
    const target = selectedId.value
        ? records.value.find((r) => r.id === selectedId.value)
        : records.value.find((r) => r.qc_ana_start && !r.qc_ana_result);
    if (target && target.qc_ana_start && !target.qc_ana_result)
        openModal(target);
}

const {
    activeWorkType,
    activeCategory,
    setWorkType,
    setCategory,
    fetchChartData,
    initCharts,
    destroyCharts,
} = useEndlineCharts({
    chartIdPrefix: 'qc',
    defaultCategory: 'QC Analysis',
    getParams: () => ({
        date: filterDate.value || undefined,
        shift: filterShift.value || undefined,
        cutoff: filterCutoff.value || undefined,
        work_type: filterWorktype.value || undefined,
        lipas_yn: filterLipas.value || undefined,
    }),
});

// Re-fetch charts whenever any page filter changes (Requirements 8.1, 8.3)
watch(
    [filterDate, filterShift, filterCutoff, filterWorktype, filterLipas],
    () => fetchChartData(),
);

onMounted(() => {
    fetchRecords();
    initCharts();
    fetchChartData();
    document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    destroyCharts();
    document.removeEventListener('keydown', onKeydown);
});
</script>
