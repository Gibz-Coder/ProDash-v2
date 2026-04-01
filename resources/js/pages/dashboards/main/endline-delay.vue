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

                <!-- Reset to Defaults -->
                <button
                    class="flex items-center gap-1 rounded-lg border border-violet-600 bg-transparent px-3 py-1.5 text-xs font-medium text-violet-600 shadow-sm transition-colors hover:bg-violet-600 hover:text-white"
                    @click="resetToDefaults"
                >
                    <RotateCcw class="h-3.5 w-3.5" /> Default
                </button>

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

        <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden p-4">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold">QC Entry</h1>
                    <p class="text-muted-foreground">
                        QC inspected lots entry and monitoring
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Wildcard search -->
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted-foreground"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.35-4.35" />
                            </svg>
                        </span>
                        <input
                            v-model="searchQuery"
                            type="text"
                            class="h-10 w-64 rounded-lg border border-input bg-background pr-4 pl-9 text-sm transition-all placeholder:text-muted-foreground/60 focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                            placeholder="Search lot no, model, defect..."
                            @input="fetchRecords"
                        />
                        <button
                            v-if="searchQuery"
                            class="absolute inset-y-0 right-2 flex items-center text-muted-foreground/60 hover:text-muted-foreground"
                            @click="
                                searchQuery = '';
                                fetchRecords();
                            "
                        >
                            <svg
                                class="h-3.5 w-3.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <!-- Decision filter -->
                    <select
                        v-model="decisionFilter"
                        class="h-10 rounded-lg border border-input bg-background px-3 text-sm text-foreground transition-all focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none"
                        @change="fetchRecords"
                    >
                        <option value="">All Decisions</option>
                        <option value="Proceed">Proceed</option>
                        <option value="Rework">Rework</option>
                        <option value="Low Yield">Low Yield</option>
                    </select>
                    <button
                        class="inline-flex items-center rounded-lg bg-gradient-to-r from-primary to-primary/90 px-6 py-2.5 text-sm font-semibold text-primary-foreground shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-primary/25"
                        @click="openCreateModal"
                    >
                        <PlusCircle class="mr-2 h-4 w-4" /> Add Record
                        <span
                            class="ml-2 rounded bg-white/20 px-1.5 py-0.5 font-mono text-xs"
                            >F2</span
                        >
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-6 gap-3">
                <!-- Total -->
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
                    <div
                        class="mt-2 flex gap-2 border-t border-blue-200/50 pt-2 dark:border-blue-800/30"
                    >
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-amber-600 uppercase"
                            >
                                Pending
                            </p>
                            <p class="text-xs font-bold text-amber-700">
                                {{ formatQty(totalBreakdown.pendingQty) }}
                            </p>
                            <p class="text-[9px] text-amber-600/70">
                                {{ totalBreakdown.pendingCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-blue-200/50 dark:bg-blue-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-orange-600 uppercase"
                            >
                                In Prog
                            </p>
                            <p class="text-xs font-bold text-orange-700">
                                {{ formatQty(totalBreakdown.inProgressQty) }}
                            </p>
                            <p class="text-[9px] text-orange-600/70">
                                {{ totalBreakdown.inProgressCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-blue-200/50 dark:bg-blue-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-emerald-600 uppercase"
                            >
                                Done
                            </p>
                            <p class="text-xs font-bold text-emerald-700">
                                {{ formatQty(totalBreakdown.doneQty) }}
                            </p>
                            <p class="text-[9px] text-emerald-600/70">
                                {{ totalBreakdown.doneCount }} lots
                            </p>
                        </div>
                    </div>
                </div>
                <!-- QC OK -->
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-teal-50 to-teal-100/50 p-4 shadow-lg dark:from-teal-950/30 dark:to-teal-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-teal-700 dark:text-teal-300"
                            >
                                QC OK
                            </p>
                            <p
                                class="text-2xl font-bold text-teal-900 dark:text-teal-100"
                            >
                                {{ formatQty(sumQtyByQcBucket(isQcOk)) }}
                            </p>
                            <p
                                class="text-xs text-teal-600/70 dark:text-teal-400/70"
                            >
                                {{ countByQcBucket(isQcOk) }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-teal-500/10 p-3 ring-1 ring-teal-500/20"
                        >
                            <CheckCircle
                                class="h-5 w-5 text-teal-600 dark:text-teal-400"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-2 flex gap-2 border-t border-teal-200/50 pt-2 dark:border-teal-800/30"
                    >
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-amber-600 uppercase"
                            >
                                Pending
                            </p>
                            <p class="text-xs font-bold text-amber-700">
                                {{ formatQty(qcOkBreakdown.pendingQty) }}
                            </p>
                            <p class="text-[9px] text-amber-600/70">
                                {{ qcOkBreakdown.pendingCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-teal-200/50 dark:bg-teal-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-orange-600 uppercase"
                            >
                                In Prog
                            </p>
                            <p class="text-xs font-bold text-orange-700">
                                {{ formatQty(qcOkBreakdown.inProgressQty) }}
                            </p>
                            <p class="text-[9px] text-orange-600/70">
                                {{ qcOkBreakdown.inProgressCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-teal-200/50 dark:bg-teal-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-emerald-600 uppercase"
                            >
                                Done
                            </p>
                            <p class="text-xs font-bold text-emerald-700">
                                {{ formatQty(qcOkBreakdown.doneQty) }}
                            </p>
                            <p class="text-[9px] text-emerald-600/70">
                                {{ qcOkBreakdown.doneCount }} lots
                            </p>
                        </div>
                    </div>
                </div>
                <!-- QC Analysis -->
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-indigo-50 to-indigo-100/50 p-4 shadow-lg dark:from-indigo-950/30 dark:to-indigo-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-indigo-700 dark:text-indigo-300"
                            >
                                QC Analysis
                            </p>
                            <p
                                class="text-2xl font-bold text-indigo-900 dark:text-indigo-100"
                            >
                                {{
                                    formatQty(
                                        sumQtyByDefectClass('QC Analysis'),
                                    )
                                }}
                            </p>
                            <p
                                class="text-xs text-indigo-600/70 dark:text-indigo-400/70"
                            >
                                {{ countByDefectClass('QC Analysis') }} lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-indigo-500/10 p-3 ring-1 ring-indigo-500/20"
                        >
                            <CheckCircle
                                class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-2 flex gap-2 border-t border-indigo-200/50 pt-2 dark:border-indigo-800/30"
                    >
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-amber-600 uppercase"
                            >
                                Pending
                            </p>
                            <p class="text-xs font-bold text-amber-700">
                                {{ formatQty(qcAnalysisBreakdown.pendingQty) }}
                            </p>
                            <p class="text-[9px] text-amber-600/70">
                                {{ qcAnalysisBreakdown.pendingCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-indigo-200/50 dark:bg-indigo-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-orange-600 uppercase"
                            >
                                In Prog
                            </p>
                            <p class="text-xs font-bold text-orange-700">
                                {{
                                    formatQty(qcAnalysisBreakdown.inProgressQty)
                                }}
                            </p>
                            <p class="text-[9px] text-orange-600/70">
                                {{ qcAnalysisBreakdown.inProgressCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-indigo-200/50 dark:bg-indigo-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-emerald-600 uppercase"
                            >
                                Done
                            </p>
                            <p class="text-xs font-bold text-emerald-700">
                                {{ formatQty(qcAnalysisBreakdown.doneQty) }}
                            </p>
                            <p class="text-[9px] text-emerald-600/70">
                                {{ qcAnalysisBreakdown.doneCount }} lots
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Tech'l Verification -->
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-purple-50 to-purple-100/50 p-4 shadow-lg dark:from-purple-950/30 dark:to-purple-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-purple-700 dark:text-purple-300"
                            >
                                Tech'l Verification
                            </p>
                            <p
                                class="text-2xl font-bold text-purple-900 dark:text-purple-100"
                            >
                                {{
                                    formatQty(
                                        sumQtyByDefectClass(
                                            "Tech'l Verification",
                                        ),
                                    )
                                }}
                            </p>
                            <p
                                class="text-xs text-purple-600/70 dark:text-purple-400/70"
                            >
                                {{ countByDefectClass("Tech'l Verification") }}
                                lots
                            </p>
                        </div>
                        <div
                            class="rounded-full bg-purple-500/10 p-3 ring-1 ring-purple-500/20"
                        >
                            <AlertCircle
                                class="h-5 w-5 text-purple-600 dark:text-purple-400"
                            />
                        </div>
                    </div>
                    <div
                        class="mt-2 flex gap-2 border-t border-purple-200/50 pt-2 dark:border-purple-800/30"
                    >
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-amber-600 uppercase"
                            >
                                Pending
                            </p>
                            <p class="text-xs font-bold text-amber-700">
                                {{ formatQty(techVerifBreakdown.pendingQty) }}
                            </p>
                            <p class="text-[9px] text-amber-600/70">
                                {{ techVerifBreakdown.pendingCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-purple-200/50 dark:bg-purple-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-orange-600 uppercase"
                            >
                                In Prog
                            </p>
                            <p class="text-xs font-bold text-orange-700">
                                {{
                                    formatQty(techVerifBreakdown.inProgressQty)
                                }}
                            </p>
                            <p class="text-[9px] text-orange-600/70">
                                {{ techVerifBreakdown.inProgressCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-purple-200/50 dark:bg-purple-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-emerald-600 uppercase"
                            >
                                Done
                            </p>
                            <p class="text-xs font-bold text-emerald-700">
                                {{ formatQty(techVerifBreakdown.doneQty) }}
                            </p>
                            <p class="text-[9px] text-emerald-600/70">
                                {{ techVerifBreakdown.doneCount }} lots
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Mainlot -->
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
                                {{ formatQty(sumQtyByQcBucket(isMainlot)) }}
                            </p>
                            <p
                                class="text-xs text-violet-600/70 dark:text-violet-400/70"
                            >
                                {{ countByQcBucket(isMainlot) }} lots
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
                    <div
                        class="mt-2 flex gap-2 border-t border-violet-200/50 pt-2 dark:border-violet-800/30"
                    >
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-amber-600 uppercase"
                            >
                                Pending
                            </p>
                            <p class="text-xs font-bold text-amber-700">
                                {{ formatQty(mainlotBreakdown.pendingQty) }}
                            </p>
                            <p class="text-[9px] text-amber-600/70">
                                {{ mainlotBreakdown.pendingCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-violet-200/50 dark:bg-violet-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-orange-600 uppercase"
                            >
                                In Prog
                            </p>
                            <p class="text-xs font-bold text-orange-700">
                                {{ formatQty(mainlotBreakdown.inProgressQty) }}
                            </p>
                            <p class="text-[9px] text-orange-600/70">
                                {{ mainlotBreakdown.inProgressCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-violet-200/50 dark:bg-violet-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-emerald-600 uppercase"
                            >
                                Done
                            </p>
                            <p class="text-xs font-bold text-emerald-700">
                                {{ formatQty(mainlotBreakdown.doneQty) }}
                            </p>
                            <p class="text-[9px] text-emerald-600/70">
                                {{ mainlotBreakdown.doneCount }} lots
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Rework (R-Rework + L-Rework) -->
                <div
                    class="relative overflow-hidden rounded-xl border border-border/50 bg-gradient-to-br from-orange-50 to-orange-100/50 p-4 shadow-lg dark:from-orange-950/30 dark:to-orange-900/20"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-xs font-medium text-orange-700 dark:text-orange-300"
                            >
                                RL - Rework
                            </p>
                            <p
                                class="text-2xl font-bold text-orange-900 dark:text-orange-100"
                            >
                                {{ formatQty(sumQtyByQcBucket(isRework)) }}
                            </p>
                            <p
                                class="text-xs text-orange-600/70 dark:text-orange-400/70"
                            >
                                {{ countByQcBucket(isRework) }} lots
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
                    <div
                        class="mt-2 flex gap-2 border-t border-orange-200/50 pt-2 dark:border-orange-800/30"
                    >
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-amber-600 uppercase"
                            >
                                Pending
                            </p>
                            <p class="text-xs font-bold text-amber-700">
                                {{ formatQty(reworkBreakdown.pendingQty) }}
                            </p>
                            <p class="text-[9px] text-amber-600/70">
                                {{ reworkBreakdown.pendingCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-orange-200/50 dark:bg-orange-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-orange-600 uppercase"
                            >
                                In Prog
                            </p>
                            <p class="text-xs font-bold text-orange-700">
                                {{ formatQty(reworkBreakdown.inProgressQty) }}
                            </p>
                            <p class="text-[9px] text-orange-600/70">
                                {{ reworkBreakdown.inProgressCount }} lots
                            </p>
                        </div>
                        <div
                            class="w-px bg-orange-200/50 dark:bg-orange-800/30"
                        ></div>
                        <div class="flex-1 text-center">
                            <p
                                class="text-[9px] font-semibold text-emerald-600 uppercase"
                            >
                                Done
                            </p>
                            <p class="text-xs font-bold text-emerald-700">
                                {{ formatQty(reworkBreakdown.doneQty) }}
                            </p>
                            <p class="text-[9px] text-emerald-600/70">
                                {{ reworkBreakdown.doneCount }} lots
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div
                class="flex min-h-0 flex-1 flex-col rounded-xl border border-border/50 bg-card shadow-lg"
            >
                <div v-if="loading" class="flex justify-center py-10">
                    <div
                        class="h-7 w-7 animate-spin rounded-full border-4 border-primary border-r-transparent"
                        role="status"
                    >
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div v-else class="min-h-0 flex-1 overflow-auto">
                    <table class="w-full min-w-[1100px] table-fixed text-xs">
                        <colgroup>
                            <col class="w-[40px]" />
                            <!-- No. -->
                            <col class="w-[100px]" />
                            <!-- Lot No -->
                            <col class="w-[60px]" />
                            <!-- QC NG -->
                            <col class="w-[70px]" />
                            <!-- Defect -->
                            <col class="w-[130px]" />
                            <!-- Defect Class -->
                            <col class="w-[130px]" />
                            <!-- Model -->
                            <col class="w-[90px]" />
                            <!-- Qty -->
                            <col class="w-[56px]" />
                            <!-- LIPAS -->
                            <col class="w-[100px]" />
                            <!-- WorkType -->
                            <col class="w-[120px]" />
                            <!-- Date Time -->
                            <col class="w-[90px]" />
                            <!-- Decision -->
                            <col class="w-[90px]" />
                            <!-- Elapsed -->
                            <col class="w-[110px]" />
                            <!-- Created By -->
                            <col class="w-[100px]" />
                            <!-- Updated By -->
                            <col class="w-[110px]" />
                            <!-- Actions -->
                        </colgroup>
                        <thead class="sticky top-0 z-10">
                            <tr
                                class="via-slate-750 dark:via-slate-850 bg-gradient-to-r from-slate-700 to-slate-800 dark:from-slate-800 dark:to-slate-900"
                            >
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-center text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    No.
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Lot No
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    QC NG
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Defect
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Defect Class
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
                                    WorkType
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Date Time
                                </th>
                                <th
                                    class="border-r border-white/10 px-2 py-2.5 text-left text-[10px] font-bold tracking-widest text-slate-100 uppercase"
                                >
                                    Decision
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
                                v-for="(rec, index) in records"
                                :key="rec.id"
                                class="transition-colors hover:bg-muted/30"
                            >
                                <td
                                    class="px-2 py-1.5 text-center text-xs font-medium text-muted-foreground"
                                >
                                    {{ index + 1 }}
                                </td>
                                <td
                                    class="px-2 py-1.5 font-mono text-xs font-semibold text-primary"
                                >
                                    {{ rec.lot_id }}
                                </td>
                                <td class="px-2 py-1.5">
                                    <span
                                        v-if="rec.qc_result"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="qcNgBadgeClass(rec.qc_result)"
                                        >{{ rec.qc_result }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs text-foreground"
                                    :title="rec.qc_defect ?? ''"
                                >
                                    {{ rec.qc_defect || '—' }}
                                </td>
                                <td class="px-2 py-1.5">
                                    <span
                                        v-if="rec.defect_class"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            rec.defect_class === 'QC Analysis'
                                                ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20 dark:bg-indigo-950/30 dark:text-indigo-400'
                                                : 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-950/30 dark:text-purple-400'
                                        "
                                        >{{ rec.defect_class }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs text-foreground"
                                    :title="rec.model ?? ''"
                                >
                                    {{ rec.model || '—' }}
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs font-medium text-foreground"
                                >
                                    {{
                                        rec.lot_qty != null
                                            ? formatQty(rec.lot_qty)
                                            : '—'
                                    }}
                                </td>
                                <td class="px-2 py-1.5">
                                    <span
                                        v-if="rec.lipas_yn"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            rec.lipas_yn === 'Y'
                                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/30 dark:text-emerald-400'
                                                : 'bg-slate-50 text-slate-600 ring-slate-600/20 dark:bg-slate-950/30 dark:text-slate-400'
                                        "
                                        >{{ rec.lipas_yn }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs text-foreground"
                                    :title="rec.work_type ?? ''"
                                >
                                    {{ rec.work_type || '—' }}
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs whitespace-nowrap text-muted-foreground"
                                >
                                    {{ getDateTime(rec) }}
                                </td>
                                <td class="px-2 py-1.5">
                                    <span
                                        v-if="getDecision(rec)"
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                        :class="
                                            decisionBadgeClass(
                                                getDecision(rec)!,
                                            )
                                        "
                                        >{{ getDecision(rec) }}</span
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="px-2 py-1.5 text-xs whitespace-nowrap text-amber-600 dark:text-amber-400"
                                >
                                    {{
                                        getElapsedTime(rec) != null
                                            ? formatDuration(
                                                  getElapsedTime(rec)!,
                                              )
                                            : '—'
                                    }}
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs text-muted-foreground"
                                    :title="rec.created_by ?? ''"
                                >
                                    {{ rec.created_by || '—' }}
                                </td>
                                <td
                                    class="truncate px-2 py-1.5 text-xs text-muted-foreground"
                                    :title="rec.updated_by ?? ''"
                                >
                                    {{ rec.updated_by || '—' }}
                                </td>
                                <td class="px-2 py-1.5">
                                    <div
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <button
                                            class="h-6 rounded border border-primary/20 bg-primary/5 px-2 text-[10px] font-semibold text-primary hover:bg-primary/10"
                                            @click="editRecord(rec)"
                                        >
                                            <Pencil class="inline h-3 w-3" />
                                            Edit
                                        </button>
                                        <button
                                            v-if="canDeleteEndline"
                                            class="h-6 rounded border border-destructive/20 bg-destructive/5 px-2 text-[10px] font-semibold text-destructive hover:bg-destructive/10"
                                            @click="confirmDelete(rec)"
                                        >
                                            <Trash2 class="inline h-3 w-3" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="records.length === 0">
                                <td
                                    colspan="14"
                                    class="py-10 text-center text-muted-foreground"
                                >
                                    <Clock
                                        class="mx-auto h-12 w-12 opacity-30"
                                    />
                                    <p class="mt-2 text-xs">No records found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
import { usePage } from '@inertiajs/vue3';
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
    RotateCcw,
    Trash2,
    X,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const canDeleteEndline = computed(
    () =>
        (page.props.auth as any)?.permissions?.includes('Delete Endline') ??
        false,
);

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
    qc_result: string | null;
    qc_defect: string | null;
    defect_class: string | null;
    qc_ana_start: string | null;
    qc_ana_result: string | null;
    qc_ana_completed_at: string | null;
    vi_techl_start: string | null;
    vi_techl_result: string | null;
    vi_techl_completed_at: string | null;
    work_type: string | null;
    final_decision: string | null;
    output_status: string | null;
    lot_completed_at: string | null;
    remarks: string | null;
    inspection_times: number | null;
    updated_by: string | null;
    created_by: string | null;
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
        return (qty / 1000).toLocaleString(undefined, {
            maximumFractionDigits: 1,
        });
    if (filterUnit.value === 'Mpcs')
        return (qty / 1_000_000).toLocaleString(undefined, {
            maximumFractionDigits: 2,
        });
    return qty.toLocaleString();
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

function formatDuration(minutes: number): string {
    if (minutes < 60) return `${minutes} min`;
    const days = Math.floor(minutes / 1440);
    const hours = Math.floor((minutes % 1440) / 60);
    const mins = minutes % 60;
    if (days > 0) return `${days}d ${hours}h ${mins}m`;
    return `${hours}h ${mins}m`;
}

// ElapsedTime: now - created_at for active lots; lot_completed_at - created_at for finalized
function getElapsedTime(rec: EndlineRecord): number | null {
    if (!rec.created_at) return null;
    const start = new Date(rec.created_at).getTime();
    const isFinalized =
        rec.output_status === 'Completed' || rec.output_status === 'Rework';
    const freezeAt =
        rec.lot_completed_at ?? (isFinalized ? rec.updated_at : null);
    const end = freezeAt ? new Date(freezeAt).getTime() : Date.now();
    return Math.floor((end - start) / 60000);
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

// Bucket helpers based on qc_result combinations
function isMainlot(r: EndlineRecord): boolean {
    return !!(r.qc_result && r.qc_result.includes('Main'));
}
function isRRework(r: EndlineRecord): boolean {
    return !!(
        r.qc_result &&
        r.qc_result.includes('RR') &&
        !r.qc_result.includes('Main')
    );
}
function isLRework(r: EndlineRecord): boolean {
    return !!(
        r.qc_result &&
        r.qc_result.includes('LY') &&
        !r.qc_result.includes('RR') &&
        !r.qc_result.includes('Main')
    );
}

const countByQcBucket = (fn: (r: EndlineRecord) => boolean) =>
    records.value.filter(fn).length;
const sumQtyByQcBucket = (fn: (r: EndlineRecord) => boolean) =>
    records.value.filter(fn).reduce((s, r) => s + (r.lot_qty ?? 0), 0);
const totalQty = () => records.value.reduce((s, r) => s + (r.lot_qty ?? 0), 0);

// Output status breakdown helper — generic for overall/mainlot/rework/qcok cards
function statusBreakdown(recs: EndlineRecord[]) {
    const pending = recs.filter(
        (r) =>
            r.output_status === 'Pending' &&
            r.final_decision !== 'In Progress' &&
            r.final_decision !== 'Technical',
    );
    const inProgress = recs.filter(
        (r) =>
            r.output_status !== 'Completed' &&
            r.output_status !== 'Rework' &&
            (r.final_decision === 'In Progress' ||
                r.final_decision === 'Technical'),
    );
    const done = recs.filter(
        (r) => r.output_status === 'Completed' || r.output_status === 'Rework',
    );
    return {
        pendingCount: pending.length,
        pendingQty: pending.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        inProgressCount: inProgress.length,
        inProgressQty: inProgress.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        doneCount: done.length,
        doneQty: done.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    };
}

// Breakdown for QC Analysis card — uses qc_ana_* fields
function qcAnalysisStageBreakdown(recs: EndlineRecord[]) {
    const done = recs.filter((r) => !!r.qc_ana_result);
    const inProgress = recs.filter((r) => !r.qc_ana_result && !!r.qc_ana_start);
    const pending = recs.filter((r) => !r.qc_ana_result && !r.qc_ana_start);
    return {
        pendingCount: pending.length,
        pendingQty: pending.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        inProgressCount: inProgress.length,
        inProgressQty: inProgress.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        doneCount: done.length,
        doneQty: done.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    };
}

// Breakdown for VI Technical card — uses vi_techl_* fields
function viTechnicalStageBreakdown(recs: EndlineRecord[]) {
    const done = recs.filter((r) => !!r.vi_techl_result);
    const inProgress = recs.filter(
        (r) => !r.vi_techl_result && !!r.vi_techl_start,
    );
    const pending = recs.filter((r) => !r.vi_techl_result && !r.vi_techl_start);
    return {
        pendingCount: pending.length,
        pendingQty: pending.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        inProgressCount: inProgress.length,
        inProgressQty: inProgress.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        doneCount: done.length,
        doneQty: done.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    };
}

const totalBreakdown = computed(() => statusBreakdown(records.value));
const qcAnalysisBreakdown = computed(() =>
    qcAnalysisStageBreakdown(
        records.value.filter((r) => r.defect_class === 'QC Analysis'),
    ),
);
const techVerifBreakdown = computed(() =>
    viTechnicalStageBreakdown(
        records.value.filter((r) => r.defect_class === "Tech'l Verification"),
    ),
);
const mainlotBreakdown = computed(() =>
    statusBreakdown(records.value.filter(isMainlot)),
);

function isRework(r: EndlineRecord): boolean {
    return isRRework(r) || isLRework(r);
}
function isQcOk(r: EndlineRecord): boolean {
    return (
        r.qc_result === 'OK' ||
        r.qc_ana_result === 'Proceed' ||
        r.vi_techl_result === 'Proceed'
    );
}

const reworkBreakdown = computed(() =>
    statusBreakdown(records.value.filter(isRework)),
);
const qcOkBreakdown = computed(() => {
    const recs = records.value.filter(isQcOk);
    const done = recs.filter(
        (r) => r.output_status === 'Completed' || r.output_status === 'Rework',
    );
    const inProgress = recs.filter(
        (r) =>
            r.output_status !== 'Completed' &&
            r.output_status !== 'Rework' &&
            (r.final_decision === 'In Progress' ||
                r.final_decision === 'Technical'),
    );
    const pending = recs.filter(
        (r) =>
            r.output_status !== 'Completed' &&
            r.output_status !== 'Rework' &&
            r.final_decision !== 'In Progress' &&
            r.final_decision !== 'Technical',
    );
    return {
        pendingCount: pending.length,
        pendingQty: pending.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        inProgressCount: inProgress.length,
        inProgressQty: inProgress.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
        doneCount: done.length,
        doneQty: done.reduce((s, r) => s + (r.lot_qty ?? 0), 0),
    };
});

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
    if (localStorage.getItem('endline_use_defaults') === '1') {
        const h = manilaHour();
        filterDate.value = new Date().toLocaleDateString('en-CA', {
            timeZone: 'Asia/Manila',
        });
        filterShift.value = h >= 7 && h < 19 ? 'DAY' : 'NIGHT';
        filterCutoff.value = currentCutoff();
        filterWorktype.value = 'NORMAL';
        filterLipas.value = '';
        setUnit('pcs');
    }
    fetchRecords();
    document.addEventListener('click', onClickOutside);
    document.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});

function manilaHour(): number {
    return parseInt(
        new Date().toLocaleString('en-US', {
            timeZone: 'Asia/Manila',
            hour: 'numeric',
            hour12: false,
        }),
        10,
    );
}

function currentCutoff(): string {
    const h = manilaHour();
    if (h < 4) return '00:00~03:59';
    if (h < 7) return '04:00~06:59';
    if (h < 12) return '07:00~11:59';
    if (h < 16) return '12:00~15:59';
    if (h < 19) return '16:00~18:59';
    return '19:00~23:59';
}

function resetToDefaults() {
    const h = manilaHour();
    filterDate.value = new Date().toLocaleDateString('en-CA', {
        timeZone: 'Asia/Manila',
    });
    filterShift.value = h >= 7 && h < 19 ? 'DAY' : 'NIGHT';
    filterCutoff.value = currentCutoff();
    filterWorktype.value = 'NORMAL';
    filterLipas.value = '';
    setUnit('pcs');
    // Persist the intent so page refresh restores defaults
    localStorage.setItem('endline_use_defaults', '1');
    fetchRecords();
}

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
    localStorage.removeItem('endline_use_defaults');
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
