<template>
    <div class="space-y-6">
        <!-- Top Welcome & Refresh Banner -->
        <div class="bg-gradient-to-r from-navy-900 via-navy-800 to-navy-900 border border-slate-800/90 rounded-2xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xl">
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0">
                    <Activity class="w-6 h-6" />
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base sm:text-lg font-extrabold text-white">
                            Pusat Komando Inventaris Perangkat IT Rumah Sakit
                        </h2>
                        <span class="text-[11px] font-mono px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                            Real-Time MySQL
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        Monitoring kondisi perangkat komputer, jaringan, server SIMRS, printer pelayanan, serta kepemilikan aset RSUD &amp; mitra KSO.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                <button
                    @click="fetchDashboard"
                    :disabled="loading"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-navy-950 hover:bg-slate-800 border border-slate-700/80 text-xs font-semibold text-slate-200 transition"
                >
                    <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin text-cyan-400': loading }" />
                    <span>Refresh Data</span>
                </button>
                <router-link
                    to="/reports"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 border border-purple-500/30 text-xs font-semibold text-purple-300 transition"
                >
                    <FileSpreadsheet class="w-3.5 h-3.5" />
                    <span>Buka Laporan</span>
                </router-link>
            </div>
        </div>

        <!-- 8 Kartu Statistik Utama (Exact Spec Section 3 - Responsive 2 Kolom di Mobile, 4 Kolom di Desktop) -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div
                v-for="card in statCards"
                :key="card.title"
                @click="card.route ? router.push(card.route) : null"
                class="bg-navy-900/90 border rounded-2xl p-3.5 sm:p-5 transition-all duration-200 hover:-translate-y-0.5 cursor-pointer relative overflow-hidden group"
                :class="card.borderClass"
            >
                <div class="flex items-start justify-between gap-2 sm:gap-3">
                    <div class="min-w-0">
                        <p class="text-[10px] sm:text-xs font-semibold text-slate-400 uppercase tracking-wider leading-tight">
                            {{ card.title }}
                        </p>
                        <div class="text-xl sm:text-3xl font-extrabold text-white mt-1.5 sm:mt-2 font-mono tracking-tight">
                            {{ card.value }}
                        </div>
                    </div>
                    <div
                        class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center border shrink-0 transition-transform group-hover:scale-110"
                        :class="card.iconBoxClass"
                    >
                        <component :is="card.icon" class="w-4 h-4 sm:w-5 sm:h-5" />
                    </div>
                </div>
                <div class="mt-2.5 sm:mt-3 pt-2.5 sm:pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-1 text-[10px] sm:text-[11px]">
                    <span class="text-slate-400 truncate">{{ card.subtitle }}</span>
                    <span class="font-semibold shrink-0" :class="card.badgeTextClass">{{ card.badge }}</span>
                </div>
            </div>
        </div>

        <!-- 3 Grafik Utama (Kategori, Kondisi, Kepemilikan) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Grafik Jumlah Aset Berdasarkan Kategori (Bar Chart) -->
            <div class="lg:col-span-6 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <BarChart3 class="w-4 h-4 text-cyan-400" />
                            Grafik Jumlah Aset Berdasarkan Kategori
                        </h3>
                        <p class="text-xs text-slate-400">Distribusi seluruh perangkat IT aktif di rumah sakit</p>
                    </div>
                    <span class="text-xs font-mono text-cyan-400 bg-cyan-500/10 border border-cyan-500/20 px-2.5 py-1 rounded-lg">
                        9 Kategori
                    </span>
                </div>
                <div class="h-64 relative">
                    <Bar v-if="categoryChartData" :data="categoryChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Grafik Kondisi Aset (Baik, Rusak, Maintenance) -->
            <div class="lg:col-span-3 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex flex-col">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <PieChart class="w-4 h-4 text-emerald-400" />
                        Grafik Kondisi Aset
                    </h3>
                    <p class="text-xs text-slate-400">Baik, Rusak &amp; Maintenance</p>
                </div>
                <div class="h-48 relative flex items-center justify-center">
                    <Doughnut v-if="conditionChartData" :data="conditionChartData" :options="doughnutOptions" />
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                        <div class="text-xs font-bold text-emerald-400">{{ charts.by_condition?.Baik || 0 }}</div>
                        <div class="text-[10px] text-slate-400">Baik</div>
                    </div>
                    <div class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/20">
                        <div class="text-xs font-bold text-amber-400">{{ charts.by_condition?.Maintenance || 0 }}</div>
                        <div class="text-[10px] text-slate-400">Perbaikan</div>
                    </div>
                    <div class="p-2 rounded-xl bg-rose-500/10 border border-rose-500/20">
                        <div class="text-xs font-bold text-rose-400">{{ charts.by_condition?.Rusak || 0 }}</div>
                        <div class="text-[10px] text-slate-400">Rusak</div>
                    </div>
                </div>
            </div>

            <!-- Grafik Kepemilikan Aset (Milik RSUD, KSO, Pihak Ketiga) -->
            <div class="lg:col-span-3 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex flex-col">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <Building class="w-4 h-4 text-purple-400" />
                        Grafik Kepemilikan Aset
                    </h3>
                    <p class="text-xs text-slate-400">RSUD, KSO &amp; Pihak Ketiga</p>
                </div>
                <div class="h-48 relative flex items-center justify-center">
                    <Doughnut v-if="ownershipChartData" :data="ownershipChartData" :options="doughnutOptions" />
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 rounded-xl bg-cyan-500/10 border border-cyan-500/20">
                        <div class="text-xs font-bold text-cyan-400">{{ charts.by_ownership?.['Milik RSUD'] || 0 }}</div>
                        <div class="text-[10px] text-slate-400">RSUD</div>
                    </div>
                    <div class="p-2 rounded-xl bg-purple-500/10 border border-purple-500/20">
                        <div class="text-xs font-bold text-purple-400">{{ charts.by_ownership?.['KSO'] || 0 }}</div>
                        <div class="text-[10px] text-slate-400">KSO</div>
                    </div>
                    <div class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/20">
                        <div class="text-xs font-bold text-amber-400">{{ charts.by_ownership?.['Milik Pihak Ketiga'] || 0 }}</div>
                        <div class="text-[10px] text-slate-400">Pihak Ke-3</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informasi Aset yang Perlu Ditindaklanjuti & Ringkasan Kepemilikan Mitra KSO -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Informasi Aset yang Perlu Ditindaklanjuti -->
            <div class="lg:col-span-7 bg-navy-900/90 border border-rose-500/30 rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400">
                            <AlertTriangle class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">
                                Informasi Aset yang Perlu Ditindaklanjuti
                            </h3>
                            <p class="text-xs text-slate-400">
                                Perangkat dengan status Rusak, sedang Maintenance, atau memiliki catatan tindak lanjut
                            </p>
                        </div>
                    </div>
                    <router-link
                        to="/assets?needs_followup=1"
                        class="text-xs font-semibold text-rose-300 hover:text-rose-200 flex items-center gap-1"
                    >
                        <span>Lihat Semua</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </router-link>
                </div>

                <div v-if="lists.followup_assets?.length" class="space-y-2.5">
                    <div
                        v-for="item in lists.followup_assets"
                        :key="item.id"
                        @click="router.push(`/assets/${item.id}`)"
                        class="p-3.5 rounded-xl bg-navy-950/80 hover:bg-navy-950 border border-slate-800 hover:border-rose-500/40 transition cursor-pointer flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">
                                    {{ item.asset_code }}
                                </span>
                                <span class="text-sm font-bold text-white">{{ item.name }}</span>
                                <span
                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
                                    :class="conditionBadgeClass(item.condition)"
                                >
                                    {{ item.condition }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">
                                <span class="text-slate-300 font-medium">{{ item.room?.name }}</span>
                                &bull; PIC: {{ item.pic || '-' }}
                            </p>
                            <p v-if="item.followup_note || item.notes" class="text-xs text-amber-300/90 flex items-center gap-1.5">
                                <Wrench class="w-3.5 h-3.5 shrink-0" />
                                <span>{{ item.followup_note || item.notes }}</span>
                            </p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <span class="text-xs font-semibold text-cyan-400 hover:underline">Detail &rarr;</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center text-xs text-slate-400">
                    Seluruh perangkat dalam kondisi baik. Tidak ada aset yang memerlukan tindak lanjut darurat.
                </div>
            </div>

            <!-- Daftar Aset Sedang Dalam Perbaikan (Maintenance Radar) -->
            <div class="lg:col-span-5 bg-navy-900/90 border border-amber-500/30 rounded-2xl p-5 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
                            <Wrench class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">
                                Aset Sedang Dalam Perbaikan
                            </h3>
                            <p class="text-xs text-slate-400">Antrean aktif pada Maintenance Radar</p>
                        </div>
                    </div>
                    <router-link
                        to="/maintenance"
                        class="text-xs font-semibold text-amber-300 hover:text-amber-200 flex items-center gap-1"
                    >
                        <span>Maintenance Radar</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </router-link>
                </div>

                <div v-if="lists.active_maintenances?.length" class="space-y-2.5 flex-1">
                    <div
                        v-for="mnt in lists.active_maintenances"
                        :key="mnt.id"
                        class="p-3.5 rounded-xl bg-navy-950/80 border border-slate-800 space-y-1.5"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-amber-300">{{ mnt.ticket_number }}</span>
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full border"
                                :class="mnt.status === 'Menunggu'
                                    ? 'bg-rose-500/15 text-rose-300 border-rose-500/30'
                                    : 'bg-amber-500/15 text-amber-300 border-amber-500/30'"
                            >
                                {{ mnt.status }}
                            </span>
                        </div>
                        <div class="text-xs font-bold text-white">
                            {{ mnt.asset?.asset_code }} &mdash; {{ mnt.asset?.name }}
                        </div>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ mnt.complaint }}</p>
                        <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                            <span>Teknisi: <strong class="text-slate-300">{{ mnt.technician }}</strong></span>
                            <span>{{ mnt.maintenance_date }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center text-xs text-slate-400">
                    Tidak ada pekerjaan perbaikan yang sedang mengantre.
                </div>
            </div>
        </div>

        <!-- Daftar Aset Terbaru & Ringkasan Mitra KSO/Pihak Ketiga -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Daftar Aset Terbaru -->
            <div class="lg:col-span-8 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <Clock class="w-4 h-4 text-cyan-400" />
                            Daftar Aset Terbaru
                        </h3>
                        <p class="text-xs text-slate-400">Inventaris perangkat yang terakhir didaftarkan ke dalam sistem</p>
                    </div>
                    <router-link
                        to="/assets"
                        class="text-xs font-semibold text-cyan-400 hover:text-cyan-300 flex items-center gap-1"
                    >
                        <span>Buka Asset Directory</span>
                        <ArrowRight class="w-3.5 h-3.5" />
                    </router-link>
                </div>

                <!-- Mobile Card List (Daftar Aset Terbaru) -->
                <div class="md:hidden space-y-2.5">
                    <div
                        v-for="asset in lists.latest_assets"
                        :key="`mob-${asset.id}`"
                        @click="router.push(`/assets/${asset.id}`)"
                        class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800 active:border-cyan-500/40 space-y-2 cursor-pointer"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-xs font-bold text-cyan-400">{{ asset.asset_code }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="conditionBadgeClass(asset.condition)">
                                {{ asset.condition }}
                            </span>
                        </div>
                        <div class="font-bold text-white text-xs">{{ asset.name }}</div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span class="truncate">{{ asset.room?.name || '-' }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border shrink-0" :class="ownershipBadgeClass(asset.ownership)">
                                {{ asset.ownership }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table (Daftar Aset Terbaru) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                                <th class="py-2.5 px-3">Kode</th>
                                <th class="py-2.5 px-3">Nama Perangkat</th>
                                <th class="py-2.5 px-3">Kategori</th>
                                <th class="py-2.5 px-3">Ruangan</th>
                                <th class="py-2.5 px-3">Kepemilikan</th>
                                <th class="py-2.5 px-3">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-xs">
                            <tr
                                v-for="asset in lists.latest_assets"
                                :key="asset.id"
                                @click="router.push(`/assets/${asset.id}`)"
                                class="hover:bg-slate-800/40 cursor-pointer transition"
                            >
                                <td class="py-3 px-3 font-mono font-bold text-cyan-400">{{ asset.asset_code }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-bold text-white">{{ asset.name }}</div>
                                    <div class="text-[11px] text-slate-400">{{ asset.brand }} {{ asset.model }}</div>
                                </td>
                                <td class="py-3 px-3 text-slate-300">{{ asset.category }}</td>
                                <td class="py-3 px-3 text-slate-300">{{ asset.room?.name || '-' }}</td>
                                <td class="py-3 px-3">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="ownershipBadgeClass(asset.ownership)">
                                        {{ asset.ownership }}
                                    </span>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border" :class="conditionBadgeClass(asset.condition)">
                                        {{ asset.condition }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Dashboard Ringkas Kepemilikan KSO & Mitra Pihak Ketiga (Section 9) -->
            <div class="lg:col-span-4 bg-navy-900/90 border border-purple-500/30 rounded-2xl p-5 flex flex-col">
                <div class="mb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <Handshake class="w-4 h-4 text-purple-400" />
                        Ringkasan Aset KSO &amp; Mitra
                    </h3>
                    <p class="text-xs text-slate-400">Rekap perangkat milik mitra KSO &amp; pihak ketiga di RSUD</p>
                </div>

                <div v-if="charts.partner_breakdown?.length" class="space-y-3 flex-1">
                    <div
                        v-for="(partner, idx) in charts.partner_breakdown"
                        :key="idx"
                        @click="router.push(`/assets?ownership=${encodeURIComponent(partner.ownership)}`)"
                        class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800 hover:border-purple-500/40 cursor-pointer transition flex items-center justify-between gap-3"
                    >
                        <div class="min-w-0">
                            <span
                                class="inline-block text-[10px] font-bold px-2 py-0.5 rounded border mb-1"
                                :class="ownershipBadgeClass(partner.ownership)"
                            >
                                {{ partner.ownership }}
                            </span>
                            <div class="text-xs font-bold text-white truncate">{{ partner.partner_name }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-lg font-mono font-extrabold text-purple-300">{{ partner.total }}</div>
                            <div class="text-[10px] text-slate-400">Unit Aset</div>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-xs text-slate-400">
                    Belum ada data aset KSO atau Pihak Ketiga.
                </div>
            </div>
        </div>

        <!-- Ringkasan & Riwayat Pemindahan Aset Terbaru (Integrasi Modul Asset Transfer - Section 11) -->
        <div class="bg-navy-900/90 border border-teal-500/30 rounded-2xl p-5 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-teal-400 shrink-0">
                        <ArrowLeftRight class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <span>Monitoring Mutasi &amp; Pemindahan Lokasi Aset IT</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-teal-500/15 text-teal-300 border border-teal-500/30">
                                Total: {{ stats.total_transfers || 0 }} Transaksi
                            </span>
                        </h3>
                        <p class="text-xs text-slate-400">
                            Bulan Ini: <strong class="text-cyan-300">{{ stats.month_transfers || 0 }}</strong> &bull;
                            Menunggu Pemindahan: <strong class="text-amber-300">{{ stats.pending_transfers || 0 }}</strong>
                        </p>
                    </div>
                </div>
                <router-link
                    to="/transfers"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-500/15 hover:bg-teal-500/25 border border-teal-500/30 text-xs font-bold text-teal-300 transition w-fit"
                >
                    <span>Kelola Pemindahan Aset</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </router-link>
            </div>

            <!-- Mobile Card List (Recent Transfers) -->
            <div v-if="lists.recent_transfers?.length" class="md:hidden space-y-2.5">
                <div
                    v-for="trf in lists.recent_transfers"
                    :key="`mob-trf-${trf.id}`"
                    @click="router.push('/transfers')"
                    class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800 space-y-2 cursor-pointer"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono text-xs font-bold text-cyan-400">{{ trf.transfer_number }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                            {{ trf.status }}
                        </span>
                    </div>
                    <div class="text-xs font-bold text-white">
                        {{ trf.asset?.asset_code }} &mdash; {{ trf.asset?.name }}
                    </div>
                    <div class="text-[11px] flex items-center flex-wrap gap-1.5">
                        <span class="text-rose-300 font-medium">{{ trf.from_room?.name }}</span>
                        <span class="text-cyan-400">&rarr;</span>
                        <strong class="text-emerald-300">{{ trf.to_room?.name }}</strong>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-400 pt-1 border-t border-slate-800/70">
                        <span>PIC: {{ trf.new_pic || '-' }}</span>
                        <span class="font-mono">{{ String(trf.transfer_date || '').slice(0, 10) }}</span>
                    </div>
                </div>
            </div>

            <!-- Desktop Table (Recent Transfers) -->
            <div v-if="lists.recent_transfers?.length" class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-2 px-3">No. Transfer</th>
                            <th class="py-2 px-3">Perangkat IT</th>
                            <th class="py-2 px-3">Lokasi Asal &rarr; Tujuan</th>
                            <th class="py-2 px-3">PIC Baru</th>
                            <th class="py-2 px-3">Tanggal</th>
                            <th class="py-2 px-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr
                            v-for="trf in lists.recent_transfers"
                            :key="trf.id"
                            @click="router.push('/transfers')"
                            class="hover:bg-slate-800/40 cursor-pointer transition"
                        >
                            <td class="py-2.5 px-3 font-mono font-bold text-cyan-400">{{ trf.transfer_number }}</td>
                            <td class="py-2.5 px-3">
                                <span class="font-mono text-slate-400">{{ trf.asset?.asset_code }}</span> &mdash;
                                <strong class="text-white">{{ trf.asset?.name }}</strong>
                            </td>
                            <td class="py-2.5 px-3">
                                <span class="text-rose-300">{{ trf.from_room?.name }}</span>
                                <span class="text-cyan-400 mx-1.5">&rarr;</span>
                                <strong class="text-emerald-300">{{ trf.to_room?.name }}</strong>
                            </td>
                            <td class="py-2.5 px-3 text-slate-200">{{ trf.new_pic || '-' }}</td>
                            <td class="py-2.5 px-3 font-mono text-slate-400">{{ String(trf.transfer_date || '').slice(0, 10) }}</td>
                            <td class="py-2.5 px-3">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                    {{ trf.status }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import {
    Boxes,
    Monitor,
    Printer,
    Network,
    Building2,
    Handshake,
    AlertOctagon,
    Wrench,
    Activity,
    RefreshCw,
    FileSpreadsheet,
    BarChart3,
    PieChart,
    Building,
    AlertTriangle,
    ArrowRight,
    ArrowLeftRight,
    Clock,
} from 'lucide-vue-next';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const router = useRouter();
const loading = ref(false);

const stats = ref({
    total_assets: 0,
    total_pc_laptop: 0,
    total_printer: 0,
    total_network: 0,
    total_rsud: 0,
    total_kso_partner: 0,
    total_damaged: 0,
    total_maintenance: 0,
});

const charts = ref({
    by_category: [],
    by_condition: { Baik: 0, Rusak: 0, Maintenance: 0 },
    by_ownership: { 'Milik RSUD': 0, KSO: 0, 'Milik Pihak Ketiga': 0 },
    by_room: [],
    partner_breakdown: [],
});

const lists = ref({
    latest_assets: [],
    active_maintenances: [],
    followup_assets: [],
});

// 8 Kartu Statistik Utama sesuai Spesifikasi Bagian 3
const statCards = computed(() => [
    {
        title: '1. Total Seluruh Aset',
        value: stats.value.total_assets,
        subtitle: 'Inventaris perangkat IT aktif',
        badge: '100% Terdaftar',
        icon: Boxes,
        route: '/assets',
        borderClass: 'border-cyan-500/30 hover:border-cyan-400/60',
        iconBoxClass: 'bg-cyan-500/15 text-cyan-400 border-cyan-500/30',
        badgeTextClass: 'text-cyan-400',
    },
    {
        title: '2. Total PC & Laptop',
        value: stats.value.total_pc_laptop,
        subtitle: 'Workstation pelayanan & kantor',
        badge: 'Komputer & Laptop',
        icon: Monitor,
        route: '/assets?category=Komputer+%2F+PC',
        borderClass: 'border-blue-500/30 hover:border-blue-400/60',
        iconBoxClass: 'bg-blue-500/15 text-blue-400 border-blue-500/30',
        badgeTextClass: 'text-blue-400',
    },
    {
        title: '3. Total Printer',
        value: stats.value.total_printer,
        subtitle: 'Printer dokumen & label thermal',
        badge: 'Unit Cetak',
        icon: Printer,
        route: '/assets?category=Printer',
        borderClass: 'border-indigo-500/30 hover:border-indigo-400/60',
        iconBoxClass: 'bg-indigo-500/15 text-indigo-400 border-indigo-500/30',
        badgeTextClass: 'text-indigo-400',
    },
    {
        title: '4. Perangkat Network',
        value: stats.value.total_network,
        subtitle: 'Router, Switch, Server & AP',
        badge: 'Infrastruktur',
        icon: Network,
        route: '/network-map',
        borderClass: 'border-purple-500/30 hover:border-purple-400/60',
        iconBoxClass: 'bg-purple-500/15 text-purple-400 border-purple-500/30',
        badgeTextClass: 'text-purple-400',
    },
    {
        title: '5. Aset Milik RSUD',
        value: stats.value.total_rsud,
        subtitle: 'Inventaris murni rumah sakit',
        badge: 'Milik RSUD',
        icon: Building2,
        route: '/assets?ownership=Milik+RSUD',
        borderClass: 'border-emerald-500/30 hover:border-emerald-400/60',
        iconBoxClass: 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
        badgeTextClass: 'text-emerald-400',
    },
    {
        title: '6. Milik KSO / Pihak Ke-3',
        value: stats.value.total_kso_partner,
        subtitle: 'Aset kerja sama operasional',
        badge: 'KSO & Mitra',
        icon: Handshake,
        route: '/assets?ownership=KSO',
        borderClass: 'border-violet-500/30 hover:border-violet-400/60',
        iconBoxClass: 'bg-violet-500/15 text-violet-400 border-violet-500/30',
        badgeTextClass: 'text-violet-400',
    },
    {
        title: '7. Total Aset Rusak',
        value: stats.value.total_damaged,
        subtitle: 'Perlu penggantian / tindak lanjut',
        badge: 'Kondisi Rusak',
        icon: AlertOctagon,
        route: '/assets?condition=Rusak',
        borderClass: 'border-rose-500/40 hover:border-rose-400/70',
        iconBoxClass: 'bg-rose-500/15 text-rose-400 border-rose-500/30',
        badgeTextClass: 'text-rose-400',
    },
    {
        title: '8. Upgrade / Perbaikan',
        value: stats.value.total_maintenance,
        subtitle: 'Sedang dalam proses maintenance',
        badge: 'Maintenance',
        icon: Wrench,
        route: '/assets?condition=Maintenance',
        borderClass: 'border-amber-500/40 hover:border-amber-400/70',
        iconBoxClass: 'bg-amber-500/15 text-amber-400 border-amber-500/30',
        badgeTextClass: 'text-amber-400',
    },
]);

const categoryChartData = computed(() => {
    const items = charts.value.by_category || [];
    return {
        labels: items.map((i) => i.category),
        datasets: [
            {
                label: 'Jumlah Unit',
                data: items.map((i) => i.total),
                backgroundColor: [
                    'rgba(6, 182, 212, 0.75)',
                    'rgba(59, 130, 246, 0.75)',
                    'rgba(139, 92, 246, 0.75)',
                    'rgba(16, 185, 129, 0.75)',
                    'rgba(245, 158, 11, 0.75)',
                    'rgba(236, 72, 153, 0.75)',
                    'rgba(14, 165, 233, 0.75)',
                    'rgba(168, 85, 247, 0.75)',
                    'rgba(100, 116, 139, 0.75)',
                ],
                borderColor: '#06b6d4',
                borderWidth: 1,
                borderRadius: 6,
            },
        ],
    };
});

const conditionChartData = computed(() => {
    const c = charts.value.by_condition || {};
    return {
        labels: ['Baik', 'Maintenance', 'Rusak'],
        datasets: [
            {
                data: [c.Baik || 0, c.Maintenance || 0, c.Rusak || 0],
                backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                borderColor: '#0b1120',
                borderWidth: 3,
            },
        ],
    };
});

const ownershipChartData = computed(() => {
    const o = charts.value.by_ownership || {};
    return {
        labels: ['Milik RSUD', 'KSO', 'Milik Pihak Ketiga'],
        datasets: [
            {
                data: [o['Milik RSUD'] || 0, o['KSO'] || 0, o['Milik Pihak Ketiga'] || 0],
                backgroundColor: ['#06b6d4', '#8b5cf6', '#f59e0b'],
                borderColor: '#0b1120',
                borderWidth: 3,
            },
        ],
    };
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        x: {
            ticks: { color: '#94a3b8', font: { size: 10 } },
            grid: { color: 'rgba(51, 65, 85, 0.25)' },
        },
        y: {
            beginAtZero: true,
            ticks: { stepSize: 1, color: '#94a3b8', font: { size: 11 } },
            grid: { color: 'rgba(51, 65, 85, 0.25)' },
        },
    },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '66%',
    plugins: {
        legend: {
            position: 'bottom',
            labels: { color: '#cbd5e1', font: { size: 11 }, boxWidth: 10, padding: 12 },
        },
    },
};

function conditionBadgeClass(cond) {
    if (cond === 'Baik') return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30';
    if (cond === 'Rusak') return 'bg-rose-500/15 text-rose-300 border-rose-500/30';
    return 'bg-amber-500/15 text-amber-300 border-amber-500/30';
}

function ownershipBadgeClass(own) {
    if (own === 'Milik RSUD') return 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30';
    if (own === 'KSO') return 'bg-purple-500/15 text-purple-300 border-purple-500/30';
    return 'bg-amber-500/15 text-amber-300 border-amber-500/30';
}

async function fetchDashboard() {
    loading.value = true;
    try {
        const { data } = await api.get('/dashboard');
        stats.value = data.stats;
        charts.value = data.charts;
        lists.value = data.lists;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchDashboard();
});
</script>
