<template>
    <div class="space-y-6">
        <!-- Print Header (For PDF Report Printing) -->
        <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-extrabold uppercase">LAPORAN RIWAYAT PEMINDAHAN / MUTASI ASET IT</h1>
                    <p class="text-xs text-slate-700">RSUD Majenang &mdash; Sistem Informasi Manajemen Aset Teknologi Informasi</p>
                </div>
                <div class="text-right text-xs font-mono">
                    <div>Tanggal Cetak: {{ new Date().toLocaleDateString('id-ID') }}</div>
                    <div>Total Transaksi: {{ transfers.length }} Record</div>
                </div>
            </div>
        </div>

        <!-- Top Header & Action Buttons -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4 no-print">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0">
                    <ArrowLeftRight class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold text-white">Asset Transfer / Pemindahan Aset IT</h2>
                        <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">
                            {{ transfers.length }} Transaksi
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Kelola mutasi perangkat IT antar-ruangan, antar-instalasi, maupun antar-gedung beserta berita acara serah terima.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                <button
                    @click="exportTransferExcel"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-xs font-semibold text-emerald-300 transition"
                >
                    <Download class="w-4 h-4 shrink-0" />
                    <span class="truncate">Export Excel</span>
                </button>
                <button
                    @click="printTransferReport"
                    class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl bg-navy-950 hover:bg-slate-800 border border-slate-700 text-xs font-semibold text-slate-200 transition"
                >
                    <Printer class="w-4 h-4 shrink-0" />
                    <span class="truncate">Cetak PDF</span>
                </button>
                <button
                    @click="showFormModal = true"
                    class="col-span-2 sm:col-span-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-extrabold text-xs shadow-lg shadow-cyan-500/20 transition"
                >
                    <Plus class="w-4 h-4 stroke-[2.5] shrink-0" />
                    <span>Catat Pemindahan Aset</span>
                </button>
            </div>
        </div>

        <!-- 4 Kartu Statistik Utama + 1 Kartu Aset Unik (Section 1 & Section 9) -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-navy-900/90 border border-cyan-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Total Pemindahan Aset</div>
                <div class="text-2xl font-mono font-extrabold text-white mt-1">{{ stats.total_transfers }}</div>
                <div class="text-[11px] text-cyan-400 mt-1">Transaksi Selesai</div>
            </div>
            <div class="bg-navy-900/90 border border-emerald-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Pemindahan Bulan Ini</div>
                <div class="text-2xl font-mono font-extrabold text-emerald-400 mt-1">{{ stats.this_month_transfers }}</div>
                <div class="text-[11px] text-emerald-300/80 mt-1">Periode Bulan Berjalan</div>
            </div>
            <div class="bg-navy-900/90 border border-blue-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Pemindahan Hari Ini</div>
                <div class="text-2xl font-mono font-extrabold text-blue-400 mt-1">{{ stats.today_transfers }}</div>
                <div class="text-[11px] text-blue-300/80 mt-1">Mutasi Harian</div>
            </div>
            <div class="bg-navy-900/90 border border-amber-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Menunggu Pemindahan</div>
                <div class="text-2xl font-mono font-extrabold text-amber-400 mt-1">{{ stats.pending_transfers }}</div>
                <div class="text-[11px] text-amber-300/80 mt-1">Menunggu Konfirmasi</div>
            </div>
            <div class="col-span-2 lg:col-span-1 bg-navy-900/90 border border-purple-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Aset Pernah Dipindahkan</div>
                <div class="text-2xl font-mono font-extrabold text-purple-400 mt-1">{{ stats.distinct_assets_transferred }}</div>
                <div class="text-[11px] text-purple-300/80 mt-1">Unit Perangkat Unik</div>
            </div>
        </div>

        <!-- Ringkasan Statistik Laporan Pemindahan (Section 9: Ruangan Masuk/Keluar Terbanyak & Rekap Bulanan) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Ruangan dengan Aset Masuk Terbanyak -->
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 space-y-2.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                    <ArrowDownLeft class="w-4 h-4" />
                    <span>Ruangan Aset Masuk Terbanyak</span>
                </h3>
                <div v-if="stats.top_incoming_rooms?.length" class="space-y-1.5">
                    <div
                        v-for="rm in stats.top_incoming_rooms"
                        :key="rm.room_id"
                        class="flex items-center justify-between text-xs p-2 rounded-xl bg-navy-950/80 border border-slate-800/80"
                    >
                        <span class="font-semibold text-white truncate">{{ rm.name }}</span>
                        <span class="font-mono font-bold text-emerald-400 px-2 py-0.5 rounded bg-emerald-500/10">
                            +{{ rm.total }} Unit
                        </span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-500 py-3">Belum ada data.</div>
            </div>

            <!-- Ruangan dengan Aset Keluar Terbanyak -->
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 space-y-2.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-amber-400 flex items-center gap-1.5">
                    <ArrowUpRight class="w-4 h-4" />
                    <span>Ruangan Aset Keluar Terbanyak</span>
                </h3>
                <div v-if="stats.top_outgoing_rooms?.length" class="space-y-1.5">
                    <div
                        v-for="rm in stats.top_outgoing_rooms"
                        :key="rm.room_id"
                        class="flex items-center justify-between text-xs p-2 rounded-xl bg-navy-950/80 border border-slate-800/80"
                    >
                        <span class="font-semibold text-white truncate">{{ rm.name }}</span>
                        <span class="font-mono font-bold text-amber-400 px-2 py-0.5 rounded bg-amber-500/10">
                            -{{ rm.total }} Unit
                        </span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-500 py-3">Belum ada data.</div>
            </div>

            <!-- Jumlah Pemindahan per Bulan -->
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 space-y-2.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-cyan-400 flex items-center gap-1.5">
                    <Calendar class="w-4 h-4" />
                    <span>Jumlah Pemindahan per Bulan</span>
                </h3>
                <div v-if="stats.monthly_recap?.length" class="space-y-1.5">
                    <div
                        v-for="m in stats.monthly_recap"
                        :key="m.month_key"
                        class="flex items-center justify-between text-xs p-2 rounded-xl bg-navy-950/80 border border-slate-800/80"
                    >
                        <span class="font-semibold text-slate-200">{{ m.month_label }}</span>
                        <span class="font-mono font-bold text-cyan-300 px-2 py-0.5 rounded bg-cyan-500/10">
                            {{ m.total }} Transaksi
                        </span>
                    </div>
                </div>
                <div v-else class="text-xs text-slate-500 py-3">Belum ada data.</div>
            </div>
        </div>

        <!-- Filter & Search Bar (Section 1 & Section 9) -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 space-y-3 no-print">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative">
                    <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="filters.search"
                        @input="debouncedFetch"
                        type="text"
                        placeholder="Cari No. Transfer, Kode/Nama Aset, PIC..."
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl pl-10 pr-4 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                    />
                </div>

                <div>
                    <select
                        v-model="filters.from_room_id"
                        @change="fetchTransfers"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200"
                    >
                        <option value="">Semua Ruangan Asal</option>
                        <option v-for="r in rooms" :key="r.id" :value="r.id">Asal: {{ r.name }}</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="filters.to_room_id"
                        @change="fetchTransfers"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200"
                    >
                        <option value="">Semua Ruangan Tujuan</option>
                        <option v-for="r in rooms" :key="r.id" :value="r.id">Tujuan: {{ r.name }}</option>
                    </select>
                </div>

                <div>
                    <select
                        v-model="filters.category"
                        @change="fetchTransfers"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200"
                    >
                        <option value="">Semua Kategori Aset</option>
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-slate-800/80">
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Dari Tanggal</label>
                    <input
                        v-model="filters.start_date"
                        @change="fetchTransfers"
                        type="date"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-1.5 text-xs text-white"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Sampai Tanggal</label>
                    <input
                        v-model="filters.end_date"
                        @change="fetchTransfers"
                        type="date"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-1.5 text-xs text-white"
                    />
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Filter Petugas</label>
                    <select
                        v-model="filters.created_by"
                        @change="fetchTransfers"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-1.5 text-xs text-slate-200"
                    >
                        <option value="">Semua Petugas</option>
                        <option v-for="u in staffList" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-semibold text-slate-400 mb-1">Status Pemindahan</label>
                    <select
                        v-model="filters.status"
                        @change="fetchTransfers"
                        class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-1.5 text-xs text-slate-200"
                    >
                        <option value="">Semua Status</option>
                        <option value="Selesai">Selesai</option>
                        <option value="Menunggu">Menunggu</option>
                        <option value="Dibatalkan">Dibatalkan</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button
                        v-if="hasActiveFilters"
                        @click="resetFilters"
                        class="w-full py-1.5 px-3 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-xs font-semibold text-rose-300"
                    >
                        Reset Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Daftar Pemindahan Aset: Mobile Cards (< 768px) & Tabel Lengkap (>= 768px) -->
        <div class="space-y-3">
            <!-- Mobile Card View -->
            <div class="md:hidden space-y-3 no-print">
                <div v-if="loading" class="bg-navy-900 border border-slate-800 rounded-2xl p-8 text-center text-xs text-slate-400">
                    Memuat data riwayat pemindahan aset...
                </div>
                <div v-else-if="!transfers.length" class="bg-navy-900 border border-slate-800 rounded-2xl p-8 text-center text-xs text-slate-400">
                    Tidak ada data pemindahan aset yang sesuai filter.
                </div>
                <div
                    v-for="trf in transfers"
                    :key="`mob-trf-card-${trf.id}`"
                    class="bg-navy-900/95 border border-slate-800/90 rounded-2xl p-4 space-y-3 shadow-lg"
                >
                    <div class="flex items-center justify-between gap-2">
                        <button
                            @click="openDetailModal(trf)"
                            class="font-mono text-xs font-extrabold text-cyan-400 hover:underline"
                        >
                            {{ trf.transfer_number }}
                        </button>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                            :class="{
                                'bg-emerald-500/15 text-emerald-300 border-emerald-500/30': trf.status === 'Selesai',
                                'bg-amber-500/15 text-amber-300 border-amber-500/30': trf.status === 'Menunggu',
                                'bg-rose-500/15 text-rose-300 border-rose-500/30': trf.status === 'Dibatalkan',
                            }"
                        >
                            {{ trf.status }}
                        </span>
                    </div>

                    <div>
                        <router-link
                            v-if="trf.asset"
                            :to="`/assets/${trf.asset.id}`"
                            class="text-xs font-extrabold text-white hover:text-cyan-300 block"
                        >
                            <span class="font-mono text-cyan-300">{{ trf.asset.asset_code }}</span> &mdash; {{ trf.asset.name }}
                        </router-link>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            {{ trf.asset?.category || '-' }} &bull; Alasan: <strong class="text-slate-200">{{ trf.transfer_reason }}</strong>
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-navy-950 border border-slate-800/80 space-y-1.5 text-xs">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-500 text-[11px]">Dari:</span>
                            <span class="font-semibold text-amber-300 text-right">{{ trf.from_room?.name || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-slate-500 text-[11px]">Ke Tujuan:</span>
                            <span class="font-bold text-emerald-300 text-right">{{ trf.to_room?.name || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-800/70 text-[11px]">
                            <span class="text-slate-400">PIC: {{ trf.previous_pic || '-' }} &rarr; <strong class="text-white">{{ trf.new_pic || '-' }}</strong></span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1 text-[11px] text-slate-400">
                        <span class="font-mono">{{ formatDateTime(trf.transfer_date) }}</span>
                        <button
                            @click="openDetailModal(trf)"
                            class="px-3 py-1.5 rounded-xl bg-cyan-500/15 border border-cyan-500/30 text-cyan-300 font-bold text-xs"
                        >
                            Detail / Cetak PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabel Utama Pemindahan Aset (Exact Columns from Section 1) -->
            <div class="hidden md:block print:block bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-navy-950/90 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-3.5 px-4">Nomor Transfer</th>
                            <th class="py-3.5 px-4">Kode Aset</th>
                            <th class="py-3.5 px-4">Nama Aset</th>
                            <th class="py-3.5 px-4">Lokasi Asal</th>
                            <th class="py-3.5 px-4">Lokasi Tujuan</th>
                            <th class="py-3.5 px-4">PIC Sebelumnya</th>
                            <th class="py-3.5 px-4">PIC Baru</th>
                            <th class="py-3.5 px-4">Tanggal Pemindahan</th>
                            <th class="py-3.5 px-4">Petugas</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        <tr v-if="loading">
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                Memuat data riwayat pemindahan aset...
                            </td>
                        </tr>
                        <tr v-else-if="!transfers.length">
                            <td colspan="11" class="py-12 text-center text-slate-400">
                                Tidak ada data pemindahan aset yang sesuai filter.
                            </td>
                        </tr>
                        <tr
                            v-for="trf in transfers"
                            :key="trf.id"
                            class="hover:bg-slate-800/35 transition"
                        >
                            <!-- Nomor Transfer -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <button
                                    @click="openDetailModal(trf)"
                                    class="font-mono font-bold text-cyan-400 hover:text-cyan-300 hover:underline"
                                >
                                    {{ trf.transfer_number }}
                                </button>
                                <div class="text-[10px] text-slate-400">{{ trf.transfer_reason }}</div>
                            </td>

                            <!-- Kode Aset -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <router-link
                                    v-if="trf.asset"
                                    :to="`/assets/${trf.asset.id}`"
                                    class="font-mono font-bold px-2 py-0.5 rounded bg-slate-800 text-cyan-300 border border-slate-700 hover:border-cyan-500/40"
                                >
                                    {{ trf.asset.asset_code }}
                                </router-link>
                                <span v-else>-</span>
                            </td>

                            <!-- Nama Aset -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white">{{ trf.asset?.name || '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ trf.asset?.category || '' }}</div>
                            </td>

                            <!-- Lokasi Asal -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-amber-500/10 text-amber-300 border border-amber-500/25 font-semibold">
                                    {{ trf.from_room?.name || '-' }}
                                </span>
                            </td>

                            <!-- Lokasi Tujuan -->
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-300 border border-emerald-500/25 font-semibold">
                                    {{ trf.to_room?.name || '-' }}
                                </span>
                            </td>

                            <!-- PIC Sebelumnya -->
                            <td class="py-3.5 px-4 text-slate-300">
                                {{ trf.previous_pic || '-' }}
                            </td>

                            <!-- PIC Baru -->
                            <td class="py-3.5 px-4 font-semibold text-white">
                                {{ trf.new_pic || '-' }}
                            </td>

                            <!-- Tanggal Pemindahan -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px] text-slate-300">
                                {{ formatDateTime(trf.transfer_date) }}
                            </td>

                            <!-- Petugas -->
                            <td class="py-3.5 px-4 text-slate-300">
                                {{ trf.creator?.name || 'Administrator' }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                    :class="{
                                        'bg-emerald-500/15 text-emerald-300 border-emerald-500/30': trf.status === 'Selesai',
                                        'bg-amber-500/15 text-amber-300 border-amber-500/30': trf.status === 'Menunggu',
                                        'bg-rose-500/15 text-rose-300 border-rose-500/30': trf.status === 'Dibatalkan',
                                    }"
                                >
                                    {{ trf.status }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-right no-print">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        @click="openDetailModal(trf)"
                                        class="px-2.5 py-1 rounded-lg bg-cyan-500/15 hover:bg-cyan-500/25 border border-cyan-500/30 text-cyan-300 font-semibold text-[11px] transition"
                                    >
                                        Detail / Cetak
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Modal Form Pemindahan Aset -->
        <AssetTransferFormModal
            :show="showFormModal"
            :assets="allAssets"
            :rooms="rooms"
            @close="showFormModal = false"
            @saved="handleTransferSaved"
        />

        <!-- Modal Detail & Cetak Bukti Pemindahan -->
        <AssetTransferDetailModal
            :show="showDetailModal"
            :transfer="selectedTransfer"
            @close="showDetailModal = false"
            @updated="fetchTransfers"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import * as XLSX from 'xlsx';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import AssetTransferFormModal from '@/components/AssetTransferFormModal.vue';
import AssetTransferDetailModal from '@/components/AssetTransferDetailModal.vue';
import {
    ArrowLeftRight,
    Download,
    Printer,
    Plus,
    Search,
    ArrowDownLeft,
    ArrowUpRight,
    Calendar,
} from 'lucide-vue-next';

const auth = useAuthStore();
const loading = ref(false);

const transfers = ref([]);
const rooms = ref([]);
const allAssets = ref([]);
const staffList = ref([]);

const stats = ref({
    total_transfers: 0,
    this_month_transfers: 0,
    today_transfers: 0,
    pending_transfers: 0,
    cancelled_transfers: 0,
    distinct_assets_transferred: 0,
    top_incoming_rooms: [],
    top_outgoing_rooms: [],
    monthly_recap: [],
});

const categories = [
    'Komputer / PC',
    'Laptop',
    'Printer',
    'Switch',
    'Router',
    'Server',
    'Access Point',
    'UPS',
    'Perangkat IT lainnya',
];

const filters = reactive({
    search: '',
    start_date: '',
    end_date: '',
    from_room_id: '',
    to_room_id: '',
    category: '',
    created_by: '',
    status: '',
});

const showFormModal = ref(false);
const showDetailModal = ref(false);
const selectedTransfer = ref(null);

const hasActiveFilters = computed(() =>
    !!(
        filters.search ||
        filters.start_date ||
        filters.end_date ||
        filters.from_room_id ||
        filters.to_room_id ||
        filters.category ||
        filters.created_by ||
        filters.status
    )
);

let debounceTimer = null;
function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchTransfers();
    }, 300);
}

function resetFilters() {
    filters.search = '';
    filters.start_date = '';
    filters.end_date = '';
    filters.from_room_id = '';
    filters.to_room_id = '';
    filters.category = '';
    filters.created_by = '';
    filters.status = '';
    fetchTransfers();
}

async function fetchTransfers() {
    loading.value = true;
    try {
        const { data } = await api.get('/asset-transfers', { params: filters });
        transfers.value = data.data || [];
        stats.value = data.stats || stats.value;
        staffList.value = data.staff_list || [];
    } finally {
        loading.value = false;
    }
}

async function fetchRoomsAndAssets() {
    const [roomsRes, assetsRes] = await Promise.all([
        api.get('/rooms'),
        api.get('/assets', { params: { all: 1 } }),
    ]);
    rooms.value = roomsRes.data.data || [];
    allAssets.value = assetsRes.data.data || [];
}

async function handleTransferSaved() {
    await Promise.all([fetchTransfers(), fetchRoomsAndAssets()]);
}

function openDetailModal(trf) {
    selectedTransfer.value = trf;
    showDetailModal.value = true;
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

function exportTransferExcel() {
    const rows = transfers.value.map((t, idx) => ({
        No: idx + 1,
        'Nomor Transfer': t.transfer_number,
        'Kode Aset': t.asset?.asset_code || '-',
        'Nama Aset': t.asset?.name || '-',
        'Kategori Aset': t.asset?.category || '-',
        'Lokasi Asal': t.from_room?.name || '-',
        'Lokasi Tujuan': t.to_room?.name || '-',
        'PIC Sebelumnya': t.previous_pic || '-',
        'PIC Baru': t.new_pic || '-',
        'Tanggal Pemindahan': t.transfer_date,
        'Alasan Pemindahan': t.transfer_reason,
        Keterangan: t.description || '-',
        'Petugas Pencatat': t.creator?.name || '-',
        Status: t.status,
    }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(rows), 'Riwayat Pemindahan Aset');
    XLSX.writeFile(wb, `Laporan_Pemindahan_Aset_IT_RSUD_${new Date().toISOString().slice(0, 10)}.xlsx`);
    auth.notify(`Export Excel berhasil (${rows.length} transaksi pemindahan).`, 'success');
}

function printTransferReport() {
    window.print();
}

onMounted(() => {
    fetchTransfers();
    fetchRoomsAndAssets();
});
</script>

