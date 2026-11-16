<template>
    <div class="space-y-6">
        <!-- Print Header (Visible when printing PDF) -->
        <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-extrabold uppercase">LAPORAN REKAPITULASI INVENTARIS ASET IT RUMAH SAKIT</h1>
                    <p class="text-xs text-slate-700">Sistem Informasi Manajemen Aset Teknologi Informasi (SIM-ASET IT RSUD)</p>
                </div>
                <div class="text-right text-xs font-mono">
                    <div>Tanggal Cetak: {{ new Date().toLocaleDateString('id-ID') }}</div>
                    <div>Total Aset Terfilter: {{ rekapKondisi.Total || 0 }} Unit</div>
                </div>
            </div>
        </div>

        <!-- Filter & Export Bar -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 space-y-4 no-print">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-extrabold text-white flex items-center gap-2">
                        <FileSpreadsheet class="w-5 h-5 text-indigo-400" />
                        <span>Laporan &amp; Rekapitulasi Inventaris Aset IT</span>
                    </h2>
                    <p class="text-xs text-slate-400">
                        Filter laporan berdasarkan kategori, ruangan, kepemilikan, kondisi, dan tahun perolehan.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <router-link
                        to="/transfers"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-500/15 hover:bg-teal-500/25 border border-teal-500/30 text-teal-300 font-bold text-xs transition"
                    >
                        <span>Laporan Pemindahan Aset &rarr;</span>
                    </router-link>
                    <button
                        @click="exportExcelReport"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 font-bold text-xs transition"
                    >
                        <Download class="w-4 h-4" />
                        <span>Export Excel (.xlsx)</span>
                    </button>
                    <button
                        @click="printPdfReport"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak PDF / Laporan</span>
                    </button>
                </div>
            </div>

            <!-- 5 Required Filters (Section 12) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 pt-2 border-t border-slate-800/80">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">1. Filter Kategori</label>
                    <select v-model="filters.category" @change="fetchReports" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Semua Kategori</option>
                        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">2. Filter Ruangan</label>
                    <select v-model="filters.room_id" @change="fetchReports" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Semua Ruangan</option>
                        <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">3. Filter Kepemilikan</label>
                    <select v-model="filters.ownership" @change="fetchReports" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Semua Kepemilikan</option>
                        <option value="Milik RSUD">Milik RSUD</option>
                        <option value="KSO">KSO</option>
                        <option value="Milik Pihak Ketiga">Milik Pihak Ketiga</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">4. Filter Kondisi</label>
                    <select v-model="filters.condition" @change="fetchReports" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                        <option value="">Semua Kondisi</option>
                        <option value="Baik">Baik</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Rusak">Rusak</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">5. Tahun Perolehan</label>
                    <div class="flex gap-2">
                        <select v-model="filters.acquisition_year" @change="fetchReports" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
                            <option value="">Semua Tahun</option>
                            <option v-for="yr in availableYears" :key="yr" :value="yr">{{ yr }}</option>
                        </select>
                        <button
                            v-if="hasFilters"
                            @click="resetFilters"
                            class="px-3 py-2 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs font-semibold shrink-0"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rekap Kondisi Aset & Kepemilikan Summary Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-navy-900/90 border border-cyan-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Total Aset Terfilter</div>
                <div class="text-2xl font-mono font-extrabold text-white mt-1">{{ rekapKondisi.Total || 0 }}</div>
            </div>
            <div class="bg-navy-900/90 border border-emerald-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Kondisi Baik</div>
                <div class="text-2xl font-mono font-extrabold text-emerald-400 mt-1">{{ rekapKondisi.Baik || 0 }}</div>
            </div>
            <div class="bg-navy-900/90 border border-amber-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Kondisi Maintenance</div>
                <div class="text-2xl font-mono font-extrabold text-amber-400 mt-1">{{ rekapKondisi.Maintenance || 0 }}</div>
            </div>
            <div class="bg-navy-900/90 border border-rose-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Kondisi Rusak</div>
                <div class="text-2xl font-mono font-extrabold text-rose-400 mt-1">{{ rekapKondisi.Rusak || 0 }}</div>
            </div>
        </div>

        <!-- 3 Tabel Rekapitulasi (Kategori, Kepemilikan, Per Ruangan) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Rekapitulasi Jumlah Aset per Kategori -->
            <div class="lg:col-span-7 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-white mb-3">1. Rekapitulasi Jumlah &amp; Kondisi Aset per Kategori</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                                <th class="py-2.5 px-3">Kategori</th>
                                <th class="py-2.5 px-3 text-center">Baik</th>
                                <th class="py-2.5 px-3 text-center">Maintenance</th>
                                <th class="py-2.5 px-3 text-center">Rusak</th>
                                <th class="py-2.5 px-3 text-center">RSUD</th>
                                <th class="py-2.5 px-3 text-center">KSO/Mitra</th>
                                <th class="py-2.5 px-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="row in rekapKategori" :key="row.category" class="hover:bg-slate-800/30">
                                <td class="py-2.5 px-3 font-semibold text-white">{{ row.category }}</td>
                                <td class="py-2.5 px-3 text-center text-emerald-400 font-mono">{{ row.baik }}</td>
                                <td class="py-2.5 px-3 text-center text-amber-400 font-mono">{{ row.maintenance }}</td>
                                <td class="py-2.5 px-3 text-center text-rose-400 font-mono">{{ row.rusak }}</td>
                                <td class="py-2.5 px-3 text-center text-cyan-300 font-mono">{{ row.milik_rsud }}</td>
                                <td class="py-2.5 px-3 text-center text-purple-300 font-mono">{{ row.kso_mitra }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-white">{{ row.total }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rekap Kepemilikan Aset -->
            <div class="lg:col-span-5 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white mb-3">2. Rekap Kepemilikan Aset</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                                    <th class="py-2.5 px-3">Kepemilikan</th>
                                    <th class="py-2.5 px-3 text-center">Baik</th>
                                    <th class="py-2.5 px-3 text-center">Maint.</th>
                                    <th class="py-2.5 px-3 text-center">Rusak</th>
                                    <th class="py-2.5 px-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="row in rekapKepemilikan" :key="row.ownership">
                                    <td class="py-3 px-3 font-bold text-white">{{ row.ownership }}</td>
                                    <td class="py-3 px-3 text-center text-emerald-400 font-mono">{{ row.baik }}</td>
                                    <td class="py-3 px-3 text-center text-amber-400 font-mono">{{ row.maintenance }}</td>
                                    <td class="py-3 px-3 text-center text-rose-400 font-mono">{{ row.rusak }}</td>
                                    <td class="py-3 px-3 text-right font-mono font-extrabold text-cyan-300">{{ row.total }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-navy-950 border border-slate-800 text-xs text-slate-400 mt-4">
                    Laporan ini dihasilkan secara dinamis dari database MySQL dan mencakup seluruh inventaris aktif sesuai filter.
                </div>
            </div>
        </div>

        <!-- Rekap Aset per Ruangan -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white mb-3">3. Rekapitulasi Aset per Ruangan / Instalasi</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-2.5 px-3">Kode</th>
                            <th class="py-2.5 px-3">Nama Ruangan</th>
                            <th class="py-2.5 px-3">Gedung / Lokasi</th>
                            <th class="py-2.5 px-3">Penanggung Jawab</th>
                            <th class="py-2.5 px-3 text-center">Baik</th>
                            <th class="py-2.5 px-3 text-center">Maintenance</th>
                            <th class="py-2.5 px-3 text-center">Rusak</th>
                            <th class="py-2.5 px-3 text-right">Jumlah Aset</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="rm in rekapRuangan" :key="rm.room_id" class="hover:bg-slate-800/30">
                            <td class="py-2.5 px-3 font-mono text-sky-400 font-bold">{{ rm.code }}</td>
                            <td class="py-2.5 px-3 font-bold text-white">{{ rm.name }}</td>
                            <td class="py-2.5 px-3 text-slate-300">{{ rm.building }}</td>
                            <td class="py-2.5 px-3 text-slate-300">{{ rm.pic_name || '-' }}</td>
                            <td class="py-2.5 px-3 text-center font-mono text-emerald-400">{{ rm.baik }}</td>
                            <td class="py-2.5 px-3 text-center font-mono text-amber-400">{{ rm.maintenance }}</td>
                            <td class="py-2.5 px-3 text-center font-mono text-rose-400">{{ rm.rusak }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-extrabold text-white">{{ rm.total }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rincian Daftar Aset Lengkap -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5">
            <h3 class="text-sm font-bold text-white mb-3">
                4. Daftar Rincian Inventaris Aset ({{ assets.length }} Perangkat)
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-2.5 px-3">No</th>
                            <th class="py-2.5 px-3">Kode Aset</th>
                            <th class="py-2.5 px-3">Nama Aset</th>
                            <th class="py-2.5 px-3">Kategori</th>
                            <th class="py-2.5 px-3">Merk / Model / SN</th>
                            <th class="py-2.5 px-3">IP Address</th>
                            <th class="py-2.5 px-3">Ruangan</th>
                            <th class="py-2.5 px-3">Kepemilikan</th>
                            <th class="py-2.5 px-3">Tahun</th>
                            <th class="py-2.5 px-3">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <tr v-for="(item, idx) in assets" :key="item.id" class="hover:bg-slate-800/30">
                            <td class="py-2.5 px-3 font-mono text-slate-400">{{ idx + 1 }}</td>
                            <td class="py-2.5 px-3 font-mono font-bold text-cyan-400">{{ item.asset_code }}</td>
                            <td class="py-2.5 px-3 font-semibold text-white">{{ item.name }}</td>
                            <td class="py-2.5 px-3 text-slate-300">{{ item.category }}</td>
                            <td class="py-2.5 px-3 text-slate-300">
                                {{ item.brand }} {{ item.model }}
                                <span v-if="item.serial_number" class="block text-[10px] font-mono text-slate-400">SN: {{ item.serial_number }}</span>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-cyan-300">{{ item.ip_address || '-' }}</td>
                            <td class="py-2.5 px-3 text-slate-200">{{ item.room?.name || '-' }}</td>
                            <td class="py-2.5 px-3">
                                <span>{{ item.ownership }}</span>
                                <span v-if="item.partner_name" class="block text-[10px] text-purple-300">{{ item.partner_name }}</span>
                            </td>
                            <td class="py-2.5 px-3 font-mono text-slate-300">{{ item.acquisition_year || '-' }}</td>
                            <td class="py-2.5 px-3 font-bold" :class="{
                                'text-emerald-400': item.condition === 'Baik',
                                'text-amber-400': item.condition === 'Maintenance',
                                'text-rose-400': item.condition === 'Rusak',
                            }">
                                {{ item.condition }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import * as XLSX from 'xlsx';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { FileSpreadsheet, Download, Printer } from 'lucide-vue-next';

const auth = useAuthStore();

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

const rooms = ref([]);
const assets = ref([]);
const rekapKategori = ref([]);
const rekapKondisi = ref({ Baik: 0, Rusak: 0, Maintenance: 0, Total: 0 });
const rekapKepemilikan = ref([]);
const rekapRuangan = ref([]);
const availableYears = ref([]);

const filters = reactive({
    category: '',
    room_id: '',
    ownership: '',
    condition: '',
    acquisition_year: '',
});

const hasFilters = computed(() =>
    !!(filters.category || filters.room_id || filters.ownership || filters.condition || filters.acquisition_year)
);

function resetFilters() {
    filters.category = '';
    filters.room_id = '';
    filters.ownership = '';
    filters.condition = '';
    filters.acquisition_year = '';
    fetchReports();
}

async function fetchRooms() {
    const { data } = await api.get('/rooms');
    rooms.value = data.data || [];
}

async function fetchReports() {
    const { data } = await api.get('/reports', { params: filters });
    assets.value = data.assets || [];
    rekapKategori.value = data.rekap_kategori || [];
    rekapKondisi.value = data.rekap_kondisi || { Baik: 0, Rusak: 0, Maintenance: 0, Total: 0 };
    rekapKepemilikan.value = data.rekap_kepemilikan || [];
    rekapRuangan.value = data.rekap_ruangan || [];
    availableYears.value = data.available_years || [];
}

function exportExcelReport() {
    const wb = XLSX.utils.book_new();

    // Sheet 1: Daftar Rincian Aset
    const detailRows = assets.value.map((item, idx) => ({
        No: idx + 1,
        'Kode Aset': item.asset_code,
        'Nama Aset': item.name,
        Kategori: item.category,
        Merk: item.brand || '-',
        Model: item.model || '-',
        'Serial Number': item.serial_number || '-',
        'IP Address': item.ip_address || '-',
        'MAC Address': item.mac_address || '-',
        Spesifikasi: item.specifications || '-',
        Ruangan: item.room?.name || '-',
        Gedung: item.room?.building || '-',
        'Penanggung Jawab': item.pic || '-',
        Kepemilikan: item.ownership,
        'Mitra KSO/Pihak Ke-3': item.partner_name || '-',
        Kondisi: item.condition,
        'Tahun Perolehan': item.acquisition_year || '-',
        Keterangan: item.notes || '-',
    }));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(detailRows), 'Rincian Aset');

    // Sheet 2: Rekap Kategori
    const catRows = rekapKategori.value.map((r) => ({
        Kategori: r.category,
        Baik: r.baik,
        Maintenance: r.maintenance,
        Rusak: r.rusak,
        'Milik RSUD': r.milik_rsud,
        'KSO & Pihak Ketiga': r.kso_mitra,
        'Total Unit': r.total,
    }));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(catRows), 'Rekap Kategori');

    // Sheet 3: Rekap Ruangan
    const roomRows = rekapRuangan.value.map((r) => ({
        'Kode Ruangan': r.code,
        'Nama Ruangan': r.name,
        Gedung: r.building,
        'Penanggung Jawab': r.pic_name || '-',
        Baik: r.baik,
        Maintenance: r.maintenance,
        Rusak: r.rusak,
        'Total Aset': r.total,
    }));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(roomRows), 'Rekap Per Ruangan');

    // Sheet 4: Rekap Kepemilikan
    const ownRows = rekapKepemilikan.value.map((r) => ({
        Kepemilikan: r.ownership,
        Baik: r.baik,
        Maintenance: r.maintenance,
        Rusak: r.rusak,
        'Total Unit': r.total,
    }));
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(ownRows), 'Rekap Kepemilikan');

    XLSX.writeFile(wb, `Laporan_Rekap_Aset_IT_RSUD_${new Date().toISOString().slice(0, 10)}.xlsx`);
    auth.notify('Laporan Excel lengkap (4 Sheet Rekapitulasi) berhasil diunduh.', 'success');
}

function printPdfReport() {
    window.print();
}

onMounted(() => {
    fetchRooms();
    fetchReports();
});
</script>

