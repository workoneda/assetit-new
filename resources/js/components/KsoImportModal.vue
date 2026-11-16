<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/75 backdrop-blur-sm no-print">
        <div class="bg-navy-900 border-t sm:border border-slate-700/80 rounded-t-2xl sm:rounded-2xl max-w-5xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[94dvh] sm:max-h-[92vh]">
            <!-- Modal Header -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-800 flex items-center justify-between bg-navy-950/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400 shrink-0">
                        <FileSpreadsheet class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-white">
                            Import &amp; Pemetaan Data Inventaris Mitra KSO (.xlsx / .xls / .csv)
                        </h3>
                        <p class="text-[11px] sm:text-xs text-slate-400">
                            Data disimpan ke sesi rekonsiliasi sementara dan tidak mengubah database aset utama secara otomatis
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-5 text-xs">
                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/40 text-rose-200 flex items-center gap-2.5">
                    <AlertCircle class="w-4 h-4 text-rose-400 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Step 1: Pilih Mitra KSO, Periode & Upload File -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-navy-950/90 border border-slate-800">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">
                            Mitra KSO <span class="text-rose-400">*</span>
                        </label>
                        <select
                            v-model="selectedPartnerName"
                            class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-cyan-500"
                        >
                            <option v-for="p in partners" :key="p.id" :value="p.name">
                                {{ p.name }} ({{ p.code }})
                            </option>
                            <option value="Semua Mitra KSO">Semua Mitra KSO (Seluruh Aset KSO)</option>
                            <option value="__NEW__">+ Tambah Nama Mitra KSO Baru...</option>
                        </select>
                        <input
                            v-if="selectedPartnerName === '__NEW__'"
                            v-model="customPartnerName"
                            type="text"
                            placeholder="Ketik nama mitra KSO baru..."
                            class="mt-2 w-full bg-navy-900 border border-purple-500/50 rounded-xl px-3 py-2 text-white"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1.5">
                            Periode Rekonsiliasi <span class="text-rose-400">*</span>
                        </label>
                        <input
                            v-model="period"
                            type="text"
                            placeholder="Contoh: Semester II 2026 / November 2026"
                            class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white focus:outline-none focus:border-cyan-500"
                        />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="font-semibold text-slate-300">File Excel / CSV Mitra <span class="text-rose-400">*</span></label>
                            <button
                                type="button"
                                @click="downloadKsoTemplate"
                                class="text-[11px] font-bold text-cyan-400 hover:underline flex items-center gap-1"
                            >
                                <Download class="w-3 h-3" />
                                <span>Unduh Contoh File KSO</span>
                            </button>
                        </div>
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleFileSelect"
                            class="block w-full text-[11px] text-slate-400 file:mr-2.5 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30 cursor-pointer"
                        />
                    </div>
                </div>

                <!-- Step 2: Pemetaan Kolom (Column Mapping) -->
                <div v-if="rawHeaders.length" class="p-4 rounded-2xl bg-navy-950/90 border border-purple-500/30 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-purple-300 flex items-center gap-2">
                                <Sliders class="w-4 h-4" />
                                <span>Pemetaan Kolom Otomatis &amp; Kustomisasi (Column Mapping)</span>
                            </h4>
                            <p class="text-[11px] text-slate-400">
                                Sistem telah mendeteksi kolom secara otomatis. Anda dapat menyesuaikan pemetaan kolom di bawah ini bila diperlukan.
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-lg bg-purple-500/15 border border-purple-500/30 text-purple-300 font-mono text-[11px]">
                            File: {{ fileName }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5">
                        <div v-for="field in mappingFields" :key="field.key" class="p-2.5 rounded-xl bg-navy-900 border border-slate-800">
                            <label class="block text-[11px] font-bold text-slate-300 mb-1">
                                {{ field.label }}
                                <span v-if="field.required" class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="columnMap[field.key]"
                                class="w-full bg-navy-950 border border-slate-700 rounded-lg px-2 py-1.5 text-[11px] text-white"
                            >
                                <option value="">-- Tidak Ada --</option>
                                <option v-for="hdr in rawHeaders" :key="hdr" :value="hdr">{{ hdr }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Statistik Validasi & Preview Data Sebelum Diproses -->
                <div v-if="mappedPreviewRows.length" class="space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div class="p-3 rounded-xl bg-navy-950 border border-cyan-500/30">
                            <div class="text-[11px] text-slate-400">Total Baris Terbaca</div>
                            <div class="text-xl font-mono font-extrabold text-white">{{ mappedPreviewRows.length }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-navy-950 border border-emerald-500/30">
                            <div class="text-[11px] text-slate-400">Data Valid (SN Tersedia)</div>
                            <div class="text-xl font-mono font-extrabold text-emerald-400">{{ validRowCount }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-navy-950 border border-amber-500/30">
                            <div class="text-[11px] text-slate-400">SN Kosong / Tidak Valid</div>
                            <div class="text-xl font-mono font-extrabold text-amber-400">{{ invalidRowCount }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-navy-950 border border-purple-500/30">
                            <div class="text-[11px] text-slate-400">SN Terdeteksi Ganda di File</div>
                            <div class="text-xl font-mono font-extrabold text-purple-400">{{ duplicateInFileCount }}</div>
                        </div>
                    </div>

                    <div class="bg-navy-950 border border-slate-800 rounded-2xl overflow-hidden">
                        <div class="px-4 py-2.5 bg-navy-900/90 border-b border-slate-800 flex items-center justify-between">
                            <span class="font-bold text-slate-200">
                                Preview Normalisasi Data Sebelum Rekonsiliasi (Menampilkan {{ Math.min(20, mappedPreviewRows.length) }} dari {{ mappedPreviewRows.length }} baris)
                            </span>
                            <span class="text-[11px] text-cyan-400">Spasi &amp; Huruf Besar/Kecil Telah Dinormalisasi</span>
                        </div>
                        <div class="overflow-x-auto max-h-60">
                            <table class="w-full text-left border-collapse text-[11px]">
                                <thead>
                                    <tr class="border-b border-slate-800 text-slate-400 bg-navy-950">
                                        <th class="py-2 px-3">No</th>
                                        <th class="py-2 px-3">Serial Number (Normalized)</th>
                                        <th class="py-2 px-3">Model Perangkat</th>
                                        <th class="py-2 px-3">Merk</th>
                                        <th class="py-2 px-3">Kategori</th>
                                        <th class="py-2 px-3">Lokasi / Ruangan</th>
                                        <th class="py-2 px-3">Validasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr v-for="(r, i) in mappedPreviewRows.slice(0, 20)" :key="i" class="hover:bg-slate-900/60">
                                        <td class="py-2 px-3 font-mono text-slate-400">{{ r.item_number }}</td>
                                        <td class="py-2 px-3 font-mono font-bold" :class="r.normalized_sn ? 'text-cyan-300' : 'text-rose-400'">
                                            {{ r.normalized_sn || '(SN KOSONG)' }}
                                        </td>
                                        <td class="py-2 px-3 text-white font-semibold">{{ r.normalized_model || '-' }}</td>
                                        <td class="py-2 px-3 text-slate-300">{{ r.brand || '-' }}</td>
                                        <td class="py-2 px-3 text-slate-300">{{ r.category || '-' }}</td>
                                        <td class="py-2 px-3 text-slate-300">{{ r.location || '-' }}</td>
                                        <td class="py-2 px-3">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                                :class="r.normalized_sn ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300'"
                                            >
                                                {{ r.normalized_sn ? 'Valid' : 'SN Kosong' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-t border-slate-800 bg-navy-950/90 flex flex-wrap items-center justify-between gap-3">
                <div class="text-[11px] text-slate-400">
                    Data aset utama tetap aman dan tidak diubah saat proses pencocokan dijalankan.
                </div>
                <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:text-white text-xs font-semibold"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitReconciliation"
                        :disabled="submitting || !mappedPreviewRows.length"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 disabled:opacity-40 text-slate-950 font-extrabold text-xs shadow-lg shadow-cyan-500/20 transition"
                    >
                        <Play class="w-4 h-4 fill-current" />
                        <span>{{ submitting ? 'Memproses Pencocokan...' : `Jalankan Rekonsiliasi (${mappedPreviewRows.length} Data)` }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import * as XLSX from 'xlsx';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { FileSpreadsheet, X, AlertCircle, Download, Sliders, Play } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    partners: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'created']);
const auth = useAuthStore();

const selectedPartnerName = ref('MITRA ABADI');
const customPartnerName = ref('');
const period = ref(`Semester II ${new Date().getFullYear()}`);
const fileName = ref('');
const rawRows = ref([]);
const rawHeaders = ref([]);
const submitting = ref(false);
const errorMessage = ref('');
const fileInput = ref(null);

const mappingFields = [
    { key: 'item_number', label: '1. Kolom Nomor', required: false },
    { key: 'sn', label: '2. Serial Number (SN)', required: true },
    { key: 'model', label: '3. Model Perangkat', required: true },
    { key: 'brand', label: '4. Merk', required: false },
    { key: 'category', label: '5. Kategori', required: false },
    { key: 'location', label: '6. Lokasi / Ruangan', required: false },
    { key: 'notes', label: '7. Keterangan', required: false },
];

const columnMap = reactive({
    item_number: '',
    sn: '',
    model: '',
    brand: '',
    category: '',
    location: '',
    notes: '',
});

const synonyms = {
    item_number: ['no', 'nomor', 'no.', 'urut', 'id', 'nomor urut'],
    sn: ['serial number', 'serial number (sn)', 'sn', 's/n', 'no seri', 'nomor seri', 'serial_number', 'serial'],
    model: ['model', 'model perangkat', 'tipe', 'type', 'tipe perangkat', 'nama perangkat', 'perangkat'],
    brand: ['merk', 'merek', 'brand', 'pabrikan', 'vendor', 'manufaktur'],
    category: ['kategori', 'jenis', 'jenis perangkat', 'category'],
    location: ['lokasi', 'ruangan', 'lokasi / ruangan', 'penempatan', 'instalasi', 'unit', 'room'],
    notes: ['keterangan', 'catatan', 'notes', 'deskripsi', 'kondisi', 'status'],
};

function normalizeText(val) {
    if (val === null || val === undefined) return '';
    const cleaned = String(val).replace(/\s+/g, ' ').trim();
    const upper = cleaned.toUpperCase();
    if (['-', '--', 'N/A', 'NA', 'NULL', 'NONE', 'KOSONG', 'TIDAK ADA', '0'].includes(upper)) {
        return '';
    }
    return upper;
}

function autoMapColumns(headers) {
    Object.keys(columnMap).forEach((k) => (columnMap[k] = ''));

    for (const field of Object.keys(synonyms)) {
        const candidates = synonyms[field];
        const found = headers.find((h) => {
            const normH = String(h).toLowerCase().trim();
            return candidates.some((c) => normH === c || normH.includes(c));
        });
        if (found) {
            columnMap[field] = found;
        }
    }
}

function handleFileSelect(e) {
    errorMessage.value = '';
    const file = e.target.files?.[0];
    if (!file) return;

    fileName.value = file.name;
    const reader = new FileReader();
    reader.onload = (evt) => {
        try {
            const data = new Uint8Array(evt.target.result);
            const wb = XLSX.read(data, { type: 'array' });
            const firstSheet = wb.Sheets[wb.SheetNames[0]];
            const json = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });

            if (!json.length) {
                errorMessage.value = 'File Excel/CSV kosong atau tidak memiliki baris data.';
                rawRows.value = [];
                rawHeaders.value = [];
                return;
            }

            rawRows.value = json;
            rawHeaders.value = Object.keys(json[0]);
            autoMapColumns(rawHeaders.value);
        } catch {
            errorMessage.value = 'Gagal membaca file Excel/CSV. Pastikan format .xlsx, .xls, atau .csv valid.';
        }
    };
    reader.readAsArrayBuffer(file);
}

const mappedPreviewRows = computed(() => {
    return rawRows.value.map((row, idx) => {
        const rawSn = columnMap.sn ? String(row[columnMap.sn] ?? '').trim() : '';
        const rawModel = columnMap.model ? String(row[columnMap.model] ?? '').trim() : '';
        return {
            row_number: idx + 1,
            item_number: columnMap.item_number ? String(row[columnMap.item_number] ?? idx + 1).trim() : String(idx + 1),
            sn: rawSn,
            normalized_sn: normalizeText(rawSn),
            model: rawModel,
            normalized_model: normalizeText(rawModel),
            brand: columnMap.brand ? String(row[columnMap.brand] ?? '').trim() : '',
            category: columnMap.category ? String(row[columnMap.category] ?? '').trim() : '',
            location: columnMap.location ? String(row[columnMap.location] ?? '').trim() : '',
            notes: columnMap.notes ? String(row[columnMap.notes] ?? '').trim() : '',
            raw_payload: row,
        };
    });
});

const validRowCount = computed(() => mappedPreviewRows.value.filter((r) => r.normalized_sn !== '').length);
const invalidRowCount = computed(() => mappedPreviewRows.value.filter((r) => r.normalized_sn === '').length);

const duplicateInFileCount = computed(() => {
    const counts = {};
    for (const r of mappedPreviewRows.value) {
        if (r.normalized_sn) {
            counts[r.normalized_sn] = (counts[r.normalized_sn] || 0) + 1;
        }
    }
    return mappedPreviewRows.value.filter((r) => r.normalized_sn && counts[r.normalized_sn] > 1).length;
});

function downloadKsoTemplate() {
    const sampleRows = [
        {
            Nomor: 1,
            'Serial Number (SN)': 'XE4Q246212',
            'Model Perangkat': 'L3211',
            Merk: 'Epson',
            Kategori: 'Printer',
            'Lokasi / Ruangan': 'Ruang Pendaftaran',
            Keterangan: 'Unit KSO Mitra Abadi - Sesuai Kontrak',
        },
        {
            Nomor: 2,
            'Serial Number (SN)': 'X9LU288129',
            'Model Perangkat': 'EPSON L121',
            Merk: 'Epson',
            Kategori: 'Printer',
            'Lokasi / Ruangan': 'Poli Klinik',
            Keterangan: 'Unit KSO Aktif',
        },
        {
            Nomor: 3,
            'Serial Number (SN)': 'X9LU244478',
            'Model Perangkat': 'EPSON L3210 SERIES',
            Merk: 'Epson',
            Kategori: 'Printer',
            'Lokasi / Ruangan': 'Instalasi Farmasi',
            Keterangan: 'Contoh perbedaan model (Model Mismatch)',
        },
        {
            Nomor: 4,
            'Serial Number (SN)': 'KSO-NEW-2026-889',
            'Model Perangkat': 'EPSON L3250 WIFI',
            Merk: 'Epson',
            Kategori: 'Printer',
            'Lokasi / Ruangan': 'Ruang IGD',
            Keterangan: 'Unit KSO baru belum terdaftar di aplikasi (Missing in System)',
        },
        {
            Nomor: 5,
            'Serial Number (SN)': '',
            'Model Perangkat': 'EPSON L121',
            Merk: 'Epson',
            Kategori: 'Printer',
            'Lokasi / Ruangan': 'Ruang Kasir',
            Keterangan: 'Contoh SN kosong untuk pemeriksaan manual',
        },
    ];

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, XLSX.utils.json_to_sheet(sampleRows), 'Data Inventaris KSO');
    XLSX.writeFile(wb, 'Template_Inventaris_Mitra_KSO_RSUD.xlsx');
}

async function submitReconciliation() {
    errorMessage.value = '';
    const finalPartnerName =
        selectedPartnerName.value === '__NEW__' ? customPartnerName.value.trim() : selectedPartnerName.value;

    if (!finalPartnerName) {
        errorMessage.value = 'Silakan pilih atau isi nama Mitra KSO terlebih dahulu.';
        return;
    }

    submitting.value = true;
    try {
        const partnerObj = props.partners.find((p) => p.name === finalPartnerName);
        const { data } = await api.post('/kso-reconciliation/sessions', {
            kso_partner_id: partnerObj?.id || null,
            partner_name: finalPartnerName,
            file_name: fileName.value || 'Data_Mitra_KSO.xlsx',
            period: period.value,
            items: mappedPreviewRows.value,
        });

        auth.notify(data.message, 'success');
        emit('created', data.session);
        emit('close');
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Gagal menjalankan rekonsiliasi data KSO.';
    } finally {
        submitting.value = false;
    }
}

watch(
    () => props.show,
    (val) => {
        if (val) {
            errorMessage.value = '';
            rawRows.value = [];
            rawHeaders.value = [];
            fileName.value = '';
            if (fileInput.value) fileInput.value.value = '';
            if (props.partners.length && !selectedPartnerName.value) {
                selectedPartnerName.value = props.partners[0].name;
            }
        }
    }
);
</script>

