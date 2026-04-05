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
                <!-- Auto-refresh control -->
                <AutoRefreshControl
                    :enabled="autoRefreshEnabled"
                    :interval="autoRefreshInterval"
                    :spinning="loading"
                    @toggle="toggleAutoRefresh"
                    @set-interval="setAutoRefreshInterval"
                />
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

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
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
                        <span
                            class="ml-2 rounded bg-white/20 px-1.5 py-0.5 font-mono text-xs"
                            >F2</span
                        >
                    </button>
                </div>
            </div>

            <!-- Summary cards row -->
            <div class="flex gap-2">
                <!-- Total -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 shadow transition-all dark:from-blue-950/30 dark:to-blue-900/20"
                    :class="
                        statusFilter === null
                            ? 'border-blue-500 bg-blue-100 ring-2 ring-blue-400 dark:bg-blue-950/50'
                            : 'border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 hover:ring-1 hover:ring-blue-300'
                    "
                    @click="toggleStatusFilter('all' as any)"
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
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 shadow transition-all dark:from-red-950/30 dark:to-red-900/20"
                    :class="
                        statusFilter === 'pending'
                            ? 'border-red-500 bg-red-100 ring-2 ring-red-400 dark:bg-red-950/50'
                            : 'border-border/50 bg-gradient-to-br from-red-50 to-red-100/50 hover:ring-1 hover:ring-red-300'
                    "
                    @click="toggleStatusFilter('pending')"
                >
                    <div
                        class="rounded-full bg-red-500/10 p-1.5 ring-1 ring-red-500/20"
                    >
                        <Clock
                            class="h-3.5 w-3.5 text-red-600 dark:text-red-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-red-700 uppercase dark:text-red-300"
                        >
                            Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-red-900 dark:text-red-100"
                        >
                            {{ summaryStats.pendingQty }}
                        </p>
                        <p
                            class="text-[9px] text-red-600/70 dark:text-red-400/70"
                        >
                            {{ summaryStats.pending }} lots
                        </p>
                    </div>
                </div>
                <!-- In Progress -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 shadow transition-all dark:from-amber-950/30 dark:to-amber-900/20"
                    :class="
                        statusFilter === 'inprogress'
                            ? 'border-amber-500 bg-amber-100 ring-2 ring-amber-400 dark:bg-amber-950/50'
                            : 'border-border/50 bg-gradient-to-br from-amber-50 to-amber-100/50 hover:ring-1 hover:ring-amber-300'
                    "
                    @click="toggleStatusFilter('inprogress')"
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
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 shadow transition-all dark:from-emerald-950/30 dark:to-emerald-900/20"
                    :class="
                        statusFilter === 'completed'
                            ? 'border-emerald-500 bg-emerald-100 ring-2 ring-emerald-400 dark:bg-emerald-950/50'
                            : 'border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 hover:ring-1 hover:ring-emerald-300'
                    "
                    @click="toggleStatusFilter('completed')"
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
                <!-- Average TAT -->
                <div
                    class="flex flex-1 items-center gap-2 rounded-lg border border-border/50 bg-gradient-to-br from-violet-50 to-violet-100/50 px-3 py-2 shadow dark:from-violet-950/30 dark:to-violet-900/20"
                >
                    <div
                        class="rounded-full bg-violet-500/10 p-1.5 ring-1 ring-violet-500/20"
                    >
                        <Timer
                            class="h-3.5 w-3.5 text-violet-600 dark:text-violet-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-violet-700 uppercase dark:text-violet-300"
                        >
                            Average TAT
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-violet-900 dark:text-violet-100"
                        >
                            {{ summaryStats.avgTat }}
                        </p>
                        <p
                            class="text-[9px] text-violet-600/70 dark:text-violet-400/70"
                        >
                            {{ summaryStats.total }} lots
                        </p>
                    </div>
                </div>
                <!-- Prev Day -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 shadow transition-all dark:from-rose-950/30 dark:to-rose-900/20"
                    :class="
                        statusFilter === 'prevday'
                            ? 'border-rose-500 bg-rose-100 ring-2 ring-rose-400 dark:bg-rose-950/50'
                            : 'border-border/50 bg-gradient-to-br from-rose-50 to-rose-100/50 hover:ring-1 hover:ring-rose-300'
                    "
                    @click="toggleStatusFilter('prevday')"
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
                            Summary of QC Analysis Lots
                        </h3>
                    </div>
                    <div id="qc-pie-chart" class="h-[300px] w-full"></div>
                </div>
                <div
                    class="relative h-[360px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex justify-center">
                        <h3 class="text-sm font-bold text-foreground">
                            Per Size QC Analysis Lots
                        </h3>
                    </div>
                    <div id="qc-bar-chart" class="h-[300px] w-full"></div>
                </div>
                <div
                    class="relative h-[360px] overflow-hidden rounded-xl border border-sidebar-border/70 p-4 md:col-span-2 dark:border-sidebar-border"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-foreground">
                            Detailed QC Analysis Lots
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
                                    'rounded-r-md border px-2 py-1 text-xs font-medium transition-colors focus:z-10',
                                    activeWorkType === 'RL-Rework'
                                        ? 'border-blue-600 bg-blue-600 text-white'
                                        : 'border-blue-600 bg-transparent text-blue-600 hover:bg-blue-600 hover:text-white dark:border-blue-500 dark:text-blue-500',
                                ]"
                                @click="setWorkType('RL-Rework')"
                            >
                                RL-Rework
                            </button>
                        </div>
                    </div>
                    <div id="qc-column-chart" class="h-[300px] w-full"></div>
                </div>
            </div>

            <!-- Table -->
            <div
                class="flex min-h-0 flex-1 flex-col rounded-xl border border-border/50 bg-card shadow-lg"
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
                <div
                    v-else
                    class="min-h-0 flex-1 overflow-x-auto overflow-y-auto"
                    style="scrollbar-gutter: stable"
                >
                    <table class="w-full min-w-[1100px] table-fixed text-xs">
                        <colgroup>
                            <col class="w-[40px]" />
                            <!-- No -->
                            <col class="w-[90px]" />
                            <!-- Lot No -->
                            <col class="w-[130px]" />
                            <!-- Model -->
                            <col class="w-[75px]" />
                            <!-- Qty -->
                            <col class="w-[50px]" />
                            <!-- LIPAS -->
                            <col class="w-[85px]" />
                            <!-- WorkType -->
                            <col class="w-[120px]" />
                            <!-- Date Time -->
                            <col class="w-[90px]" />
                            <!-- Defect Code -->
                            <col class="w-[80px]" />
                            <!-- Status -->
                            <col class="w-[70px]" />
                            <!-- Elapsed -->
                            <col class="w-[85px]" />
                            <!-- Created By -->
                            <col class="w-[85px]" />
                            <!-- Updated By -->
                            <col class="w-[95px]" />
                            <!-- Decision -->
                            <col class="w-[120px]" />
                            <!-- Completed -->
                            <col class="w-[150px]" />
                            <!-- Remarks -->
                            <col class="w-[90px]" />
                            <!-- Actions -->
                        </colgroup>
                        <thead class="sticky top-0 z-10">
                            <tr
                                class="bg-gradient-to-r from-slate-700 to-slate-800 whitespace-nowrap dark:from-slate-800 dark:to-slate-900"
                            >
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-center text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    No
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('lot_id')"
                                >
                                    Lot No
                                    <span class="opacity-60">{{
                                        sortIcon('lot_id')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('model')"
                                >
                                    Model
                                    <span class="opacity-60">{{
                                        sortIcon('model')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('lot_qty')"
                                >
                                    Qty
                                    <span class="opacity-60">{{
                                        sortIcon('lot_qty')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('lipas_yn')"
                                >
                                    LIPAS
                                    <span class="opacity-60">{{
                                        sortIcon('lipas_yn')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('work_type')"
                                >
                                    WorkType
                                    <span class="opacity-60">{{
                                        sortIcon('work_type')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('analysis_start_at')"
                                >
                                    Date Time
                                    <span class="opacity-60">{{
                                        sortIcon('analysis_start_at')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('defect_code')"
                                >
                                    Defect Code
                                    <span class="opacity-60">{{
                                        sortIcon('defect_code')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('analysis_result')"
                                >
                                    Status
                                    <span class="opacity-60">{{
                                        sortIcon('analysis_result')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('analysis_start_at')"
                                >
                                    Elapsed
                                    <span class="opacity-60">{{
                                        sortIcon('analysis_start_at')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('created_by')"
                                >
                                    Created By
                                    <span class="opacity-60">{{
                                        sortIcon('created_by')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('updated_by')"
                                >
                                    Updated By
                                    <span class="opacity-60">{{
                                        sortIcon('updated_by')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('analysis_result')"
                                >
                                    Decision
                                    <span class="opacity-60">{{
                                        sortIcon('analysis_result')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('analysis_completed_at')"
                                >
                                    Completed
                                    <span class="opacity-60">{{
                                        sortIcon('analysis_completed_at')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('remarks')"
                                >
                                    Remarks
                                    <span class="opacity-60">{{
                                        sortIcon('remarks')
                                    }}</span>
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
                                    class="truncate px-2 py-2 text-xs text-foreground"
                                    :title="rec.work_type ?? ''"
                                >
                                    {{ rec.work_type || '—' }}
                                </td>
                                <td
                                    class="px-2 py-2 text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDateTime(rec.analysis_start_at) }}
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs text-foreground"
                                    :title="rec.defect_code ?? ''"
                                >
                                    {{ rec.defect_code || '—' }}
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
                                        v-if="
                                            rec.analysis_result &&
                                            rec.analysis_completed_at &&
                                            rec.analysis_start_at
                                        "
                                        class="text-muted-foreground"
                                    >
                                        {{
                                            formatDuration(
                                                Math.round(
                                                    (new Date(
                                                        rec.analysis_completed_at,
                                                    ).getTime() -
                                                        new Date(
                                                            rec.analysis_start_at,
                                                        ).getTime()) /
                                                        60_000,
                                                ),
                                            )
                                        }}
                                    </span>
                                    <ElapsedCell
                                        v-else-if="rec.analysis_start_at"
                                        :start="rec.analysis_start_at"
                                    />
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs text-muted-foreground"
                                    :title="rec.created_by ?? ''"
                                >
                                    {{ rec.created_by || '—' }}
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs text-muted-foreground"
                                    :title="rec.updated_by ?? ''"
                                >
                                    {{ rec.updated_by || '—' }}
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        v-if="
                                            rec.analysis_result === 'Rework' ||
                                            rec.analysis_result ===
                                                'DRB Approval'
                                        "
                                        class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-600"
                                    >
                                        <AlertCircle class="h-3 w-3" />{{
                                            rec.analysis_result
                                        }}
                                    </span>
                                    <span
                                        v-else-if="rec.analysis_result"
                                        class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />{{
                                            rec.analysis_result
                                        }}
                                    </span>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <!-- Completed At -->
                                <td
                                    class="px-2 py-2 text-xs whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    <span v-if="rec.analysis_completed_at">
                                        {{
                                            new Date(
                                                rec.analysis_completed_at,
                                            ).toLocaleString('en-US', {
                                                month: 'short',
                                                day: '2-digit',
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })
                                        }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="max-w-[160px] truncate px-2 py-2 text-xs text-muted-foreground"
                                    :title="rec.remarks ?? ''"
                                >
                                    {{ rec.remarks || '—' }}
                                </td>
                                <td class="px-2 py-2">
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <button
                                            v-if="
                                                !rec.analysis_result ||
                                                rec.analysis_result ===
                                                    'In Progress' ||
                                                rec.analysis_result ===
                                                    'For Decision'
                                            "
                                            class="h-7 rounded border border-primary/30 bg-primary/10 px-3 text-[10px] font-semibold text-primary hover:bg-primary/20"
                                            @click.stop="openModal(rec)"
                                        >
                                            <ClipboardCheck
                                                class="mr-1 inline h-3 w-3"
                                            />Update
                                        </button>
                                        <button
                                            v-else
                                            class="h-7 rounded border border-slate-300/50 bg-slate-100/60 px-3 text-[10px] font-semibold text-slate-500 hover:bg-slate-200/60 dark:border-slate-600/50 dark:bg-slate-800/40 dark:text-slate-400 dark:hover:bg-slate-700/40"
                                            @click.stop="openModal(rec, true)"
                                        >
                                            <Eye
                                                class="mr-1 inline h-3 w-3"
                                            />View
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRecords.length === 0">
                                <td
                                    colspan="17"
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

        <QcAnalysisModal
            :open="showModal"
            :lot="modalLot as any"
            :readonly="modalReadonly"
            @update:open="showModal = $event"
            @submitted="fetchRecords"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import AutoRefreshControl from '@/components/AutoRefreshControl.vue';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import { useElapsedTimer } from '@/composables/useElapsedTimer';
import { useEndlineCharts } from '@/composables/useEndlineCharts';
import {
    formatDuration,
    useMonitorPage,
    type MonitorRecord,
} from '@/composables/useMonitorPage';
import { useTableSort } from '@/composables/useTableSort';
import AppLayout from '@/layouts/AppLayout.vue';
import QcAnalysisModal from '@/pages/dashboards/subs/qc-analysis-modal.vue';
import axios from 'axios';
import {
    AlertCircle,
    CheckCircle2,
    ClipboardCheck,
    Clock,
    Download,
    Eye,
    Loader2,
    Package,
    Plus,
    RefreshCw,
    Search,
    Timer,
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
                formatDuration(elapsedMinutes.value),
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

const statusFilter = ref<
    'pending' | 'inprogress' | 'completed' | 'prevday' | null
>(null);

const { records, loading, error, fetchRecords, summaryStats } = useMonitorPage({
    apiUrl: '/api/qc-analysis',
    mode: 'qc_analysis',
    unit: filterUnit,
    params: () => ({
        date:
            statusFilter.value === 'prevday' ||
            tableSearch.value.trim().length === 7
                ? undefined
                : filterDate.value || undefined,
        work_type: filterWorktype.value || undefined,
        lipas_yn: filterLipas.value || undefined,
        shift: filterShift.value || undefined,
        cutoff: filterCutoff.value || undefined,
        status_filter: statusFilter.value || undefined,
    }),
});

const {
    enabled: autoRefreshEnabled,
    interval: autoRefreshInterval,
    toggle: toggleAutoRefresh,
    setInterval: setAutoRefreshInterval,
} = useAutoRefresh(fetchRecords);

const selectedId = ref<number | null>(null);
const showModal = ref(false);
const modalLot = ref<MonitorRecord | null>(null);
const modalReadonly = ref(false);

function rowClass(rec: MonitorRecord) {
    if (rec.analysis_result === 'In Progress')
        return 'bg-amber-50/40 hover:bg-amber-50/60 dark:bg-amber-950/10';
    if (rec.analysis_result && rec.analysis_result !== 'For Decision')
        return 'bg-emerald-50/40 hover:bg-emerald-50/60 dark:bg-emerald-950/10';
    return 'hover:bg-muted/30';
}

function statusLabel(rec: MonitorRecord) {
    if (rec.analysis_result === 'In Progress') return 'In Progress';
    if (rec.analysis_result === 'For Decision') return 'Pending';
    if (rec.analysis_result) return 'Completed';
    return 'Pending';
}

function statusBadgeClass(rec: MonitorRecord) {
    if (rec.analysis_result === 'In Progress')
        return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400';
    if (rec.analysis_result === 'For Decision')
        return 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/30 dark:text-red-400';
    if (rec.analysis_result)
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    return 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/30 dark:text-red-400';
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

function qcResultBadgeClass(result: string): string {
    const r = result.toUpperCase();
    if (r === 'OK')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (r.includes('MAIN'))
        return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/30 dark:text-blue-400';
    if (r.includes('RR'))
        return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400';
    if (r.includes('LY'))
        return 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/30 dark:text-violet-400';
    return 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-800/30 dark:text-slate-400';
}

function openModal(rec: MonitorRecord, readonly = false) {
    modalReadonly.value = readonly;
    // Pre-fill immediately so modal shows lot details right away
    modalLot.value = rec as any;
    showModal.value = true;
    // Then enrich with inspection bin results in the background
    axios
        .get<{ data: any }>('/api/qc-analysis/find-by-lot', {
            params: { lot_id: rec.lot_id },
        })
        .then(({ data }) => {
            modalLot.value = data.data;
        })
        .catch(() => {
            // keep pre-filled data on error
        });
}

const showExportPicker = ref(false);
const tableSearch = ref('');
const { sortKey, sortDir, toggleSort, sortIcon, applySort } =
    useTableSort<MonitorRecord>();

function toggleStatusFilter(
    val: 'pending' | 'inprogress' | 'completed' | 'prevday' | 'all',
) {
    if (val === 'all') {
        statusFilter.value = null;
    } else {
        statusFilter.value = statusFilter.value === val ? null : val;
    }
    fetchRecords();
    fetchChartData();
}

const filteredRecords = computed(() => {
    const q = tableSearch.value.trim().toLowerCase();
    const bucket = activeWorkType.value;
    const sf = statusFilter.value;
    const todayStr = new Date().toLocaleDateString('en-CA', {
        timeZone: 'Asia/Manila',
    });

    const base = records.value.filter((r) => {
        if (
            q &&
            !(
                r.lot_id?.toLowerCase().includes(q) ||
                r.model?.toLowerCase().includes(q) ||
                r.defect_code?.toLowerCase().includes(q)
            )
        )
            return false;

        if (sf === 'pending')
            return !r.analysis_result || r.analysis_result === 'For Decision';
        if (sf === 'inprogress') return r.analysis_result === 'In Progress';
        if (sf === 'completed')
            return (
                !!r.analysis_result &&
                r.analysis_result !== 'In Progress' &&
                r.analysis_result !== 'For Decision'
            );
        if (sf === 'prevday') {
            if (r.analysis_result && r.analysis_result !== 'In Progress')
                return false;
            if (!r.analysis_start_at) return false;
            return (
                new Date(r.analysis_start_at).toLocaleDateString('en-CA', {
                    timeZone: 'Asia/Manila',
                }) < todayStr
            );
        }

        return true;
    });

    const now = Date.now();
    const elapsedFn = (r: MonitorRecord) =>
        r.analysis_start_at ? now - new Date(r.analysis_start_at).getTime() : 0;

    // Auto-sort: pending/in-progress (highest elapsed first) → completed (most recent first)
    const PENDING_STATUSES = [null, '', 'For Decision', 'In Progress'];
    const pending = base.filter((r) =>
        PENDING_STATUSES.includes(r.analysis_result ?? null),
    );
    const completed = base.filter(
        (r) =>
            r.analysis_result && !PENDING_STATUSES.includes(r.analysis_result),
    );

    const byElapsedDesc = (a: MonitorRecord, b: MonitorRecord) => {
        const aStart = a.analysis_start_at
            ? new Date(a.analysis_start_at).getTime()
            : 0;
        const bStart = b.analysis_start_at
            ? new Date(b.analysis_start_at).getTime()
            : 0;
        return aStart - bStart; // older start = higher elapsed = comes first
    };

    const sortedPending = [...pending].sort(byElapsedDesc);
    const sortedCompleted = [...completed].sort((a, b) => {
        const aT = a.analysis_completed_at
            ? new Date(a.analysis_completed_at).getTime()
            : 0;
        const bT = b.analysis_completed_at
            ? new Date(b.analysis_completed_at).getTime()
            : 0;
        return bT - aT;
    });

    // If user has applied a manual sort, respect it; otherwise use auto-sort
    return sortKey.value
        ? applySort(base, elapsedFn)
        : [...sortedPending, ...sortedCompleted];
});

function openAddModal() {
    modalLot.value = null;
    showModal.value = true;
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
    if (filterWorktype.value) p.set('work_type', filterWorktype.value);
    if (filterLipas.value) p.set('lipas_yn', filterLipas.value);
    window.location.href = `/api/qc-analysis/export?${p.toString()}`;
    showExportPicker.value = false;
}

function onKeydown(e: KeyboardEvent) {
    if (e.key !== 'F2') return;
    e.preventDefault();
    openAddModal();
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
    chartDataUrl: '/api/qc-analysis/chart-data',
    pieLabels: ['Pending', 'In Progress', 'Completed'],
    pieColors: ['#ef4444', '#f59e0b', '#10b981'],
    barSeriesNames: ['Pending', 'In Progress', 'Completed'],
    barColors: ['#ef4444', '#f59e0b', '#10b981'],
    getParams: () => ({
        date:
            statusFilter.value === 'prevday'
                ? undefined
                : filterDate.value || undefined,
        work_type: filterWorktype.value || undefined,
        lipas_yn: filterLipas.value || undefined,
        shift: filterShift.value || undefined,
        cutoff: filterCutoff.value || undefined,
        status_filter: statusFilter.value || undefined,
    }),
});

// Re-fetch charts whenever any page filter changes (Requirements 8.1, 8.3)
watch(
    [filterDate, filterShift, filterCutoff, filterWorktype, filterLipas],
    () => fetchChartData(),
);

// Re-fetch records when search changes (drop date filter to search all dates)
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(tableSearch, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchRecords(), 350);
});

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
