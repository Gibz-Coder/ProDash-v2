<template>
    <AppLayout>
        <template #filters>
            <div class="flex items-center gap-3">
                <!-- Date Filter -->
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

                <!-- Shift Filter -->
                <div
                    class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-1.5 shadow-sm"
                >
                    <span class="text-xs font-medium text-muted-foreground"
                        >SHIFT:</span
                    >
                    <select
                        v-model="filterShift"
                        @change="fetchRecords"
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
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
                        class="cursor-pointer border-0 bg-transparent pr-6 text-xs font-semibold text-foreground focus:ring-0 focus:outline-none [&>option]:bg-background [&>option]:text-foreground"
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

                <!-- Refresh -->
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
                    <!-- Date range picker dropdown -->
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
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold">Endline Delay Records</h1>
                    <p class="text-muted-foreground">
                        QC analysis and technical verification delay entries
                    </p>
                </div>
                <button
                    class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/25"
                    @click="openCreateModal"
                >
                    <PlusCircle class="mr-2 h-4 w-4" /> Add Record
                    <span
                        class="ml-2 rounded bg-white/20 px-1.5 py-0.5 font-mono text-xs"
                        >F2</span
                    >
                </button>
            </div>

            <div class="grid grid-cols-6 gap-3">
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-blue-50 to-blue-100/50 p-4 shadow-lg dark:from-blue-950/30 dark:to-blue-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-blue-700 dark:text-blue-300"
                            >
                                Total
                            </p>
                            <p
                                class="text-2xl font-bold text-blue-900 dark:text-blue-100"
                            >
                                {{ formatQty(totalQty()) }}
                            </p>
                            <p
                                class="text-xs text-blue-600/70 dark:text-blue-400/70"
                            >
                                {{ records.length }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-blue-500/10 p-3 ring-1 ring-blue-500/20"
                        >
                            <Clock
                                class="h-5 w-5 text-blue-600 dark:text-blue-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-4 shadow-lg dark:from-emerald-950/30 dark:to-emerald-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-emerald-700 dark:text-emerald-300"
                            >
                                QC Analysis
                            </p>
                            <p
                                class="text-2xl font-bold text-emerald-900 dark:text-emerald-100"
                            >
                                {{
                                    formatQty(
                                        sumQtyByDefectClass('QC Analysis'),
                                    )
                                }}
                            </p>
                            <p
                                class="text-xs text-emerald-600/70 dark:text-emerald-400/70"
                            >
                                {{ countByDefectClass('QC Analysis') }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-emerald-500/10 p-3 ring-1 ring-emerald-500/20"
                        >
                            <CheckCircle
                                class="h-5 w-5 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-amber-50 to-amber-100/50 p-4 shadow-lg dark:from-amber-950/30 dark:to-amber-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-amber-700 dark:text-amber-300"
                            >
                                VI Technical
                            </p>
                            <p
                                class="text-2xl font-bold text-amber-900 dark:text-amber-100"
                            >
                                {{
                                    formatQty(
                                        sumQtyByDefectClass(
                                            "Tech'l Verfication",
                                        ),
                                    )
                                }}
                            </p>
                            <p
                                class="text-xs text-amber-600/70 dark:text-amber-400/70"
                            >
                                {{ countByDefectClass("Tech'l Verfication") }}
                                lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-amber-500/10 p-3 ring-1 ring-amber-500/20"
                        >
                            <AlertCircle
                                class="h-5 w-5 text-amber-600 dark:text-amber-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-violet-50 to-violet-100/50 p-4 shadow-lg dark:from-violet-950/30 dark:to-violet-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-violet-700 dark:text-violet-300"
                            >
                                Mainlot
                            </p>
                            <p
                                class="text-2xl font-bold text-violet-900 dark:text-violet-100"
                            >
                                {{ formatQty(sumQtyByQcResult('Main')) }}
                            </p>
                            <p
                                class="text-xs text-violet-600/70 dark:text-violet-400/70"
                            >
                                {{ countByQcResult('Main') }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-violet-500/10 p-3 ring-1 ring-violet-500/20"
                        >
                            <Package
                                class="h-5 w-5 text-violet-600 dark:text-violet-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-orange-50 to-orange-100/50 p-4 shadow-lg dark:from-orange-950/30 dark:to-orange-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-orange-700 dark:text-orange-300"
                            >
                                R-Rework
                            </p>
                            <p
                                class="text-2xl font-bold text-orange-900 dark:text-orange-100"
                            >
                                {{ formatQty(sumQtyByQcResult('RR')) }}
                            </p>
                            <p
                                class="text-xs text-orange-600/70 dark:text-orange-400/70"
                            >
                                {{ countByQcResult('RR') }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-orange-500/10 p-3 ring-1 ring-orange-500/20"
                        >
                            <RefreshCw
                                class="h-5 w-5 text-orange-600 dark:text-orange-400"
                            />
                        </div>
                    </div>
                </div>
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-pink-50 to-pink-100/50 p-4 shadow-lg dark:from-pink-950/30 dark:to-pink-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-pink-700 dark:text-pink-300"
                            >
                                L-Rework
                            </p>
                            <p
                                class="text-2xl font-bold text-pink-900 dark:text-pink-100"
                            >
                                {{ formatQty(sumQtyByQcResult(['LY', 'OK'])) }}
                            </p>
                            <p
                                class="text-xs text-pink-600/70 dark:text-pink-400/70"
                            >
                                {{ countByQcResult(['LY', 'OK']) }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-pink-500/10 p-3 ring-1 ring-pink-500/20"
                        >
                            <Wrench
                                class="h-5 w-5 text-pink-600 dark:text-pink-400"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="rounded-xl border border-border/50 bg-card p-4 shadow-lg"
            >
                <div class="grid gap-4 md:grid-cols-12">
                    <div class="md:col-span-8">
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                            placeholder="Search by Lot No or Model..."
                            @input="fetchRecords"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <select
                            v-model="decisionFilter"
                            class="flex h-12 w-full rounded-lg border border-input bg-background px-4 py-3 text-sm shadow-sm focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:outline-none"
                            @change="fetchRecords"
                        >
                            <option value="">All Decisions</option>
                            <option value="Proceed">Proceed</option>
                            <option value="Rework">Rework</option>
                            <option value="Low Yield">Low Yield</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <button
                            class="inline-flex h-12 w-full items-center justify-center rounded-lg border border-border bg-background px-4 text-sm font-semibold shadow-sm transition-all hover:bg-muted"
                            @click="clearFilters"
                        >
                            <XCircle class="mr-2 h-4 w-4" /> Clear
                        </button>
                    </div>
                </div>
            </div>

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
                    <div v-if="!loading" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead
                                class="border-b border-border/50 bg-gradient-to-r from-muted/30 to-muted/10"
                            >
                                <tr>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Lot No
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        QC NG
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Defect
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Defect Class
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Model
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Qty
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        LIPAS
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Date Time
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Decision
                                    </th>
                                    <th
                                        class="px-4 py-4 text-left font-bold tracking-wide text-foreground uppercase"
                                    >
                                        Elapsed Time
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
                                    v-for="rec in records"
                                    :key="rec.id"
                                    class="transition-colors hover:bg-muted/20"
                                >
                                    <td
                                        class="px-4 py-3 font-mono font-semibold text-primary"
                                    >
                                        {{ rec.lot_id }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="rec.qc_result"
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                            :class="
                                                qcNgBadgeClass(rec.qc_result)
                                            "
                                            >{{ rec.qc_result }}</span
                                        >
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ rec.qc_defect || '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="rec.defect_class"
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                            :class="
                                                rec.defect_class ===
                                                'QC Analysis'
                                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                    : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-950/30 dark:text-yellow-400'
                                            "
                                            >{{ rec.defect_class }}</span
                                        >
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ rec.model || '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{
                                            rec.lot_qty != null
                                                ? formatQty(rec.lot_qty)
                                                : '—'
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="rec.lipas_yn"
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset"
                                            :class="
                                                rec.lipas_yn === 'Y'
                                                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                    : 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-950/30 dark:text-slate-400'
                                            "
                                            >{{ rec.lipas_yn }}</span
                                        >
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xs text-muted-foreground"
                                    >
                                        {{ getDateTime(rec) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="getDecision(rec)"
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                            :class="
                                                decisionBadgeClass(
                                                    getDecision(rec)!,
                                                )
                                            "
                                            >{{ getDecision(rec) }}</span
                                        >
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xs text-muted-foreground"
                                    >
                                        {{
                                            getElapsedTime(rec) != null
                                                ? getElapsedTime(rec) + ' min'
                                                : '—'
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <button
                                                class="flex items-center gap-1.5 rounded-lg border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary transition-all hover:bg-primary/10"
                                                @click="editRecord(rec)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                                Edit
                                            </button>
                                            <button
                                                class="flex items-center gap-1.5 rounded-lg border border-destructive/20 bg-destructive/5 px-3 py-1.5 text-xs font-semibold text-destructive transition-all hover:bg-destructive/10"
                                                @click="confirmDelete(rec)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="records.length === 0">
                                    <td
                                        colspan="11"
                                        class="py-12 text-center text-muted-foreground"
                                    >
                                        <Clock
                                            class="mx-auto h-16 w-16 opacity-30"
                                        />
                                        <p class="mt-3">No records found</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <EndlineDelayEntryModal
            :open="showModal"
            :edit-record="editRecordData"
            @update:open="showModal = $event"
            @saved="fetchRecords"
        />

        <!-- Delete Modal -->
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
                            Are you sure you want to delete record for lot
                            <span class="font-bold text-destructive">{{
                                recordToDelete?.lot_id
                            }}</span
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
                        @click="deleteRecord"
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
import EndlineDelayEntryModal from '@/pages/dashboards/subs/endline-delay-entry-modal.vue';
import axios from 'axios';
import {
    AlertCircle,
    CheckCircle,
    Clock,
    Download,
    Package,
    Pencil,
    PlusCircle,
    RefreshCw,
    Trash2,
    Wrench,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

// Close export picker on outside click
function onClickOutside(e: MouseEvent) {
    const target = e.target as HTMLElement;
    if (!target.closest('.export-picker-wrapper')) {
        showExportPicker.value = false;
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'F2') {
        e.preventDefault();
        openCreateModal();
    }
}

interface EndlineRecord {
    id: number;
    lot_id: string;
    model: string | null;
    lot_qty: number | null;
    lipas_yn: string | null;
    qc_result: string | null; // QC NG: Main | RR | LY | OK
    qc_defect: string | null; // Defect code
    defect_class: string | null; // Analysis | Technical
    qc_ana_start: string | null;
    qc_ana_result: string | null; // Decision when Analysis
    qc_ana_tat: number | null; // ElapsedTime when Analysis
    vi_techl_start: string | null;
    vi_techl_result: string | null; // Decision when Technical
    vi_techl_tat: number | null; // ElapsedTime when Technical
    work_type: string | null;
    final_decision: string | null;
    remarks: string | null;
    created_at: string | null;
    updated_at: string | null;
}

const records = ref<EndlineRecord[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const decisionFilter = ref('');
const filterDate = ref(
    new Date().toLocaleDateString('en-CA', { timeZone: 'Asia/Manila' }),
);
const filterLipas = ref('');
const filterShift = ref('');
const filterCutoff = ref('');
const filterWorktype = ref('');
const filterUnit = ref<'pcs' | 'Kpcs' | 'Mpcs'>(
    (localStorage.getItem('endline_unit') as 'pcs' | 'Kpcs' | 'Mpcs') ?? 'pcs',
);

function setUnit(u: 'pcs' | 'Kpcs' | 'Mpcs') {
    filterUnit.value = u;
    localStorage.setItem('endline_unit', u);
}

function formatQty(qty: number): string {
    if (filterUnit.value === 'Kpcs')
        return (
            (qty / 1000).toLocaleString(undefined, {
                maximumFractionDigits: 1,
            }) + ' Kpcs'
        );
    if (filterUnit.value === 'Mpcs')
        return (
            (qty / 1_000_000).toLocaleString(undefined, {
                maximumFractionDigits: 2,
            }) + ' Mpcs'
        );
    return qty.toLocaleString() + ' pcs';
}

// Export date range picker state
const showExportPicker = ref(false);
const today = new Date().toLocaleDateString('en-CA', {
    timeZone: 'Asia/Manila',
});
const exportDateFrom = ref(today);
const exportDateTo = ref(today);

function triggerExport() {
    const p = new URLSearchParams();
    if (exportDateFrom.value) p.set('date_from', exportDateFrom.value);
    if (exportDateTo.value) p.set('date_to', exportDateTo.value);
    window.location.href = `/api/endline-delay/export?${p.toString()}`;
    showExportPicker.value = false;
}

const exportUrl = computed(() => {
    const p = new URLSearchParams();
    if (searchQuery.value) p.set('search', searchQuery.value);
    if (filterDate.value) p.set('date', filterDate.value);
    if (filterLipas.value) p.set('lipas_yn', filterLipas.value);
    if (filterShift.value) p.set('shift', filterShift.value);
    if (filterCutoff.value) p.set('cutoff', filterCutoff.value);
    if (filterWorktype.value) p.set('work_type', filterWorktype.value);
    const qs = p.toString();
    return `/api/endline-delay/export${qs ? '?' + qs : ''}`;
});

const showModal = ref(false);
const editRecordData = ref<EndlineRecord | null>(null);
const showDeleteModal = ref(false);
const recordToDelete = ref<EndlineRecord | null>(null);
const deleting = ref(false);

// Decision: read directly from final_decision column
function getDecision(rec: EndlineRecord): string | null {
    return rec.final_decision ?? null;
}

// DateTime: from created_at
function getDateTime(rec: EndlineRecord): string {
    if (!rec.created_at) return '—';
    return new Date(rec.created_at).toLocaleString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

// ElapsedTime: current time - updated_at in minutes
function getElapsedTime(rec: EndlineRecord): number | null {
    if (!rec.updated_at) return null;
    const diff = Date.now() - new Date(rec.updated_at).getTime();
    return Math.floor(diff / 60000);
}

const countByDecision = (d: string) =>
    records.value.filter((r) => getDecision(r) === d).length;
const countByLipas = (v: string) =>
    records.value.filter((r) => r.lipas_yn === v).length;
const countByDefectClass = (c: string) =>
    records.value.filter((r) => r.defect_class === c).length;
const sumQtyByDefectClass = (c: string) =>
    records.value
        .filter((r) => r.defect_class === c)
        .reduce((s, r) => s + (r.lot_qty ?? 0), 0);
const countByQcResult = (v: string | string[]) => {
    const vals = Array.isArray(v) ? v : [v];
    return records.value.filter(
        (r) => r.qc_result && vals.includes(r.qc_result),
    ).length;
};
const sumQtyByQcResult = (v: string | string[]) => {
    const vals = Array.isArray(v) ? v : [v];
    return records.value
        .filter((r) => r.qc_result && vals.includes(r.qc_result))
        .reduce((s, r) => s + (r.lot_qty ?? 0), 0);
};
const totalQty = () => records.value.reduce((s, r) => s + (r.lot_qty ?? 0), 0);

function qcNgBadgeClass(type: string) {
    if (type === 'Main')
        return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/30 dark:text-blue-400';
    if (type === 'RR')
        return 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-950/30 dark:text-orange-400';
    if (type === 'LY')
        return 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/30 dark:text-violet-400';
    if (type === 'OK')
        return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400';
    return 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-950/30 dark:text-slate-400';
}

function decisionBadgeClass(d: string) {
    if (d === 'Proceed')
        return 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/30 dark:text-blue-400';
    if (d === 'Rework')
        return 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/30 dark:text-red-400';
    if (d === 'Low Yield')
        return 'bg-violet-50 text-violet-700 ring-violet-600/20 dark:bg-violet-950/30 dark:text-violet-400';
    // Pending / fallback
    return 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-950/30 dark:text-slate-400';
}

onMounted(() => {
    fetchRecords();
    document.addEventListener('click', onClickOutside);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});

async function fetchRecords() {
    loading.value = true;
    try {
        const { data } = await axios.get<EndlineRecord[]>(
            '/api/endline-delay',
            {
                params: {
                    search: searchQuery.value || undefined,
                    date: filterDate.value || undefined,
                    lipas_yn: filterLipas.value || undefined,
                    shift: filterShift.value || undefined,
                    cutoff: filterCutoff.value || undefined,
                    work_type: filterWorktype.value || undefined,
                },
            },
        );
        records.value = decisionFilter.value
            ? data.filter((r) => getDecision(r) === decisionFilter.value)
            : data;
    } catch {
        console.error('Failed to load records.');
    } finally {
        loading.value = false;
    }
}

function clearFilters() {
    searchQuery.value = '';
    decisionFilter.value = '';
    filterDate.value = '';
    filterLipas.value = '';
    filterShift.value = '';
    filterCutoff.value = '';
    filterWorktype.value = '';
    fetchRecords();
}

function openCreateModal() {
    editRecordData.value = null;
    showModal.value = true;
}

function editRecord(rec: EndlineRecord) {
    editRecordData.value = rec;
    showModal.value = true;
}

function confirmDelete(rec: EndlineRecord) {
    recordToDelete.value = rec;
    showDeleteModal.value = true;
}
function closeDeleteModal() {
    showDeleteModal.value = false;
    recordToDelete.value = null;
}

async function deleteRecord() {
    if (!recordToDelete.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/api/endline-delay/${recordToDelete.value.id}`);
        closeDeleteModal();
        fetchRecords();
    } catch {
        console.error('Delete failed.');
    } finally {
        deleting.value = false;
    }
}
</script>
