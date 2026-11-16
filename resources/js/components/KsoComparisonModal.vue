<template>
    <div v-if="show && result" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/75 backdrop-blur-sm no-print">
        <div class="bg-navy-900 border-t sm:border border-slate-700/80 rounded-t-2xl sm:rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[94dvh] sm:max-h-[92vh]">
            <!-- Header -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-800 flex items-center justify-between bg-navy-950/80">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0">
                        <GitCompare class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm sm:text-base font-bold text-white">
                                Detail Perbandingan Aset KSO (Baris #{{ result.row_number }})
                            </h3>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border"
                                :class="statusBadgeClass(result.status)"
                            >
                                {{ result.status }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-400">
                            Status Tindakan: <strong class="text-cyan-300">{{ result.action_status }}</strong>
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-4 sm:p-6 overflow-y-auto flex-1 space-y-5 text-xs">
                <!-- Legend Indikator Visual (Section 5) -->
                <div class="flex flex-wrap items-center justify-between gap-2 p-3 rounded-xl bg-navy-950 border border-slate-800">
                    <span class="font-bold text-slate-300">Indikator Perbandingan Parameter:</span>
                    <div class="flex flex-wrap items-center gap-3 text-[11px]">
                        <span class="inline-flex items-center gap-1.5 text-emerald-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
                            <span>Hijau: Sesuai (Match)</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-rose-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-400"></span>
                            <span>Merah: Berbeda (Mismatch)</span>
                        </span>
                        <span class="inline-flex items-center gap-1.5 text-amber-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span>Kuning: Data Kosong / Perlu Pemeriksaan</span>
                        </span>
                    </div>
                </div>

                <!-- TAMPILAN PERBANDINGAN DUA SISI (Section 5: Sisi Kiri DATA MITRA KSO vs Sisi Kanan DATA APLIKASI) -->
                <div class="space-y-2.5">
                    <!-- Column Headers -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
                        <div class="sm:col-span-5 p-3 rounded-xl bg-purple-950/30 border border-purple-500/40 font-extrabold text-purple-300 uppercase tracking-wider text-center">
                            SISI KIRI: DATA MITRA KSO
                        </div>
                        <div class="sm:col-span-2 hidden sm:flex items-center justify-center text-[11px] font-bold text-slate-400 uppercase">
                            Parameter
                        </div>
                        <div class="sm:col-span-5 p-3 rounded-xl bg-cyan-950/30 border border-cyan-500/40 font-extrabold text-cyan-300 uppercase tracking-wider text-center">
                            SISI KANAN: DATA APLIKASI IT ASSET MANAGEMENT
                        </div>
                    </div>

                    <!-- Field Rows: Serial Number, Model, Merk, Kategori, Lokasi, Keterangan -->
                    <div
                        v-for="row in comparisonRows"
                        :key="row.key"
                        class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-stretch p-2.5 rounded-xl border"
                        :class="rowContainerClass(row.state)"
                    >
                        <!-- Left: KSO Data -->
                        <div class="sm:col-span-5 p-2.5 rounded-lg bg-navy-950/90 border border-slate-800/80 flex flex-col justify-center">
                            <span class="text-[10px] text-purple-300 font-bold sm:hidden mb-0.5">{{ row.label }} (Mitra KSO):</span>
                            <span
                                class="font-bold break-all"
                                :class="row.mono ? 'font-mono text-sm text-white' : 'text-slate-100'"
                            >
                                {{ row.ksoValue || '(Kosong / Tidak Tersedia)' }}
                            </span>
                        </div>

                        <!-- Center Badge: Parameter Name & Visual Indicator -->
                        <div class="sm:col-span-2 flex sm:flex-col items-center justify-between sm:justify-center gap-1 px-2 text-center">
                            <span class="text-[11px] font-bold text-slate-300">{{ row.label }}</span>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold border"
                                :class="rowBadgeClass(row.state)"
                            >
                                {{ rowStateLabel(row.state) }}
                            </span>
                        </div>

                        <!-- Right: System Data -->
                        <div class="sm:col-span-5 p-2.5 rounded-lg bg-navy-950/90 border border-slate-800/80 flex flex-col justify-center">
                            <span class="text-[10px] text-cyan-300 font-bold sm:hidden mb-0.5">{{ row.label }} (Sistem RSUD):</span>
                            <span
                                class="font-bold break-all"
                                :class="row.mono ? 'font-mono text-sm text-cyan-300' : 'text-slate-100'"
                            >
                                {{ row.sysValue || '(Kosong / Belum Terdaftar)' }}
                            </span>
                            <span v-if="row.key === 'serial_number' && result.system_asset_code" class="text-[10px] font-mono text-slate-400 mt-0.5">
                                Kode Sistem: {{ result.system_asset_code }} &mdash; {{ result.system_name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Candidates / Duplicate SN List (For DUPLICATE SN or NEEDS MANUAL REVIEW) -->
                <div
                    v-if="result.candidates?.length"
                    class="p-4 rounded-xl bg-amber-950/20 border border-amber-500/40 space-y-2.5"
                >
                    <div class="font-bold text-amber-300 flex items-center gap-2">
                        <AlertTriangle class="w-4 h-4" />
                        <span v-if="result.status === 'DUPLICATE SN'">
                            Daftar Aset dengan Serial Number Sama (Deteksi Duplikat)
                        </span>
                        <span v-else>
                            Kandidat Pencocokan Berdasarkan Model &amp; Merk (Wajib Pemeriksaan Manual)
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(cand, cIdx) in result.candidates"
                            :key="cIdx"
                            class="p-3 rounded-lg bg-navy-950 border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                        >
                            <div>
                                <span class="font-mono font-bold text-cyan-400">{{ cand.asset_code }}</span>
                                <span class="font-bold text-white ml-2">{{ cand.name }}</span>
                                <div class="text-[11px] text-slate-400">
                                    SN: <strong class="font-mono text-slate-200">{{ cand.serial_number || '-' }}</strong> &bull;
                                    Model: {{ cand.model || '-' }} &bull;
                                    Lokasi: {{ cand.location || '-' }}
                                </div>
                            </div>
                            <router-link
                                v-if="cand.asset_id"
                                :to="`/assets/${cand.asset_id}`"
                                target="_blank"
                                class="text-[11px] font-bold text-cyan-400 hover:underline shrink-0"
                            >
                                Lihat Aset &rarr;
                            </router-link>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 6: TINDAKAN HASIL REKONSILIASI SESUAI STATUS -->
                <div class="p-4 rounded-2xl bg-navy-950 border border-cyan-500/30 space-y-4">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-cyan-400">
                        Tindakan Hasil Rekonsiliasi ({{ result.status }})
                    </h4>

                    <!-- 1. Tindakan untuk MATCH -->
                    <div v-if="result.status === 'MATCH'" class="space-y-3">
                        <p class="text-slate-300">
                            Serial Number dan Model perangkat telah sesuai antara data mitra KSO dan database aplikasi.
                        </p>
                        <div>
                            <label class="block text-slate-400 font-semibold mb-1">Catatan Verifikasi (Opsional)</label>
                            <input
                                v-model="actionNote"
                                type="text"
                                placeholder="Contoh: Sudah diverifikasi sesuai berita acara KSO..."
                                class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white"
                            />
                        </div>
                        <button
                            type="button"
                            @click="executeAction('verify_match')"
                            :disabled="submitting"
                            class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs shadow-lg"
                        >
                            {{ submitting ? 'Menyimpan...' : 'Tandai Sebagai Sudah Diverifikasi' }}
                        </button>
                    </div>

                    <!-- 2. Tindakan untuk MODEL MISMATCH -->
                    <div v-else-if="result.status === 'MODEL MISMATCH'" class="space-y-3">
                        <p class="text-slate-300">
                            Serial Number cocok (<strong class="font-mono text-cyan-300">{{ result.kso_sn }}</strong>), tetapi terdapat perbedaan Model:
                            Mitra KSO mencatat <strong class="text-purple-300">{{ result.kso_model || '-' }}</strong> sedangkan sistem mencatat
                            <strong class="text-amber-300">{{ result.system_model || '-' }}</strong>.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label
                                class="p-3 rounded-xl border cursor-pointer flex items-start gap-2.5"
                                :class="mismatchChoice === 'keep_system' ? 'bg-cyan-500/15 border-cyan-500/50 text-white' : 'bg-navy-900 border-slate-800 text-slate-400'"
                            >
                                <input type="radio" v-model="mismatchChoice" value="keep_system" class="mt-0.5" />
                                <div>
                                    <div class="font-bold text-slate-100">Pertahankan Data Sistem</div>
                                    <div class="text-[11px]">Tetap gunakan model "{{ result.system_model }}" pada database RSUD.</div>
                                </div>
                            </label>

                            <label
                                class="p-3 rounded-xl border cursor-pointer flex items-start gap-2.5"
                                :class="mismatchChoice === 'update_system' ? 'bg-amber-500/15 border-amber-500/50 text-white' : 'bg-navy-900 border-slate-800 text-slate-400'"
                            >
                                <input type="radio" v-model="mismatchChoice" value="update_system" class="mt-0.5" />
                                <div>
                                    <div class="font-bold text-slate-100">Perbarui Data Sistem Setelah Diverifikasi</div>
                                    <div class="text-[11px]">Perbarui model aset menjadi "{{ result.kso_model }}" dan catat audit log.</div>
                                </div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">
                                Alasan Koreksi / Keputusan Verifikasi <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="actionNote"
                                type="text"
                                required
                                placeholder="Wajib diisi: Jelaskan alasan mempertahankan atau memperbarui model perangkat..."
                                class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white"
                            />
                        </div>

                        <button
                            type="button"
                            @click="executeAction(mismatchChoice)"
                            :disabled="submitting || (mismatchChoice === 'update_system' && !actionNote.trim())"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-extrabold text-xs shadow-lg disabled:opacity-40"
                        >
                            {{ submitting ? 'Menyimpan...' : mismatchChoice === 'update_system' ? 'Simpan & Perbarui Data Sistem' : 'Simpan & Pertahankan Data Sistem' }}
                        </button>
                    </div>

                    <!-- 3. Tindakan untuk MISSING IN SYSTEM (Tambahkan ke Asset Directory dengan Konfirmasi) -->
                    <div v-else-if="result.status === 'MISSING IN SYSTEM'" class="space-y-3">
                        <p class="text-slate-300">
                            Perangkat ini tercatat pada daftar Mitra KSO tetapi belum ada di database aplikasi. Lengkapi data di bawah ini untuk menambahkannya ke <strong>Asset Directory</strong>:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Nama Aset <span class="text-rose-400">*</span></label>
                                <input v-model="newAssetForm.name" type="text" required class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Kategori <span class="text-rose-400">*</span></label>
                                <select v-model="newAssetForm.category" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Pilih Ruangan Penempatan <span class="text-rose-400">*</span></label>
                                <select v-model="newAssetForm.room_id" required class="w-full bg-navy-900 border border-emerald-500/40 rounded-xl px-3 py-2 text-white">
                                    <option value="" disabled>-- Pilih Ruangan --</option>
                                    <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }} ({{ r.building }})</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Serial Number (Dari KSO)</label>
                                <input v-model="newAssetForm.serial_number" type="text" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 font-mono text-cyan-300" />
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Model Perangkat</label>
                                <input v-model="newAssetForm.model" type="text" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Merk</label>
                                <input v-model="newAssetForm.brand" type="text" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Penanggung Jawab (PIC)</label>
                                <input v-model="newAssetForm.pic" type="text" placeholder="Nama PIC Ruangan" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Kondisi</label>
                                <select v-model="newAssetForm.condition" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                    <option value="Baik">Baik</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Rusak">Rusak</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate-400 font-semibold mb-1">Mitra KSO</label>
                                <input v-model="newAssetForm.partner_name" type="text" class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-purple-300 font-bold" />
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="executeAction('add_to_directory')"
                            :disabled="submitting || !newAssetForm.room_id || !newAssetForm.name"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 text-slate-950 font-extrabold text-xs shadow-lg disabled:opacity-40"
                        >
                            {{ submitting ? 'Menambahkan...' : '+ Konfirmasi & Tambahkan ke Asset Directory' }}
                        </button>
                    </div>

                    <!-- 4. Tindakan untuk MISSING IN KSO -->
                    <div v-else-if="result.status === 'MISSING IN KSO'" class="space-y-3">
                        <p class="text-slate-300">
                            Aset <strong class="font-mono text-cyan-300">{{ result.system_asset_code }}</strong> ({{ result.system_name }}) tercatat sebagai aset KSO di sistem RSUD, namun tidak ditemukan pada file mitra KSO. Sistem tidak menghapus atau mengubah kepemilikan aset secara otomatis.
                        </p>
                        <div>
                            <label class="block text-slate-400 font-semibold mb-1">Catatan Konfirmasi ke Mitra KSO</label>
                            <input
                                v-model="actionNote"
                                type="text"
                                placeholder="Contoh: Perlu konfirmasi ke Mitra Abadi apakah unit sudah ditarik atau belum masuk daftar..."
                                class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white"
                            />
                        </div>
                        <button
                            type="button"
                            @click="executeAction('flag_confirm_kso')"
                            :disabled="submitting"
                            class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs shadow-lg"
                        >
                            {{ submitting ? 'Menyimpan...' : 'Tandai Perlu Konfirmasi ke Mitra KSO' }}
                        </button>
                    </div>

                    <!-- 5. Tindakan untuk DUPLICATE SN / NEEDS MANUAL REVIEW / INVALID DATA -->
                    <div v-else class="space-y-3">
                        <p class="text-slate-300">
                            Data ini memerlukan pemeriksaan manual. Sistem tidak menggabungkan atau menghapus data secara otomatis.
                        </p>
                        <div>
                            <label class="block text-slate-400 font-semibold mb-1">Catatan Hasil Pemeriksaan Manual</label>
                            <input
                                v-model="actionNote"
                                type="text"
                                placeholder="Tuliskan hasil pengecekan fisik SN/Model di lapangan..."
                                class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-white"
                            />
                        </div>
                        <button
                            type="button"
                            @click="executeAction('manual_review')"
                            :disabled="submitting"
                            class="px-5 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-extrabold text-xs shadow-lg"
                        >
                            {{ submitting ? 'Menyimpan...' : 'Simpan Hasil Pemeriksaan Manual' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 sm:px-6 py-3.5 border-t border-slate-800 bg-navy-950/90 flex items-center justify-end">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:text-white text-xs font-semibold"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { GitCompare, X, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    result: { type: Object, default: null },
    rooms: { type: Array, default: () => [] },
    partnerName: { type: String, default: 'MITRA ABADI' },
});

const emit = defineEmits(['close', 'resolved']);
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

const submitting = ref(false);
const actionNote = ref('');
const mismatchChoice = ref('keep_system');

const newAssetForm = reactive({
    name: '',
    category: 'Printer',
    brand: '',
    model: '',
    serial_number: '',
    room_id: '',
    pic: '',
    condition: 'Baik',
    partner_name: '',
});

const comparisonRows = computed(() => {
    const r = props.result;
    if (!r) return [];
    const diffs = r.differences || {};

    return [
        {
            key: 'serial_number',
            label: 'Serial Number (SN)',
            ksoValue: r.kso_sn,
            sysValue: r.system_sn,
            state: diffs.serial_number || 'empty',
            mono: true,
        },
        {
            key: 'model',
            label: 'Model Perangkat',
            ksoValue: r.kso_model,
            sysValue: r.system_model,
            state: diffs.model || 'empty',
            mono: false,
        },
        {
            key: 'brand',
            label: 'Merk',
            ksoValue: r.kso_brand,
            sysValue: r.system_brand,
            state: diffs.brand || 'empty',
            mono: false,
        },
        {
            key: 'category',
            label: 'Kategori',
            ksoValue: r.kso_category,
            sysValue: r.system_category,
            state: diffs.category || 'empty',
            mono: false,
        },
        {
            key: 'location',
            label: 'Lokasi / Ruangan',
            ksoValue: r.kso_location,
            sysValue: r.system_location,
            state: diffs.location || 'empty',
            mono: false,
        },
        {
            key: 'notes',
            label: 'Keterangan',
            ksoValue: r.kso_notes,
            sysValue: r.system_notes,
            state: r.kso_notes && r.system_notes ? (r.kso_notes === r.system_notes ? 'match' : 'empty') : 'empty',
            mono: false,
        },
    ];
});

function rowContainerClass(state) {
    if (state === 'match') return 'bg-emerald-950/15 border-emerald-500/35';
    if (state === 'mismatch') return 'bg-rose-950/20 border-rose-500/40';
    return 'bg-amber-950/15 border-amber-500/35';
}

function rowBadgeClass(state) {
    if (state === 'match') return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
    if (state === 'mismatch') return 'bg-rose-500/20 text-rose-300 border-rose-500/40';
    return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
}

function rowStateLabel(state) {
    if (state === 'match') return 'Sesuai';
    if (state === 'mismatch') return 'Berbeda';
    return 'Perlu Cek';
}

function statusBadgeClass(st) {
    if (st === 'MATCH') return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40';
    if (st === 'MODEL MISMATCH') return 'bg-amber-500/20 text-amber-300 border-amber-500/40';
    if (st === 'MISSING IN SYSTEM') return 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40';
    if (st === 'MISSING IN KSO') return 'bg-purple-500/20 text-purple-300 border-purple-500/40';
    if (st === 'DUPLICATE SN') return 'bg-rose-500/20 text-rose-300 border-rose-500/40';
    return 'bg-slate-700 text-slate-300 border-slate-600';
}

watch(
    () => props.result,
    (r) => {
        if (!r) return;
        actionNote.value = r.action_note || '';
        mismatchChoice.value = 'keep_system';

        newAssetForm.name = r.kso_model || 'Perangkat KSO Baru';
        newAssetForm.category = categories.includes(r.kso_category) ? r.kso_category : 'Printer';
        newAssetForm.brand = r.kso_brand || 'Epson';
        newAssetForm.model = r.kso_model || '';
        newAssetForm.serial_number = r.kso_sn || '';
        newAssetForm.partner_name =
            props.partnerName && props.partnerName !== 'Semua Mitra KSO' ? props.partnerName : 'MITRA ABADI';

        // Try matching room name from KSO location
        if (r.kso_location && props.rooms.length) {
            const locLower = r.kso_location.toLowerCase();
            const matchedRoom = props.rooms.find((rm) => rm.name.toLowerCase().includes(locLower) || locLower.includes(rm.name.toLowerCase()));
            newAssetForm.room_id = matchedRoom?.id || props.rooms[0]?.id || '';
        } else {
            newAssetForm.room_id = props.rooms[0]?.id || '';
        }
    },
    { immediate: true }
);

async function executeAction(actionType) {
    if (!props.result) return;
    submitting.value = true;
    try {
        const payload = {
            action: actionType,
            action_note: actionNote.value,
        };

        if (actionType === 'add_to_directory') {
            Object.assign(payload, newAssetForm);
        }

        const { data } = await api.post(`/kso-reconciliation/results/${props.result.id}/resolve`, payload);
        auth.notify(data.message, 'success');
        emit('resolved', data.result);
        emit('close');
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyimpan tindakan rekonsiliasi.', 'error');
    } finally {
        submitting.value = false;
    }
}
</script>

