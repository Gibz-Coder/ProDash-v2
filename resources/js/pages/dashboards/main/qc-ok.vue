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
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
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
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
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
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background"
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
            <!-- Title -->
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
                        @click="openUpdateModal(null)"
                    >
                        <ArrowRightLeft class="h-3.5 w-3.5" /> Update Status
                        <span
                            class="ml-2 rounded bg-white/20 px-1.5 py-0.5 font-mono text-xs"
                            >F2</span
                        >
                    </button>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="flex gap-2">
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
                            {{ formatQty(meta.total_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-blue-600/70 dark:text-blue-400/70"
                        >
                            {{ meta.total_count }} lots
                        </p>
                    </div>
                </div>
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-red-50 to-red-100/50 px-3 py-2 shadow transition-all dark:from-red-950/30 dark:to-red-900/20"
                    :class="
                        cardFilter === 'pending'
                            ? 'border-red-500 ring-2 ring-red-400/50'
                            : 'border-border/50 hover:border-red-400/60'
                    "
                    @click="
                        cardFilter = cardFilter === 'pending' ? null : 'pending'
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
                            Pending
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-red-900 dark:text-red-100"
                        >
                            {{ formatQty(meta.pending_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-red-600/70 dark:text-red-400/70"
                        >
                            {{ meta.pending_count }} lots
                        </p>
                    </div>
                </div>
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
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-rose-50 to-rose-100/50 px-3 py-2 shadow transition-all dark:from-rose-950/30 dark:to-rose-900/20"
                    :class="
                        cardFilter === 'rework'
                            ? 'border-rose-500 ring-2 ring-rose-400/50'
                            : 'border-border/50 hover:border-rose-400/60'
                    "
                    @click="
                        cardFilter = cardFilter === 'rework' ? null : 'rework'
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
                            Rework
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-rose-900 dark:text-rose-100"
                        >
                            {{ formatQty(meta.rework_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-rose-600/70 dark:text-rose-400/70"
                        >
                            {{ meta.rework_count }} lots
                        </p>
                    </div>
                </div>
                <div
                    class="flex flex-1 cursor-pointer items-center gap-2 rounded-lg border bg-gradient-to-br from-amber-50 to-amber-100/50 px-3 py-2 shadow transition-all dark:from-amber-950/30 dark:to-amber-900/20"
                    :class="
                        cardFilter === 'prev_day'
                            ? 'border-amber-500 ring-2 ring-amber-400/50'
                            : 'border-border/50 hover:border-amber-400/60'
                    "
                    @click="
                        cardFilter =
                            cardFilter === 'prev_day' ? null : 'prev_day'
                    "
                >
                    <div
                        class="rounded-full bg-amber-500/10 p-1.5 ring-1 ring-amber-500/20"
                    >
                        <Timer
                            class="h-3.5 w-3.5 text-amber-600 dark:text-amber-400"
                        />
                    </div>
                    <div>
                        <p
                            class="text-[9px] font-semibold tracking-widest text-amber-700 uppercase dark:text-amber-300"
                        >
                            Prev Day
                        </p>
                        <p
                            class="text-lg leading-none font-bold text-amber-900 dark:text-amber-100"
                        >
                            {{ formatQty(meta.prev_day_qty) }}
                        </p>
                        <p
                            class="text-[9px] text-amber-600/70 dark:text-amber-400/70"
                        >
                            {{ meta.prev_day_count }} lots
                        </p>
                    </div>
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
                    <table class="w-full table-fixed text-xs">
                        <colgroup>
                            <col style="width: 40px" />
                            <col style="width: 80px" />
                            <col style="width: 120px" />
                            <col style="width: 65px" />
                            <col style="width: 48px" />
                            <col style="width: 75px" />
                            <col style="width: 105px" />
                            <col style="width: 62px" />
                            <col style="width: 75px" />
                            <col style="width: 75px" />
                            <col style="width: 80px" />
                            <col style="width: 80px" />
                            <col style="width: 80px" />
                            <col />
                            <col style="width: 85px" />
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
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    LIPAS
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('work_type')"
                                >
                                    W.Type
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
                                    Crt. By
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('updated_by')"
                                >
                                    Upd. By
                                    <span class="opacity-60">{{
                                        sortIcon('updated_by')
                                    }}</span>
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Insp. Result
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Ana. Result
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Tech. Result
                                </th>
                                <th
                                    class="cursor-pointer border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase select-none hover:bg-white/10"
                                    @click="toggleSort('output_status')"
                                >
                                    Status
                                    <span class="opacity-60">{{
                                        sortIcon('output_status')
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
                                class="transition-colors hover:bg-muted/30"
                                :class="rowClass(rec)"
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
                                    {{ fmt(rec.created_at) }}
                                </td>
                                <td
                                    class="px-2 py-2 text-xs whitespace-nowrap text-amber-600 dark:text-amber-400"
                                >
                                    {{ elapsedStr(rec) }}
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
                                        v-if="rec.inspection_result"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            resultBadgeClass(
                                                rec.inspection_result,
                                            )
                                        "
                                    >
                                        {{ rec.inspection_result }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        v-if="rec.analysis_result"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            resultBadgeClass(
                                                rec.analysis_result,
                                            )
                                        "
                                    >
                                        {{ rec.analysis_result }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        v-if="rec.technical_result"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            resultBadgeClass(
                                                rec.technical_result,
                                            )
                                        "
                                    >
                                        {{ rec.technical_result }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-muted-foreground/40"
                                        >—</span
                                    >
                                </td>
                                <td class="px-2 py-2">
                                    <span
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            statusBadgeClass(rec.output_status)
                                        "
                                    >
                                        {{ rec.output_status || 'Pending' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2">
                                    <div
                                        class="flex items-center justify-center"
                                    >
                                        <button
                                            class="h-7 rounded border px-3 text-[10px] font-semibold transition-colors"
                                            :class="
                                                isFinalized(rec)
                                                    ? 'cursor-not-allowed border-border/30 bg-muted/30 text-muted-foreground/40'
                                                    : 'border-primary/30 bg-primary/10 text-primary hover:bg-primary/20'
                                            "
                                            :disabled="isFinalized(rec)"
                                            @click="
                                                !isFinalized(rec) &&
                                                openUpdateModal(rec)
                                            "
                                        >
                                            <ArrowRightLeft
                                                class="mr-1 inline h-3 w-3"
                                            />Update
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="filteredRecords.length === 0">
                                <td
                                    colspan="15"
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

        <!-- Update Status Modal -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="showModal = false"
        >
            <div
                class="w-full max-w-sm rounded-xl border border-border bg-card p-6 shadow-2xl"
            >
                <h3 class="mb-4 text-sm font-bold text-foreground">
                    Update Output Status
                </h3>
                <div
                    v-if="modalRecord"
                    class="mb-4 rounded-lg bg-muted/40 px-3 py-2 text-xs"
                >
                    <span class="font-mono font-bold text-primary">{{
                        modalRecord.lot_id
                    }}</span>
                    <span class="ml-2 text-muted-foreground">{{
                        modalRecord.model
                    }}</span>
                </div>
                <div class="space-y-3">
                    <div>
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Status</label
                        >
                        <select
                            v-model="modalStatus"
                            class="h-9 w-full rounded-lg border border-input bg-background px-3 text-xs text-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                        >
                            <option value="">Select status...</option>
                            <option value="Completed">Completed</option>
                            <option value="Rework">Rework</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >Remarks</label
                        >
                        <textarea
                            v-model="modalRemarks"
                            rows="2"
                            class="w-full resize-none rounded-lg border border-input bg-background px-3 py-2 text-xs text-foreground focus:border-primary focus:ring-1 focus:ring-primary focus:outline-none"
                            placeholder="Optional remarks..."
                        />
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button
                        class="flex-1 rounded border border-border bg-background px-3 py-1.5 text-xs font-medium text-foreground hover:bg-muted"
                        @click="showModal = false"
                    >
                        Cancel
                    </button>
                    <button
                        class="flex-1 rounded bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60"
                        :disabled="!modalStatus || saving"
                        @click="saveStatus"
                    >
                        <span
                            v-if="saving"
                            class="mr-1 inline-block h-3 w-3 animate-spin rounded-full border-2 border-white border-r-transparent"
                        />
                        Save
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AutoRefreshControl from '@/components/AutoRefreshControl.vue';
import { useAutoRefresh } from '@/composables/useAutoRefresh';
import { useTableSort } from '@/composables/useTableSort';
import AppLayout from '@/layouts/AppLayout.vue';
import axios from 'axios';
import {
    AlertCircle,
    ArrowRightLeft,
    CheckCircle2,
    Clock,
    Package,
    RefreshCw,
    Search,
    Timer,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface QcOkRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    work_type: string | null;
    inspection_times: number | null;
    inspection_spl: number | null;
    inspection_result: string | null;
    analysis_result: string | null;
    technical_result: string | null;
    pending: string | null;
    remarks: string | null;
    output_status: string | null;
    lot_completed_at: string | null;
    created_by: string | null;
    updated_by: string | null;
    total_tat: number | null;
    created_at: string | null;
    updated_at: string | null;
}

interface Meta {
    total_count: number;
    total_qty: number;
    pending_count: number;
    pending_qty: number;
    completed_count: number;
    completed_qty: number;
    rework_count: number;
    rework_qty: number;
    prev_day_count: number;
    prev_day_qty: number;
}

// ── Filters ───────────────────────────────────────────────────────────────────
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
const tableSearch = ref('');
const cardFilter = ref<string | null>(null);

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

// ── Data ──────────────────────────────────────────────────────────────────────
const records = ref<QcOkRecord[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const meta = ref<Meta>({
    total_count: 0,
    total_qty: 0,
    pending_count: 0,
    pending_qty: 0,
    completed_count: 0,
    completed_qty: 0,
    rework_count: 0,
    rework_qty: 0,
    prev_day_count: 0,
    prev_day_qty: 0,
});

async function fetchRecords() {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get('/api/qc-ok', {
            params: {
                date:
                    tableSearch.value.trim() || cardFilter.value === 'prev_day'
                        ? undefined
                        : filterDate.value || undefined,
                shift: filterShift.value || undefined,
                cutoff: filterCutoff.value || undefined,
                work_type: filterWorktype.value || undefined,
                lipas_yn: filterLipas.value || undefined,
                search: tableSearch.value.trim() || undefined,
            },
        });
        records.value = data.data ?? [];
        if (data.meta) meta.value = data.meta;
    } catch (e: any) {
        error.value = e.response?.data?.error ?? 'Failed to load records';
    } finally {
        loading.value = false;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt(dt: string | null): string {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function elapsedStr(rec: QcOkRecord): string {
    const start = rec.created_at;
    if (!start) return '—';
    const endMs = rec.lot_completed_at
        ? new Date(rec.lot_completed_at).getTime()
        : Date.now();
    const mins = Math.floor((endMs - new Date(start).getTime()) / 60_000);
    if (mins < 60) return `${mins}m`;
    return `${Math.floor(mins / 60)}h ${mins % 60}m`;
}

function isFinalized(rec: QcOkRecord): boolean {
    return rec.output_status === 'Completed' || rec.output_status === 'Rework';
}

function rowClass(rec: QcOkRecord): string {
    if (rec.output_status === 'Completed')
        return 'bg-emerald-50/30 dark:bg-emerald-950/10';
    if (rec.output_status === 'Rework')
        return 'bg-rose-50/30 dark:bg-rose-950/10';
    return '';
}

function statusBadgeClass(status: string | null): string {
    if (status === 'Completed')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (status === 'Rework')
        return 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/30 dark:text-rose-400';
    return 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/30 dark:text-red-400';
}

function resultBadgeClass(result: string | null): string {
    const r = (result ?? '').toUpperCase();
    if (r === 'OK' || r === 'PASS' || r === 'PROCEED')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    if (r === 'NG' || r === 'FAIL')
        return 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/30 dark:text-red-400';
    return 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-800/30 dark:text-slate-400';
}

// ── Sort + filter ─────────────────────────────────────────────────────────────
const { toggleSort, sortIcon, applySort } = useTableSort<QcOkRecord>();

const filteredRecords = computed(() => {
    const q = tableSearch.value.trim().toLowerCase();
    let base = q
        ? records.value.filter(
              (r) =>
                  r.lot_id?.toLowerCase().includes(q) ||
                  r.model?.toLowerCase().includes(q),
          )
        : records.value.slice();

    if (cardFilter.value === 'pending') {
        base = base.filter(
            (r) => !r.output_status || r.output_status === 'Pending',
        );
    } else if (cardFilter.value === 'completed') {
        base = base.filter((r) => r.output_status === 'Completed');
    } else if (cardFilter.value === 'rework') {
        base = base.filter((r) => r.output_status === 'Rework');
    } else if (cardFilter.value === 'prev_day') {
        const today = new Date().toLocaleDateString('en-CA', {
            timeZone: 'Asia/Manila',
        });
        base = base.filter(
            (r) =>
                (!r.output_status || r.output_status === 'Pending') &&
                r.created_at &&
                new Date(r.created_at).toLocaleDateString('en-CA', {
                    timeZone: 'Asia/Manila',
                }) < today,
        );
    }

    return applySort(base, (r: QcOkRecord) =>
        r.created_at ? Date.now() - new Date(r.created_at).getTime() : 0,
    );
});

// ── Modal ─────────────────────────────────────────────────────────────────────
const showModal = ref(false);
const modalRecord = ref<QcOkRecord | null>(null);
const modalStatus = ref('');
const modalRemarks = ref('');
const saving = ref(false);

function openUpdateModal(rec: QcOkRecord | null) {
    modalRecord.value = rec;
    modalStatus.value = rec?.output_status ?? '';
    modalRemarks.value = rec?.remarks ?? '';
    showModal.value = true;
}

async function saveStatus() {
    if (!modalRecord.value || !modalStatus.value) return;
    saving.value = true;
    try {
        await axios.put(`/api/qc-ok/${modalRecord.value.id}`, {
            output_status: modalStatus.value,
            remarks: modalRemarks.value || null,
        });
        showModal.value = false;
        await fetchRecords();
    } catch (e: any) {
        error.value = e.response?.data?.message ?? 'Failed to save';
    } finally {
        saving.value = false;
    }
}

// ── Auto-refresh ──────────────────────────────────────────────────────────────
const {
    enabled: autoRefreshEnabled,
    interval: autoRefreshInterval,
    toggle: toggleAutoRefresh,
    setInterval: setAutoRefreshInterval,
} = useAutoRefresh(fetchRecords);

function onKeydown(e: KeyboardEvent) {
    if (e.key !== 'F2') return;
    e.preventDefault();
    openUpdateModal(null);
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(tableSearch, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchRecords(), 350);
});
watch(
    [filterDate, filterShift, filterCutoff, filterWorktype, filterLipas],
    () => fetchRecords(),
);
watch(cardFilter, () => fetchRecords());

onMounted(() => {
    fetchRecords();
    document.addEventListener('keydown', onKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown);
});
</script>
