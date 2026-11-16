<template>
    <div class="space-y-6">
        <!-- Print Header (For Official Reconciliation PDF Report) -->
        <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-extrabold uppercase">LAPORAN RESMI REKONSILIASI INVENTARIS ASET KSO RUMAH SAKIT</h1>
                    <p class="text-xs text-slate-700">RSUD Majenang &mdash; Sistem Informasi Manajemen Aset Teknologi Informasi</p>
                </div>
                <div class="text-right text-xs font-mono">
                    <div>No. Rekonsiliasi: {{ activeSession?.reconciliation_number || '-' }}</div>
                    <div>Mitra KSO: {{ activeSession?.partner_name || '-' }} ({{ activeSession?.period || '-' }})</div>
                    <div>Tanggal Cetak: {{ new Date().toLocaleDateString('id-ID') }}</div>
                </div>
            </div>
        </div>

        <!-- Top Action Banner -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 sm:p-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4 no-print">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400 shrink-0">
                    <GitCompare class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base font-extrabold text-white">KSO Asset Reconciliation</h2>
                        <span
                            v-if="activeSession"
                            class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-purple-500/15 text-purple-300 border border-purple-500/30"
                        >
                            {{ activeSession.reconciliation_number }} &bull; {{ activeSession.partner_name }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Pencocokan &amp; rekonsiliasi inventaris perangkat KSO rumah sakit terhadap daftar resmi mitra KSO berdasarkan Serial Number &amp; Model.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                <!-- Session Selector -->
                <select
                    v-if="sessions.length"
                    v-model="selectedSessionId"
                    @change="fetchData()"
                    class="col-span-2 sm:col-span-1 bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-cyan-300 font-mono font-bold"
                >
                    <option v-for="s in sessions" :key="s.id" :value="s.id">
                        {{ s.reconciliation_number }} — {{ s.partner_name }} ({{ s.period }})
                    </option>
                </select>

                <button
                    @click="exportOfficialExcel"
                    :disabled="!activeSession"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-xs font-semibold text-emerald-300 disabled:opacity-40 transition"
                >
                    <Download class="w-4 h-4 shrink-0" />
                    <span>Export Excel</span>
                </button>

                <button
                    @click="printOfficialPdf"
                    :disabled="!activeSession"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-navy-950 hover:bg-slate-800 border border-slate-700 text-xs font-semibold text-slate-200 disabled:opacity-40 transition"
                >
                    <Printer class="w-4 h-4 shrink-0" />
                    <span>Export / Cetak PDF</span>
                </button>

                <button
                    @click="showImportModal = true"
                    class="col-span-2 sm:col-span-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-400 hover:to-indigo-500 text-white font-extrabold text-xs shadow-lg shadow-purple-500/25 transition"
                >
                    <Upload class="w-4 h-4 stroke-[2.5] shrink-0" />
                    <span>Import Data KSO Baru</span>
                </button>
            </div>
        </div>

        <!-- 7 Kartu Statistik Utama (Exact Spec Section 1 - Dinamis dari Hasil Rekonsiliasi) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3">
            <!-- 1. Total aset dalam database aplikasi -->
            <div
                @click="setFilterStatus('')"
                class="bg-navy-900/90 border border-slate-700/80 hover:border-cyan-500/40 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">1. Aset DB Sistem</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-white mt-1">{{ stats.total_system_assets }}</div>
                <div class="text-[10px] text-slate-400 mt-1">Unit KSO di Aplikasi</div>
            </div>

            <!-- 2. Total aset dari data mitra KSO -->
            <div
                @click="setFilterStatus('')"
                class="bg-navy-900/90 border border-purple-500/30 hover:border-purple-400/60 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-purple-300">2. Data Mitra KSO</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-purple-400 mt-1">{{ stats.total_kso_items }}</div>
                <div class="text-[10px] text-purple-300/80 mt-1">Baris File Import</div>
            </div>

            <!-- 3. Aset cocok (Match) -->
            <div
                @click="setFilterStatus('MATCH')"
                class="bg-navy-900/90 border border-emerald-500/30 hover:border-emerald-400/60 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-300">3. Aset Cocok</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-emerald-400 mt-1">{{ stats.match_count }}</div>
                <div class="text-[10px] text-emerald-300/80 mt-1">MATCH (SN &amp; Model)</div>
            </div>

            <!-- 4. Aset belum terdaftar (Missing in System) -->
            <div
                @click="setFilterStatus('MISSING IN SYSTEM')"
                class="bg-navy-900/90 border border-cyan-500/30 hover:border-cyan-400/60 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-cyan-300">4. Belum Terdaftar</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-cyan-400 mt-1">{{ stats.missing_in_system_count }}</div>
                <div class="text-[10px] text-cyan-300/80 mt-1">MISSING IN SYSTEM</div>
            </div>

            <!-- 5. Aset tidak ditemukan di data KSO (Missing in KSO) -->
            <div
                @click="setFilterStatus('MISSING IN KSO')"
                class="bg-navy-900/90 border border-indigo-500/30 hover:border-indigo-400/60 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-indigo-300">5. Tidak Ada di KSO</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-indigo-400 mt-1">{{ stats.missing_in_kso_count }}</div>
                <div class="text-[10px] text-indigo-300/80 mt-1">MISSING IN KSO</div>
            </div>

            <!-- 6. Aset dengan perbedaan data (Data Mismatch) -->
            <div
                @click="setFilterStatus('MODEL MISMATCH')"
                class="bg-navy-900/90 border border-amber-500/30 hover:border-amber-400/60 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-300">6. Perbedaan Data</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-amber-400 mt-1">{{ stats.data_mismatch_count }}</div>
                <div class="text-[10px] text-amber-300/80 mt-1">MODEL MISMATCH</div>
            </div>

            <!-- 7. Aset dengan Serial Number duplikat -->
            <div
                @click="setFilterStatus('DUPLICATE SN')"
                class="col-span-2 sm:col-span-1 bg-navy-900/90 border border-rose-500/30 hover:border-rose-400/60 rounded-2xl p-3.5 cursor-pointer transition"
            >
                <div class="text-[10px] font-bold uppercase tracking-wider text-rose-300">7. SN Duplikat</div>
                <div class="text-xl sm:text-2xl font-mono font-extrabold text-rose-400 mt-1">{{ stats.duplicate_sn_count }}</div>
                <div class="text-[10px] text-rose-300/80 mt-1">DUPLICATE SN</div>
            </div>
        </div>

        <!-- Sub-Navigasi Modul KSO Reconciliation -->
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3 no-print">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="activeTab = 'results'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition border"
                    :class="activeTab === 'results'
                        ? 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40'
                        : 'bg-navy-900 text-slate-400 hover:text-white border-slate-800'"
                >
                    <CheckSquare class="w-4 h-4" />
                    <span>1. Hasil Rekonsiliasi ({{ results.length }})</span>
                </button>

                <button
                    @click="activeTab = 'history'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition border"
                    :class="activeTab === 'history'
                        ? 'bg-purple-500/20 text-purple-300 border-purple-500/40'
                        : 'bg-navy-900 text-slate-400 hover:text-white border-slate-800'"
                >
                    <History class="w-4 h-4" />
                    <span>2. History Reconciliation ({{ sessions.length }})</span>
                </button>

                <button
                    @click="activeTab = 'report'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition border"
                    :class="activeTab === 'report'
                        ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
                        : 'bg-navy-900 text-slate-400 hover:text-white border-slate-800'"
                >
                    <FileText class="w-4 h-4" />
                    <span>3. Laporan Resmi Rekonsiliasi</span>
                </button>
            </div>

            <!-- Quick Batch Actions for Active Session -->
            <div v-if="activeSession && activeTab === 'results'" class="flex flex-wrap items-center gap-2">
                <button
                    v-if="stats.match_count > 0"
                    @click="verifyAllMatches"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 font-bold text-xs transition"
                >
                    <CheckCircle2 class="w-3.5 h-3.5" />
                    <span>Verifikasi Semua MATCH</span>
                </button>
                <button
                    v-if="activeSession.status !== 'Selesai'"
                    @click="finalizeSession"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 font-bold text-xs transition"
                >
                    <span>Kunci &amp; Selesaikan Sesi</span>
                </button>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 1: HASIL REKONSILIASI INTERAKTIF (Section 4)          -->
        <!-- ========================================================= -->
        <div v-if="activeTab === 'results'" class="space-y-4">
            <!-- Status Filter Pills & Search Bar -->
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 space-y-3 no-print">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="st in statusTabs"
                        :key="st.value"
                        @click="setFilterStatus(st.value)"
                        class="px-3 py-1.5 rounded-xl text-[11px] font-bold border transition"
                        :class="filters.status === st.value
                            ? 'bg-cyan-500 text-slate-950 border-cyan-400'
                            : 'bg-navy-950 text-slate-300 border-slate-800 hover:border-slate-700'"
                    >
                        {{ st.label }}
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 pt-2 border-t border-slate-800/80">
                    <div class="sm:col-span-6 relative">
                        <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="filters.search"
                            @input="debouncedFetch"
                            type="text"
                            placeholder="Cari Serial Number (SN), Model, Kode Aset AST-XXXX, atau Lokasi..."
                            class="w-full bg-navy-950 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                        />
                    </div>
                    <div class="sm:col-span-4">
                        <select
                            v-model="filters.action_status"
                            @change="fetchData()"
                            class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200"
                        >
                            <option value="">Semua Status Tindakan</option>
                            <option value="Belum Ditindaklanjuti">Belum Ditindaklanjuti</option>
                            <option value="Terverifikasi">Terverifikasi</option>
                            <option value="Data Sistem Dipertahankan">Data Sistem Dipertahankan</option>
                            <option value="Data Sistem Diperbarui">Data Sistem Diperbarui</option>
                            <option value="Ditambahkan ke Asset Directory">Ditambahkan ke Asset Directory</option>
                            <option value="Perlu Konfirmasi Mitra KSO">Perlu Konfirmasi Mitra KSO</option>
                            <option value="Ditinjau Manual">Ditinjau Manual</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <button
                            @click="resetFilters"
                            class="w-full py-2 px-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-300"
                        >
                            Reset Filter
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Card View (< 768px) -->
            <div class="md:hidden space-y-3 no-print">
                <div v-if="!results.length" class="bg-navy-900 border border-slate-800 rounded-2xl p-8 text-center text-xs text-slate-400">
                    Belum ada hasil rekonsiliasi yang sesuai filter.
                </div>
                <div
                    v-for="row in results"
                    :key="`mob-rec-${row.id}`"
                    @click="openComparison(row)"
                    class="bg-navy-900/95 border border-slate-800/90 rounded-2xl p-4 space-y-3 text-xs shadow-lg cursor-pointer"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-slate-400 font-bold">#{{ row.row_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border" :class="statusBadgeClass(row.status)">
                            {{ row.status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5 p-2.5 rounded-xl bg-navy-950 border border-slate-800/80">
                        <div>
                            <span class="text-[10px] text-purple-300 font-bold block">SN &amp; Model KSO</span>
                            <span class="font-mono font-bold text-white block">{{ row.kso_sn || '-' }}</span>
                            <span class="text-[11px] text-slate-300 truncate block">{{ row.kso_model || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-cyan-300 font-bold block">SN &amp; Model Sistem</span>
                            <span class="font-mono font-bold text-cyan-300 block">{{ row.system_sn || '-' }}</span>
                            <span class="text-[11px] text-slate-300 truncate block">{{ row.system_model || '-' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Lokasi: <strong class="text-slate-200">{{ row.system_location || row.kso_location || '-' }}</strong></span>
                        <span class="text-cyan-400 font-bold">Bandingkan &rarr;</span>
                    </div>
                </div>
            </div>

            <!-- Tabel Interaktif Hasil Rekonsiliasi (Exact 9 Columns from Section 4) -->
            <div class="hidden md:block bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-navy-950/90 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                                <th class="py-3.5 px-3">No</th>
                                <th class="py-3.5 px-3">Serial Number KSO</th>
                                <th class="py-3.5 px-3">Model KSO</th>
                                <th class="py-3.5 px-3">Serial Number Sistem</th>
                                <th class="py-3.5 px-3">Model Sistem</th>
                                <th class="py-3.5 px-3">Kategori</th>
                                <th class="py-3.5 px-3">Lokasi</th>
                                <th class="py-3.5 px-3">Status Rekonsiliasi</th>
                                <th class="py-3.5 px-3 text-right no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/70">
                            <tr v-if="loading">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    Memuat data hasil rekonsiliasi KSO...
                                </td>
                            </tr>
                            <tr v-else-if="!results.length">
                                <td colspan="9" class="py-12 text-center text-slate-400">
                                    Tidak ada baris hasil rekonsiliasi yang sesuai filter.
                                </td>
                            </tr>
                            <tr
                                v-for="row in results"
                                :key="row.id"
                                @click="openComparison(row)"
                                class="hover:bg-slate-800/40 cursor-pointer transition"
                            >
                                <td class="py-3 px-3 font-mono text-slate-400">{{ row.row_number }}</td>
                                <td class="py-3 px-3 font-mono font-bold" :class="row.kso_sn ? 'text-purple-300' : 'text-slate-500'">
                                    {{ row.kso_sn || '-' }}
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-white">{{ row.kso_model || '-' }}</div>
                                    <div v-if="row.kso_brand" class="text-[10px] text-slate-400">{{ row.kso_brand }}</div>
                                </td>
                                <td class="py-3 px-3 font-mono font-bold" :class="row.system_sn ? 'text-cyan-300' : 'text-slate-500'">
                                    <div>{{ row.system_sn || '-' }}</div>
                                    <div v-if="row.system_asset_code" class="text-[10px] text-slate-400">{{ row.system_asset_code }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold" :class="row.status === 'MODEL MISMATCH' ? 'text-amber-300' : 'text-slate-200'">
                                        {{ row.system_model || '-' }}
                                    </div>
                                    <div v-if="row.system_name && row.system_name !== row.system_model" class="text-[10px] text-slate-400">
                                        {{ row.system_name }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-300">
                                    {{ row.system_category || row.kso_category || '-' }}
                                </td>
                                <td class="py-3 px-3 text-slate-300">
                                    <div>{{ row.system_location || row.kso_location || '-' }}</div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border inline-block"
                                        :class="statusBadgeClass(row.status)"
                                    >
                                        {{ row.status }}
                                    </span>
                                    <div class="text-[10px] mt-1" :class="row.action_status === 'Belum Ditindaklanjuti' ? 'text-slate-500' : 'text-emerald-400 font-semibold'">
                                        {{ row.action_status }}
                                    </div>
                                </td>
                                <td class="py-3 px-3 whitespace-nowrap text-right no-print" @click.stop>
                                    <button
                                        @click="openComparison(row)"
                                        class="px-3 py-1.5 rounded-xl bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 font-bold text-[11px] transition"
                                    >
                                        Detail &amp; Tindakan
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 2: RIWAYAT REKONSILIASI / HISTORY (Section 7)         -->
        <!-- ========================================================= -->
        <div v-else-if="activeTab === 'history'" class="space-y-6">
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 space-y-4">
                <div>
                    <h3 class="text-sm font-extrabold text-white">
                        Daftar Snapshot Riwayat Proses Rekonsiliasi KSO
                    </h3>
                    <p class="text-xs text-slate-400">
                        Setiap proses rekonsiliasi disimpan sebagai snapshot permanen agar hasil rekonsiliasi terdahulu tidak berubah ketika database aset diperbarui.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-navy-950 border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                                <th class="py-3 px-3">Nomor Rekonsiliasi</th>
                                <th class="py-3 px-3">Nama File KSO</th>
                                <th class="py-3 px-3">Mitra KSO &amp; Periode</th>
                                <th class="py-3 px-3">Tanggal Import</th>
                                <th class="py-3 px-3 text-center">Aset KSO</th>
                                <th class="py-3 px-3 text-center">Aset Sistem</th>
                                <th class="py-3 px-3 text-center">Match</th>
                                <th class="py-3 px-3 text-center">Mismatch</th>
                                <th class="py-3 px-3 text-center">Missing Sys</th>
                                <th class="py-3 px-3 text-center">Missing KSO</th>
                                <th class="py-3 px-3 text-center">Duplicate</th>
                                <th class="py-3 px-3">Petugas</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/70">
                            <tr v-for="s in sessions" :key="s.id" class="hover:bg-slate-800/35">
                                <td class="py-3 px-3 font-mono font-bold text-purple-300">{{ s.reconciliation_number }}</td>
                                <td class="py-3 px-3 font-medium text-white">{{ s.file_name }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-cyan-300">{{ s.partner_name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ s.period }}</div>
                                </td>
                                <td class="py-3 px-3 font-mono text-[11px] text-slate-300">{{ formatDateTime(s.created_at) }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-white">{{ s.total_kso_items }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-slate-300">{{ s.total_system_assets }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-emerald-400">{{ s.match_count }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-amber-400">{{ s.model_mismatch_count }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-cyan-400">{{ s.missing_in_system_count }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-indigo-400">{{ s.missing_in_kso_count }}</td>
                                <td class="py-3 px-3 text-center font-mono font-bold text-rose-400">{{ s.duplicate_sn_count }}</td>
                                <td class="py-3 px-3 text-slate-300">{{ s.creator?.name || 'Administrator' }}</td>
                                <td class="py-3 px-3">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="s.status === 'Selesai'
                                            ? 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'
                                            : 'bg-amber-500/15 text-amber-300 border-amber-500/30'"
                                    >
                                        {{ s.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <button
                                        @click="switchSession(s.id)"
                                        class="px-3 py-1 rounded-lg bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 font-bold text-[11px]"
                                    >
                                        Buka Snapshot
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audit Log Tindakan & Koreksi Rekonsiliasi (Section 9 & 10) -->
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 space-y-4">
                <h3 class="text-sm font-extrabold text-white">
                    Log Audit Tindakan &amp; Koreksi Rekonsiliasi ({{ activeSession?.reconciliation_number }})
                </h3>
                <div v-if="auditLogs.length" class="space-y-2.5">
                    <div
                        v-for="log in auditLogs"
                        :key="log.id"
                        class="p-3.5 rounded-xl bg-navy-950 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-cyan-500/15 text-cyan-300 font-mono text-[10px] font-bold uppercase">
                                    {{ log.action_type }}
                                </span>
                                <span class="text-slate-400 text-[11px]">Oleh: <strong class="text-white">{{ log.user?.name || 'Administrator' }}</strong></span>
                            </div>
                            <p class="text-slate-200">{{ log.description }}</p>
                        </div>
                        <span class="font-mono text-[11px] text-slate-400 shrink-0">{{ formatDateTime(log.created_at) }}</span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-500 py-4">Belum ada log tindakan pada sesi ini.</div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- TAB 3: LAPORAN RESMI REKONSILIASI KSO (Section 8)         -->
        <!-- ========================================================= -->
        <div v-else-if="activeTab === 'report'" class="space-y-6">
            <!-- 1. Ringkasan Hasil Rekonsiliasi -->
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                    <div>
                        <h3 class="text-base font-extrabold text-white">
                            1. Ringkasan Hasil Rekonsiliasi Inventaris KSO
                        </h3>
                        <p class="text-xs text-slate-400">
                            Nomor Sesi: <strong class="text-cyan-300 font-mono">{{ activeSession?.reconciliation_number }}</strong> &bull;
                            Mitra KSO: <strong class="text-white">{{ activeSession?.partner_name }}</strong> &bull;
                            Periode: <strong class="text-white">{{ activeSession?.period }}</strong>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 no-print">
                        <button
                            @click="exportOfficialExcel"
                            class="px-3.5 py-2 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-bold text-xs"
                        >
                            Export Excel (7 Sheet)
                        </button>
                        <button
                            @click="printOfficialPdf"
                            class="px-3.5 py-2 rounded-xl bg-cyan-500 text-slate-950 font-bold text-xs"
                        >
                            Cetak Laporan Resmi (PDF)
                        </button>
                    </div>
                </div>

                <!-- 6 Report Tables corresponding to Section 8 items 2..7 -->
                <div v-for="(section, sIdx) in reportSections" :key="section.title" class="space-y-2 pt-2">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-cyan-400">
                        {{ sIdx + 2 }}. {{ section.title }} ({{ section.items.length }} Data)
                    </h4>
                    <div v-if="section.items.length" class="overflow-x-auto border border-slate-800 rounded-xl">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-navy-950 border-b border-slate-800 text-[10px] font-bold uppercase text-slate-400">
                                    <th class="py-2 px-3">No</th>
                                    <th class="py-2 px-3">SN Mitra KSO</th>
                                    <th class="py-2 px-3">Model KSO</th>
                                    <th class="py-2 px-3">Kode &amp; SN Sistem</th>
                                    <th class="py-2 px-3">Model Sistem</th>
                                    <th class="py-2 px-3">Lokasi</th>
                                    <th class="py-2 px-3">Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="(item, idx) in section.items" :key="item.id">
                                    <td class="py-2 px-3 font-mono text-slate-400">{{ idx + 1 }}</td>
                                    <td class="py-2 px-3 font-mono font-bold text-purple-300">{{ item.kso_sn || '-' }}</td>
                                    <td class="py-2 px-3 text-white">{{ item.kso_model || '-' }}</td>
                                    <td class="py-2 px-3 font-mono text-cyan-300">
                                        {{ item.system_asset_code ? `[${item.system_asset_code}] ` : '' }}{{ item.system_sn || '-' }}
                                    </td>
                                    <td class="py-2 px-3 text-slate-200">{{ item.system_model || '-' }}</td>
                                    <td class="py-2 px-3 text-slate-300">{{ item.system_location || item.kso_location || '-' }}</td>
                                    <td class="py-2 px-3 text-emerald-300">{{ item.action_status }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="p-3 rounded-xl bg-navy-950/60 border border-slate-800/80 text-xs text-slate-500">
                        Tidak ada item pada kategori ini.
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Import Data KSO (Section 2) -->
        <KsoImportModal
            :show="showImportModal"
            :partners="partners"
            @close="showImportModal = false"
            @created="handleSessionCreated"
        />

        <!-- Modal Detail Perbandingan Dua Sisi & Tindakan (Section 5 & 6) -->
        <KsoComparisonModal
            :show="showComparisonModal"
            :result="selectedResult"
            :rooms="rooms"
            :partnerName="activeSession?.partner_name || 'MITRA ABADI'"
            @close="showComparisonModal = false"
            @resolved="fetchData"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import * as XLSX from 'xlsx';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import KsoImportModal from '@/components/KsoImportModal.vue';
import KsoComparisonModal from '@/components/KsoComparisonModal.vue';
import {
    GitCompare,
    Download,
    Printer,
    Upload,
    CheckSquare,
    History,
    FileText,
    CheckCircle2,
    Search,
} from 'lucide-vue-next';

const auth = useAuthStore();
const loading = ref(false);
const activeTab = ref('results');

const partners = ref([]);
const sessions = ref([]);
const activeSession = ref(null);
const selectedSessionId = ref(null);
const results = ref([]);
const auditLogs = ref([]);
const rooms = ref([]);

const stats = ref({
    total_system_assets: 0,
    total_kso_items: 0,
    match_count: 0,
    missing_in_system_count: 0,
    missing_in_kso_count: 0,
    data_mismatch_count: 0,
    duplicate_sn_count: 0,
    invalid_data_count: 0,
    manual_review_count: 0,
});

const filters = reactive({
    status: '',
    action_status: '',
    search: '',
});

const statusTabs = [
    { label: 'Semua Status', value: '' },
    { label: 'MATCH', value: 'MATCH' },
    { label: 'MODEL MISMATCH', value: 'MODEL MISMATCH' },
    { label: 'MISSING IN SYSTEM', value: 'MISSING IN SYSTEM' },
    { label: 'MISSING IN KSO', value: 'MISSING IN KSO' },
    { label: 'DUPLICATE SN', value: 'DUPLICATE SN' },
    { label: 'INVALID DATA', value: 'INVALID DATA' },
    { label: 'NEEDS MANUAL REVIEW', value: 'NEEDS MANUAL REVIEW' },
];

const showImportModal = ref(false);
const showComparisonModal = ref(false);
const selectedResult = ref(null);

const reportSections = computed(() => [
    {
        title: 'Daftar Aset yang Cocok (MATCH)',
        items: results.value.filter((r) => r.status === 'MATCH'),
    },
    {
        title: 'Daftar Aset yang Berbeda Model (MODEL MISMATCH)',
        items: results.value.filter((r) => r.status === 'MODEL MISMATCH'),
    },
    {
        title: 'Daftar Aset yang Belum Tercatat di Aplikasi (MISSING IN SYSTEM)',
        items: results.value.filter((r) => r.status === 'MISSING IN SYSTEM'),
    },
    {
        title: 'Daftar Aset yang Tidak Ditemukan pada Data KSO (MISSING IN KSO)',
        items: results.value.filter((r) => r.status === 'MISSING IN KSO'),
    },
    {
        title: 'Daftar Serial Number Duplikat (DUPLICATE SN)',
        items: results.value.filter((r) => r.status === 'DUPLICATE SN'),
    },
    {
        title: 'Daftar Data yang Membutuhkan Verifikasi Manual (NEEDS MANUAL REVIEW & INVALID DATA)',
        items: results.value.filter((r) => r.status === 'NEEDS MANUAL REVIEW' || r.status === 'INVALID DATA'),
    },
]);

let debounceTimer = null;
function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchData();
    }, 300);
}

function setFilterStatus(st) {
    filters.status = st;
    activeTab.value = 'results';
    fetchData();
}

function resetFilters() {
    filters.status = '';
    filters.action_status = '';
    filters.search = '';
    fetchData();
}

function switchSession(id) {
    selectedSessionId.value = id;
    activeTab.value = 'results';
    fetchData();
}

function openComparison(row) {
    selectedResult.value = row;
    showComparisonModal.value = true;
}

async function handleSessionCreated(newSession) {
    selectedSessionId.value = newSession.id;
    activeTab.value = 'results';
    await fetchData();
}

async function fetchData() {
    loading.value = true;
    try {
        const { data } = await api.get('/kso-reconciliation', {
            params: {
                session_id: selectedSessionId.value,
                status: filters.status,
                action_status: filters.action_status,
                search: filters.search,
            },
        });
        partners.value = data.partners || [];
        sessions.value = data.sessions || [];
        activeSession.value = data.active_session || null;
        if (activeSession.value && !selectedSessionId.value) {
            selectedSessionId.value = activeSession.value.id;
        }
        results.value = data.results || [];
        auditLogs.value = data.audit_logs || [];
        stats.value = data.stats || stats.value;
        rooms.value = data.rooms || [];
    } finally {
        loading.value = false;
    }
}

async function verifyAllMatches() {
    if (!activeSession.value) return;
    try {
        const { data } = await api.post(`/kso-reconciliation/sessions/${activeSession.value.id}/verify-all-matches`);
        auth.notify(data.message, 'success');
        await fetchData();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal memverifikasi aset.', 'error');
    }
}

async function finalizeSession() {
    if (!activeSession.value) return;
    try {
        const { data } = await api.post(`/kso-reconciliation/sessions/${activeSession.value.id}/complete`);
        auth.notify(data.message, 'success');
        await fetchData();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyelesaikan sesi.', 'error');
    }
}

function statusBadgeClass(st) {
    if (st === 'MATCH') return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30';
    if (st === 'MODEL MISMATCH') return 'bg-amber-500/15 text-amber-300 border-amber-500/30';
    if (st === 'MISSING IN SYSTEM') return 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30';
    if (st === 'MISSING IN KSO') return 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30';
    if (st === 'DUPLICATE SN') return 'bg-rose-500/15 text-rose-300 border-rose-500/30';
    return 'bg-slate-700/80 text-slate-300 border-slate-600';
}

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function exportOfficialExcel() {
    if (!activeSession.value) return;
    const wb = XLSX.utils.book_new();

    // Sheet 1: Ringkasan Hasil Rekonsiliasi
    const summaryRows = [
        { Parameter: 'Nomor Rekonsiliasi', Nilai: activeSession.value.reconciliation_number },
        { Parameter: 'Nama Mitra KSO', Nilai: activeSession.value.partner_name },
        { Parameter: 'Nama File Import KSO', Nilai: activeSession.value.file_name },
        { Parameter: 'Periode Rekonsiliasi', Nilai: activeSession.value.period },
        { Parameter: 'Total Aset KSO di Aplikasi', Nilai: stats.value.total_system_assets },
        { Parameter: 'Total Aset dari File Mitra KSO', Nilai: stats.value.total_kso_items },
        { Parameter: 'Aset Cocok (MATCH)', Nilai: stats.value.match_count },
        { Parameter: 'Perbedaan Model (MODEL MISMATCH)', Nilai: stats.value.data_mismatch_count },
        { Parameter: 'Belum Terdaftar di Aplikasi (MISSING IN SYSTEM)', Nilai: stats.value.missing_in_system_count },
        { Parameter: 'Tidak Ditemukan di Data KSO (MISSING IN KSO)', Nilai: stats.value.missing_in_kso_count },
        { Parameter: 'Serial Number Duplikat (DUPLICATE SN)', Nilai: stats.value.duplicate_sn_count },
        { Parameter: 'Perlu Verifikasi Manual / SN Kosong', Nilai: stats.value.manual_review_count + stats.value.invalid_data_count },
    ];
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(summaryRows), '1. Ringkasan');

    const mapRows = (items) =>
        items.map((r, i) => ({
            No: i + 1,
            'Status Rekonsiliasi': r.status,
            'SN Mitra KSO': r.kso_sn || '-',
            'Model Mitra KSO': r.kso_model || '-',
            'Merk KSO': r.kso_brand || '-',
            'Lokasi KSO': r.kso_location || '-',
            'Kode Aset Sistem': r.system_asset_code || '-',
            'SN Sistem': r.system_sn || '-',
            'Model Sistem': r.system_model || '-',
            'Lokasi Sistem': r.system_location || '-',
            'Status Tindakan': r.action_status,
            'Catatan Verifikasi': r.action_note || '-',
        }));

    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(mapRows(results.value)), '2. Seluruh Hasil');
    XLSX.utils.book_append_sheet(
        wb,
        XLSX.utils.json_to_sheet(mapRows(results.value.filter((r) => r.status === 'MATCH'))),
        '3. Aset Cocok (MATCH)'
    );
    XLSX.utils.book_append_sheet(
        wb,
        XLSX.utils.json_to_sheet(mapRows(results.value.filter((r) => r.status === 'MODEL MISMATCH'))),
        '4. Berbeda Model'
    );
    XLSX.utils.book_append_sheet(
        wb,
        XLSX.utils.json_to_sheet(mapRows(results.value.filter((r) => r.status === 'MISSING IN SYSTEM'))),
        '5. Belum Tercatat'
    );
    XLSX.utils.book_append_sheet(
        wb,
        XLSX.utils.json_to_sheet(mapRows(results.value.filter((r) => r.status === 'MISSING IN KSO'))),
        '6. Tidak Ada di KSO'
    );
    XLSX.utils.book_append_sheet(
        wb,
        XLSX.utils.json_to_sheet(
            mapRows(
                results.value.filter((r) =>
                    ['DUPLICATE SN', 'NEEDS MANUAL REVIEW', 'INVALID DATA'].includes(r.status)
                )
            )
        ),
        '7. Duplikat & Manual'
    );

    XLSX.writeFile(
        wb,
        `Laporan_Rekonsiliasi_KSO_${activeSession.value.reconciliation_number}.xlsx`
    );
    auth.notify('Laporan Excel Rekonsiliasi KSO (7 Sheet) berhasil diunduh.', 'success');
}

function printOfficialPdf() {
    activeTab.value = 'report';
    setTimeout(() => {
        window.print();
    }, 150);
}

onMounted(() => {
    fetchData();
});
</script>

