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
            </div>
        </template>

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl leading-tight font-bold">
                        QC OK Monitoring
                    </h1>
                    <p class="text-[11px] text-muted-foreground">
                        Lots with QC OK result
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
                        <ArrowRightLeft class="h-3.5 w-3.5" /> Update Status
                        <span
                            class="ml-2 rounded bg-white/20 px-1.5 py-0.5 font-mono text-xs"
                            >F2</span
                        >
                    </button>
                    <button
                        class="flex h-8 items-center gap-1.5 rounded-lg bg-blue-600 px-3 text-xs font-medium text-white hover:bg-blue-700 disabled:pointer-events-none disabled:opacity-60"
                        :disabled="autoUpdating"
                        @click="onAutoUpdate"
                    >
                        <span
                            v-if="autoUpdating"
                            class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-r-transparent"
                        />
                        <Zap v-else class="h-3.5 w-3.5" /> Auto Update
                    </button>
                </div>
            </div>

            <div class="flex gap-2">
                <!-- Total -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-blue-50 to-blue-100/50 px-3 py-2 shadow transition-all dark:from-blue-950/30 dark:to-blue-900/20"
                    :class="
                        cardFilter === null
                            ? 'border-blue-500 ring-2 ring-blue-400/50'
                            : 'border-border/50 hover:border-blue-400/60'
                    "
                    @click="cardFilter = null"
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
                            {{ totalQtyFmt }}
                        </p>
                        <p
                            class="text-[9px] text-blue-600/70 dark:text-blue-400/70"
                        >
                            {{ meta.total_count }} lots
                        </p>
                    </div>
                </div>
                <!-- QC Pending -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-red-50 to-red-100/50 px-3 py-2 shadow transition-all dark:from-red-950/30 dark:to-red-900/20"
                    :class="
                        cardFilter === 'qc_pending'
                            ? 'border-red-500 ring-2 ring-red-400/50'
                            : 'border-border/50 hover:border-red-400/60'
                    "
                    @click="
                        cardFilter =
                            cardFilter === 'qc_pending' ? null : 'qc_pending'
                    "
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
                            QC Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-red-900 dark:text-red-100"
                        >
                            {{ formatQty(meta.qc_pending_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-red-600/70 dark:text-red-400/70"
                        >
                            {{ meta.qc_pending_count }} lots
                        </p>
                    </div>
                </div>
                <!-- Technical Pending -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-amber-50 to-amber-100/50 px-3 py-2 shadow transition-all dark:from-amber-950/30 dark:to-amber-900/20"
                    :class="
                        cardFilter === 'tech_pending'
                            ? 'border-amber-500 ring-2 ring-amber-400/50'
                            : 'border-border/50 hover:border-amber-400/60'
                    "
                    @click="
                        cardFilter =
                            cardFilter === 'tech_pending'
                                ? null
                                : 'tech_pending'
                    "
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
                            Technical Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-amber-900 dark:text-amber-100"
                        >
                            {{ formatQty(meta.tech_pending_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-amber-600/70 dark:text-amber-400/70"
                        >
                            {{ meta.tech_pending_count }} lots
                        </p>
                    </div>
                </div>
                <!-- Production Pending -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-violet-50 to-violet-100/50 px-3 py-2 shadow transition-all dark:from-violet-950/30 dark:to-violet-900/20"
                    :class="
                        cardFilter === 'prod_pending'
                            ? 'border-violet-500 ring-2 ring-violet-400/50'
                            : 'border-border/50 hover:border-violet-400/60'
                    "
                    @click="
                        cardFilter =
                            cardFilter === 'prod_pending'
                                ? null
                                : 'prod_pending'
                    "
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
                            Production Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-violet-900 dark:text-violet-100"
                        >
                            {{ formatQty(meta.prod_pending_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-violet-600/70 dark:text-violet-400/70"
                        >
                            {{ meta.prod_pending_count }} lots
                        </p>
                    </div>
                </div>
                <!-- Completed -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-emerald-50 to-emerald-100/50 px-3 py-2 shadow transition-all dark:from-emerald-950/30 dark:to-emerald-900/20"
                    :class="
                        cardFilter === 'completed'
                            ? 'border-emerald-500 ring-2 ring-emerald-400/50'
                            : 'border-border/50 hover:border-emerald-400/60'
                    "
                    @click="
                        cardFilter =
                            cardFilter === 'completed' ? null : 'completed'
                    "
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
                            {{ formatQty(meta.completed_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-emerald-600/70 dark:text-emerald-400/70"
                        >
                            {{ meta.completed_count }} lots
                        </p>
                    </div>
                </div>
                <!-- Prev Days -->
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-rose-50 to-rose-100/50 px-3 py-2 shadow transition-all dark:from-rose-950/30 dark:to-rose-900/20"
                    :class="
                        cardFilter === 'prev_day'
                            ? 'border-rose-500 ring-2 ring-rose-400/50'
                            : 'border-border/50 hover:border-rose-400/60'
                    "
                    @click="
                        cardFilter =
                            cardFilter === 'prev_day' ? null : 'prev_day'
                    "
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
                            Prev Days Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-rose-900 dark:text-rose-100"
                        >
                            {{ formatQty(meta.prev_day_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-rose-600/70 dark:text-rose-400/70"
                        >
                            {{ meta.prev_day_count }} lots
                        </p>
                    </div>
                </div>
            </div>

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
                >
                    <table class="w-full min-w-[900px] table-fixed text-xs">
                        <colgroup>
                            <col class="w-[40px]" />
                            <col class="w-[100px]" />
                            <col class="w-[140px]" />
                            <col class="w-[75px]" />
                            <col class="w-[50px]" />
                            <col class="w-[100px]" />
                            <col class="w-[120px]" />
                            <col class="w-[70px]" />
                            <col class="w-[100px]" />
                            <col class="w-[100px]" />
                            <col class="w-[80px]" />
                            <col class="w-[85px]" />
                            <col class="w-[130px]" />
                            <col class="w-[90px]" />
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
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    LIPAS
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('work_type')"
                                >
                                    Worktype
                                    <span class="opacity-60">{{
                                        sortIcon('work_type')
                                    }}</span>
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('created_at')"
                                >
                                    Date Time
                                    <span class="opacity-60">{{
                                        sortIcon('created_at')
                                    }}</span>
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Elapsed
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Created By
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
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    QC Result
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Output Status
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
                                class="bg-emerald-50/30 transition-colors hover:bg-muted/30 dark:bg-emerald-950/10"
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
                                >
                                    {{ rec.work_type || '—' }}
                                </td>
                                <td
                                    class="px-2 py-2 text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{ formatDateTime(arrivalAt(rec)) }}
                                </td>
                                <td
                                    class="px-2 py-2 text-xs whitespace-nowrap text-amber-600 dark:text-amber-400"
                                >
                                    {{
                                        rec.output_status === 'Completed' ||
                                        rec.output_status === 'Rework'
                                            ? rec.lot_completed_at &&
                                              rec.updated_at
                                                ? formatDuration(
                                                      Math.max(
                                                          0,
                                                          Math.floor(
                                                              (new Date(
                                                                  rec.lot_completed_at,
                                                              ).getTime() -
                                                                  new Date(
                                                                      rec.updated_at,
                                                                  ).getTime()) /
                                                                  60_000,
                                                          ),
                                                      ),
                                                  )
                                                : '—'
                                            : rec.updated_at
                                              ? formatDuration(
                                                    Math.floor(
                                                        (Date.now() -
                                                            new Date(
                                                                rec.updated_at,
                                                            ).getTime()) /
                                                            60_000,
                                                    ),
                                                )
                                              : '—'
                                    }}
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
                                        class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            qcResultBadgeClass(rec.qc_result)
                                        "
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        {{ rec.qc_result || 'OK' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            outputStatusBadgeClass(
                                                rec.output_status,
                                            )
                                        "
                                    >
                                        {{ rec.output_status || 'Pending' }}
                                    </span>
                                </td>
                                <td
                                    class="truncate px-2 py-2 text-xs font-medium"
                                    :title="rec.remarks ?? ''"
                                    :class="remarkClass(rec.remarks)"
                                >
                                    {{ rec.remarks || '—' }}
                                </td>
                                <td class="px-2 py-2">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <button
                                            class="h-7 rounded border px-3 text-[10px] font-semibold transition-colors"
                                            :class="
                                                rec.output_status ===
                                                    'Completed' ||
                                                rec.output_status === 'Rework'
                                                    ? 'cursor-not-allowed border-border/30 bg-muted/30 text-muted-foreground/40'
                                                    : 'border-primary/30 bg-primary/10 text-primary hover:bg-primary/20'
                                            "
                                            :disabled="
                                                rec.output_status ===
                                                    'Completed' ||
                                                rec.output_status === 'Rework'
                                            "
                                            @click="
                                                rec.output_status !==
                                                    'Completed' &&
                                                rec.output_status !==
                                                    'Rework' &&
                                                openUpdateStatus(rec)
                                            "
                                        >
                                            <ArrowRightLeft
                                                class="mr-1 inline h-3 w-3"
                                            />Update Status
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRecords.length === 0">
                                <td
                                    colspan="14"
                                    class="py-12 text-center text-muted-foreground"
                                >
                                    <CheckCircle2
                                        class="mx-auto h-12 w-12 opacity-20"
                                    />
                                    <p class="mt-2 text-xs">
                                        No QC OK lots found
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <QcOkUpdateModal
            :open="showUpdateModal"
            :lot="updateRecord"
            @update:open="showUpdateModal = $event"
            @saved="fetchRecords"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import AutoRefreshControl from '@/components/AutoRefreshControl.vue';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import { formatDuration } from '@/composables/useMonitorPage';
import { useTableSort } from '@/composables/useTableSort';
import AppLayout from '@/layouts/AppLayout.vue';
import QcOkUpdateModal from '@/pages/dashboards/subs/qc-ok-update-modal.vue';
import axios from 'axios';
import {
    AlertCircle,
    ArrowRightLeft,
    CheckCircle2,
    Clock,
    Loader2,
    Package,
    RefreshCw,
    Search,
    Timer,
    Zap,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface QcOkRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    qc_result: string | null;
    qc_defect: string | null;
    defect_class: string | null;
    work_type: string | null;
    final_decision: string | null;
    output_status: string | null;
    lot_completed_at: string | null;
    remarks: string | null;
    updated_by: string | null;
    created_by: string | null;
    created_at: string | null;
    updated_at: string | null;
    qc_ana_start: string | null;
    qc_ana_result: string | null;
    vi_techl_start: string | null;
    vi_techl_result: string | null;
    qc_ana_completed_at: string | null;
    vi_techl_completed_at: string | null;
}

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

function formatDateTime(dt: string | null) {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// Arrival date on the QC OK page:
// vi_techl_completed_at → qc_ana_completed_at → created_at
function arrivalAt(rec: QcOkRecord): string | null {
    return (
        rec.vi_techl_completed_at ?? rec.qc_ana_completed_at ?? rec.created_at
    );
}

const records = ref<QcOkRecord[]>([]);
const prevDayRecords = ref<QcOkRecord[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const tableSearch = ref('');
const cardFilter = ref<string | null>(null);
const showExportPicker = ref(false);
const showUpdateModal = ref(false);
const updateRecord = ref<QcOkRecord | null>(null);
const today = new Date().toLocaleDateString('en-CA', {
    timeZone: 'Asia/Manila',
});
const exportDateFrom = ref(today);
const exportDateTo = ref(today);

interface QcOkMeta {
    total_count: number;
    total_qty: number;
    qc_pending_count: number;
    qc_pending_qty: number;
    tech_pending_count: number;
    tech_pending_qty: number;
    prod_pending_count: number;
    prod_pending_qty: number;
    completed_count: number;
    completed_qty: number;
    prev_day_count: number;
    prev_day_qty: number;
}
const meta = ref<QcOkMeta>({
    total_count: 0,
    total_qty: 0,
    qc_pending_count: 0,
    qc_pending_qty: 0,
    tech_pending_count: 0,
    tech_pending_qty: 0,
    prod_pending_count: 0,
    prod_pending_qty: 0,
    completed_count: 0,
    completed_qty: 0,
    prev_day_count: 0,
    prev_day_qty: 0,
});

async function fetchRecords() {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get<{
            success: boolean;
            data: QcOkRecord[];
            meta: QcOkMeta;
        }>('/api/endline-delay/qc-ok-monitor', {
            params: {
                date: tableSearch.value.trim()
                    ? undefined
                    : filterDate.value || undefined,
                shift: filterShift.value || undefined,
                cutoff: filterCutoff.value || undefined,
                work_type: filterWorktype.value || undefined,
                lipas_yn: filterLipas.value || undefined,
            },
        });
        records.value = data.data ?? [];
        if (data.meta) meta.value = data.meta;

        // Fetch prev-day pending records independently (no date filter)
        const { data: prevData } = await axios.get<{
            success: boolean;
            data: QcOkRecord[];
        }>('/api/endline-delay/qc-ok-monitor', {
            params: {
                prev_day_only: 1,
                shift: filterShift.value || undefined,
                work_type: filterWorktype.value || undefined,
                lipas_yn: filterLipas.value || undefined,
            },
        });
        prevDayRecords.value = prevData.data ?? [];
    } catch (e: unknown) {
        error.value = e instanceof Error ? e.message : 'Failed to load records';
    } finally {
        loading.value = false;
    }
}

function statusLabel(rec: QcOkRecord): string {
    if (rec.output_status === 'Completed') return 'Completed';
    if (rec.final_decision === 'For Verify') return 'For Production';
    if (rec.qc_ana_result === 'Proceed') return 'For Production';
    if (rec.vi_techl_result === 'Proceed') return 'For Production';
    // Fresh QC OK lot — no routing applied yet
    if (
        rec.qc_result === 'OK' &&
        !rec.qc_ana_result &&
        !rec.vi_techl_result &&
        rec.defect_class !== 'QC Analysis' &&
        rec.defect_class !== "Tech'l Verification"
    )
        return 'For Production';
    if (rec.defect_class === "Tech'l Verification" && !rec.vi_techl_result)
        return 'VI Technical';
    if (rec.defect_class === 'QC Analysis' && !rec.qc_ana_result)
        return 'QC Analysis';
    if (rec.final_decision === 'Recovery') return 'Rework';
    return 'Pending';
}

function remarkClass(remarks: string | null): string {
    const r = (remarks ?? '').toLowerCase();
    if (r.includes('ok') || r.includes('proceed'))
        return 'text-emerald-600 dark:text-emerald-400';
    if (r.includes('ng') || r.includes('rework'))
        return 'text-red-600 dark:text-red-400';
    return 'text-foreground';
}

function outputStatusBadgeClass(status: string | null): string {
    if (status === 'Completed')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (status === 'Rework')
        return 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/30 dark:text-rose-400';
    return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400';
}

function statusBadgeClass(rec: QcOkRecord): string {
    const label = statusLabel(rec);
    if (label === 'Completed')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (label === 'QC Analysis')
        return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/30 dark:text-blue-400';
    if (label === 'VI Technical')
        return 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/30 dark:text-violet-400';
    if (label === 'For Production')
        return 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-950/30 dark:text-orange-400';
    if (label === 'Rework')
        return 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/30 dark:text-rose-400';
    return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400';
}

function qcResultBadgeClass(result: string | null): string {
    const r = (result ?? '').toUpperCase();
    if (r === 'OK')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (r.includes('MAIN'))
        return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/30 dark:text-blue-400';
    if (r.includes('RR') || r.includes('REWORK'))
        return 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/30 dark:text-amber-400';
    if (r.includes('LY'))
        return 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/30 dark:text-violet-400';
    return 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-800/30 dark:text-slate-400';
}

function openUpdateStatus(rec: QcOkRecord) {
    updateRecord.value = rec;
    showUpdateModal.value = true;
}

function openAddModal() {
    updateRecord.value = null;
    showUpdateModal.value = true;
}

const autoUpdating = ref(false);

async function onAutoUpdate() {
    // Only process pending lots from the current filtered view
    const pendingIds = filteredRecords.value
        .filter(
            (r) =>
                r.output_status !== 'Completed' && r.output_status !== 'Rework',
        )
        .map((r) => r.id);

    if (!pendingIds.length) return;

    autoUpdating.value = true;
    try {
        await axios.post('/api/endline-delay/auto-update-qc-ok', {
            ids: pendingIds,
        });
        await fetchRecords();
    } catch (e: unknown) {
        error.value = e instanceof Error ? e.message : 'Auto update failed';
    } finally {
        autoUpdating.value = false;
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key !== 'F2') return;
    e.preventDefault();
    openAddModal();
}

function triggerExport() {
    const p = new URLSearchParams();
    if (exportDateFrom.value) p.set('date_from', exportDateFrom.value);
    if (exportDateTo.value) p.set('date_to', exportDateTo.value);
    window.location.href = `/api/endline-delay/export?${p.toString()}`;
    showExportPicker.value = false;
}

const {
    enabled: autoRefreshEnabled,
    interval: autoRefreshInterval,
    toggle: toggleAutoRefresh,
    setInterval: setAutoRefreshInterval,
} = useAutoRefresh(fetchRecords);
const { toggleSort, sortIcon, applySort } = useTableSort<QcOkRecord>();

const filteredRecords = computed(() => {
    const q = tableSearch.value.trim().toLowerCase();
    let base = q
        ? records.value.filter(
              (r) =>
                  r.lot_id?.toLowerCase().includes(q) ||
                  r.model?.toLowerCase().includes(q) ||
                  r.qc_defect?.toLowerCase().includes(q),
          )
        : records.value;

    if (cardFilter.value === 'qc_pending') {
        base = base.filter(
            (r) =>
                (r.defect_class === 'QC Analysis' &&
                    !r.qc_ana_result &&
                    r.qc_result === 'OK') ||
                r.final_decision === 'For Decide QC',
        );
    } else if (cardFilter.value === 'tech_pending') {
        base = base.filter(
            (r) =>
                (r.defect_class === "Tech'l Verification" &&
                    r.vi_techl_start &&
                    !r.vi_techl_result) ||
                r.final_decision === "For Decision Tech'l",
        );
    } else if (cardFilter.value === 'prod_pending') {
        base = base.filter(
            (r) =>
                r.output_status !== 'Completed' &&
                r.output_status !== 'Rework' &&
                r.final_decision !== "For Decision Tech'l" &&
                r.final_decision !== 'For Decide QC' &&
                ((r.qc_result === 'OK' &&
                    !r.qc_ana_result &&
                    !r.vi_techl_result &&
                    r.defect_class !== 'QC Analysis' &&
                    r.defect_class !== "Tech'l Verification") ||
                    r.final_decision === 'For Verify' ||
                    r.qc_ana_result === 'Proceed' ||
                    r.vi_techl_result === 'Proceed'),
        );
    } else if (cardFilter.value === 'completed') {
        base = base.filter(
            (r) =>
                r.output_status === 'Completed' || r.output_status === 'Rework',
        );
    } else if (cardFilter.value === 'prev_day') {
        // Use the independently-fetched prev-day records so they show
        // even when the current date filter has no matching data (e.g. viewing today)
        const q = tableSearch.value.trim().toLowerCase();
        base = q
            ? prevDayRecords.value.filter(
                  (r) =>
                      r.lot_id?.toLowerCase().includes(q) ||
                      r.model?.toLowerCase().includes(q) ||
                      r.qc_defect?.toLowerCase().includes(q),
              )
            : prevDayRecords.value.slice();
    }

    const now = Date.now();
    return applySort(base, (r: QcOkRecord) => {
        const at = arrivalAt(r);
        return at ? now - new Date(at).getTime() : 0;
    });
});

const totalQtyFmt = computed(() => formatQty(meta.value.total_qty));
const lipasYCount = computed(
    () => records.value.filter((r) => r.lipas_yn === 'Y').length,
);
const lipasYQtyFmt = computed(() =>
    formatQty(
        records.value
            .filter((r) => r.lipas_yn === 'Y')
            .reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    ),
);
const lipasNCount = computed(
    () => records.value.filter((r) => r.lipas_yn !== 'Y').length,
);
const lipasNQtyFmt = computed(() =>
    formatQty(
        records.value
            .filter((r) => r.lipas_yn !== 'Y')
            .reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    ),
);
const normalCount = computed(
    () => records.value.filter((r) => r.work_type === 'NORMAL').length,
);
const normalQtyFmt = computed(() =>
    formatQty(
        records.value
            .filter((r) => r.work_type === 'NORMAL')
            .reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    ),
);
const reworkCount = computed(
    () =>
        records.value.filter((r) => r.work_type && r.work_type !== 'NORMAL')
            .length,
);
const reworkQtyFmt = computed(() =>
    formatQty(
        records.value
            .filter((r) => r.work_type && r.work_type !== 'NORMAL')
            .reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    ),
);
const avgTat = computed(() => {
    const mins = records.value
        .filter((r) => r.created_at && r.updated_at)
        .map((r) =>
            Math.round(
                (new Date(r.updated_at!).getTime() -
                    new Date(r.created_at!).getTime()) /
                    60_000,
            ),
        )
        .filter((m) => m > 0);
    if (!mins.length) return '—';
    return formatDuration(
        Math.round(mins.reduce((a, b) => a + b, 0) / mins.length),
    );
});

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(tableSearch, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchRecords(), 350);
});
watch(
    [filterDate, filterShift, filterCutoff, filterWorktype, filterLipas],
    () => fetchRecords(),
);
onMounted(() => {
    fetchRecords();
    document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
});
</script>
