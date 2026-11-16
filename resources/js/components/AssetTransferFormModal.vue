<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/75 backdrop-blur-sm no-print">
        <div class="bg-navy-900 border-t sm:border border-slate-700/80 rounded-t-2xl sm:rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[94dvh] sm:max-h-[92vh]">
            <!-- Modal Header -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-800 flex items-center justify-between bg-navy-950/70">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                        <ArrowLeftRight class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            Form Pemindahan Aset IT (Asset Transfer)
                        </h3>
                        <p class="text-xs text-slate-400">
                            Mutasi lokasi perangkat antar-ruangan / instalasi dengan transaksi database &amp; riwayat permanen
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitTransfer" class="p-6 overflow-y-auto flex-1 space-y-6">
                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2.5">
                    <AlertCircle class="w-4 h-4 text-rose-400 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- BAGIAN A: INFORMASI ASET -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-cyan-400 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        A. Informasi Perangkat Aset yang Dipindahkan
                    </h4>

                    <!-- Searchable Asset Picker -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
                        <div class="sm:col-span-5">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Cari Kode / Nama Perangkat
                            </label>
                            <input
                                v-model="assetSearch"
                                type="text"
                                :disabled="!!preselectedAsset"
                                placeholder="Ketik kode AST-XXXX atau nama perangkat..."
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 disabled:opacity-60"
                            />
                        </div>
                        <div class="sm:col-span-7">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Pilih Aset Aktif ({{ filteredAssets.length }} Tersedia) <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.asset_id"
                                required
                                :disabled="!!preselectedAsset"
                                @change="onAssetSelected"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500 disabled:opacity-70"
                            >
                                <option value="" disabled>-- Pilih Perangkat Aset IT --</option>
                                <option v-for="item in filteredAssets" :key="item.id" :value="item.id">
                                    {{ item.asset_code }} &mdash; {{ item.name }} [Lokasi: {{ item.room?.name || '-' }}]
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Auto-Populated Asset Info Card -->
                    <div
                        v-if="selectedAsset"
                        class="p-4 rounded-xl bg-navy-950/90 border border-cyan-500/30 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs"
                    >
                        <div>
                            <span class="text-slate-400 block text-[11px]">Kode &amp; Nama</span>
                            <span class="font-mono font-bold text-cyan-300">{{ selectedAsset.asset_code }}</span>
                            <span class="font-bold text-white block truncate">{{ selectedAsset.name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Kategori Perangkat</span>
                            <span class="font-semibold text-slate-200">{{ selectedAsset.category }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Merk &amp; Model</span>
                            <span class="font-semibold text-slate-200 block truncate">
                                {{ selectedAsset.brand || '-' }} {{ selectedAsset.model || '' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Serial Number</span>
                            <span class="font-mono text-slate-200">{{ selectedAsset.serial_number || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Lokasi Saat Ini</span>
                            <span class="font-bold text-emerald-400">{{ selectedAsset.room?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">PIC &amp; Kondisi Saat Ini</span>
                            <span class="font-semibold text-white block truncate">{{ selectedAsset.pic || '-' }}</span>
                            <span
                                class="inline-block mt-0.5 px-2 py-0.2 rounded text-[10px] font-bold"
                                :class="selectedAsset.condition === 'Baik' ? 'text-emerald-300 bg-emerald-500/15' : 'text-amber-300 bg-amber-500/15'"
                            >
                                {{ selectedAsset.condition }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN B: INFORMASI PEMINDAHAN -->
                <div class="pt-3 border-t border-slate-800/80 space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        B. Informasi Pemindahan Lokasi &amp; Penanggung Jawab
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Lokasi Asal (Otomatis) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                                Lokasi Asal (Otomatis dari Aset)
                            </label>
                            <input
                                :value="selectedAsset ? `${selectedAsset.room?.name || '-'} (${selectedAsset.room?.building || ''})` : 'Pilih aset terlebih dahulu'"
                                type="text"
                                disabled
                                class="w-full bg-navy-950/60 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-300 cursor-not-allowed"
                            />
                        </div>

                        <!-- Lokasi Tujuan -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Lokasi Tujuan (Pilih dari Master Ruangan) <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.to_room_id"
                                required
                                class="w-full bg-navy-950 border border-emerald-500/40 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-400"
                            >
                                <option value="" disabled>-- Pilih Ruangan Tujuan --</option>
                                <option
                                    v-for="room in rooms"
                                    :key="room.id"
                                    :value="room.id"
                                    :disabled="selectedAsset && Number(room.id) === Number(selectedAsset.room_id)"
                                >
                                    {{ room.name }} — {{ room.building }}
                                    {{ selectedAsset && Number(room.id) === Number(selectedAsset.room_id) ? ' (Lokasi Saat Ini)' : '' }}
                                </option>
                            </select>
                        </div>

                        <!-- PIC Sebelumnya (Otomatis) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                                PIC Sebelumnya (Otomatis)
                            </label>
                            <input
                                :value="selectedAsset?.pic || '-'"
                                type="text"
                                disabled
                                class="w-full bg-navy-950/60 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-slate-300 cursor-not-allowed"
                            />
                        </div>

                        <!-- PIC Baru (Opsional) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                PIC Baru di Lokasi Tujuan (Opsional)
                            </label>
                            <input
                                v-model="form.new_pic"
                                type="text"
                                placeholder="Nama penanggung jawab baru"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <!-- Petugas Pencatat (Otomatis) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                                Petugas Pencatat (Login Aktif)
                            </label>
                            <input
                                :value="auth.user?.name || 'Petugas IT'"
                                type="text"
                                disabled
                                class="w-full bg-navy-950/60 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-cyan-300 font-semibold cursor-not-allowed"
                            />
                        </div>

                        <!-- Tanggal Pemindahan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Tanggal Pemindahan <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.transfer_date"
                                type="date"
                                required
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <!-- Waktu Pemindahan (Opsional) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Waktu Pemindahan (Opsional)
                            </label>
                            <input
                                v-model="form.transfer_time"
                                type="time"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <!-- Status Pemindahan -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Status Eksekusi Pemindahan
                            </label>
                            <select
                                v-model="form.status"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option value="Selesai">Selesai (Perbarui Lokasi Sekarang)</option>
                                <option value="Menunggu">Menunggu (Jadwalkan Pemindahan)</option>
                            </select>
                        </div>

                        <!-- Alasan Pemindahan -->
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Alasan Pemindahan <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.transfer_reason"
                                required
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option v-for="r in reasons" :key="r" :value="r">{{ r }}</option>
                            </select>
                        </div>

                        <!-- Keterangan Tambahan -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Keterangan Tambahan
                            </label>
                            <input
                                v-model="form.description"
                                type="text"
                                placeholder="Catatan serah terima perangkat, kelengkapan kabel/adaptor, nomor surat mutasi..."
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- BAGIAN C: DOKUMENTASI PEMINDAHAN (Section 6) -->
                <div class="pt-3 border-t border-slate-800/80 space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-purple-400 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                        C. Upload Dokumentasi Pemindahan (Opsional)
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-navy-950 border border-slate-800">
                            <label class="block font-semibold text-slate-300 mb-1.5">1. Foto Sebelum Dipindahkan</label>
                            <input
                                ref="beforeInput"
                                type="file"
                                accept="image/*"
                                @change="(e) => (files.photo_before = e.target.files?.[0] || null)"
                                class="block w-full text-[11px] text-slate-400 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-cyan-500/15 file:text-cyan-300"
                            />
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950 border border-slate-800">
                            <label class="block font-semibold text-slate-300 mb-1.5">2. Foto Setelah Dipindahkan</label>
                            <input
                                ref="afterInput"
                                type="file"
                                accept="image/*"
                                @change="(e) => (files.photo_after = e.target.files?.[0] || null)"
                                class="block w-full text-[11px] text-slate-400 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-emerald-500/15 file:text-emerald-300"
                            />
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950 border border-slate-800">
                            <label class="block font-semibold text-slate-300 mb-1.5">3. Dokumen Pendukung (BA/PDF)</label>
                            <input
                                ref="docInput"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                                @change="(e) => (files.supporting_doc = e.target.files?.[0] || null)"
                                class="block w-full text-[11px] text-slate-400 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-purple-500/15 file:text-purple-300"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer Buttons (Simpan Pemindahan, Batal, Reset Form) -->
                <div class="pt-4 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="resetForm"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-navy-950 hover:bg-slate-800 border border-slate-700 text-xs font-semibold text-slate-300 transition"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        <span>Reset Form</span>
                    </button>

                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            @click="$emit('close')"
                            class="px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 text-xs font-semibold transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="submitting || !form.asset_id || !form.to_room_id"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 disabled:opacity-40 text-slate-950 font-extrabold text-xs shadow-lg shadow-cyan-500/20 transition"
                        >
                            <Save class="w-4 h-4" />
                            <span>{{ submitting ? 'Memproses Transaksi...' : 'Simpan Pemindahan' }}</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { ArrowLeftRight, X, AlertCircle, RotateCcw, Save } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    assets: { type: Array, default: () => [] },
    rooms: { type: Array, default: () => [] },
    preselectedAsset: { type: Object, default: null },
});

const emit = defineEmits(['close', 'saved']);
const auth = useAuthStore();

const reasons = [
    'Mutasi ruangan',
    'Kebutuhan operasional',
    'Pergantian pengguna',
    'Renovasi ruangan',
    'Penggantian perangkat',
    'Penataan ulang perangkat',
    'Lainnya',
];

const assetSearch = ref('');
const submitting = ref(false);
const errorMessage = ref('');

const beforeInput = ref(null);
const afterInput = ref(null);
const docInput = ref(null);

const form = reactive({
    asset_id: '',
    to_room_id: '',
    new_pic: '',
    transfer_date: new Date().toISOString().slice(0, 10),
    transfer_time: new Date().toTimeString().slice(0, 5),
    transfer_reason: 'Kebutuhan operasional',
    description: '',
    status: 'Selesai',
});

const files = reactive({
    photo_before: null,
    photo_after: null,
    supporting_doc: null,
});

const filteredAssets = computed(() => {
    if (props.preselectedAsset) {
        return [props.preselectedAsset];
    }
    const q = assetSearch.value.trim().toLowerCase();
    if (!q) return props.assets;
    return props.assets.filter(
        (a) =>
            a.asset_code?.toLowerCase().includes(q) ||
            a.name?.toLowerCase().includes(q) ||
            a.room?.name?.toLowerCase().includes(q) ||
            a.serial_number?.toLowerCase().includes(q)
    );
});

const selectedAsset = computed(() => {
    if (props.preselectedAsset && Number(form.asset_id) === Number(props.preselectedAsset.id)) {
        return props.preselectedAsset;
    }
    return props.assets.find((a) => Number(a.id) === Number(form.asset_id)) || null;
});

function onAssetSelected() {
    if (selectedAsset.value && Number(form.to_room_id) === Number(selectedAsset.value.room_id)) {
        form.to_room_id = '';
    }
}

function resetForm() {
    errorMessage.value = '';
    assetSearch.value = '';
    form.asset_id = props.preselectedAsset?.id || '';
    form.to_room_id = '';
    form.new_pic = '';
    form.transfer_date = new Date().toISOString().slice(0, 10);
    form.transfer_time = new Date().toTimeString().slice(0, 5);
    form.transfer_reason = 'Kebutuhan operasional';
    form.description = '';
    form.status = 'Selesai';
    files.photo_before = null;
    files.photo_after = null;
    files.supporting_doc = null;
    if (beforeInput.value) beforeInput.value.value = '';
    if (afterInput.value) afterInput.value.value = '';
    if (docInput.value) docInput.value.value = '';
}

watch(
    () => [props.show, props.preselectedAsset],
    ([isOpen]) => {
        if (isOpen) {
            resetForm();
        }
    },
    { immediate: true }
);

async function submitTransfer() {
    errorMessage.value = '';

    if (selectedAsset.value && Number(form.to_room_id) === Number(selectedAsset.value.room_id)) {
        errorMessage.value = 'Lokasi tujuan tidak boleh sama dengan lokasi ruangan aset saat ini.';
        return;
    }

    submitting.value = true;
    try {
        const fd = new FormData();
        fd.append('asset_id', form.asset_id);
        fd.append('to_room_id', form.to_room_id);
        if (form.new_pic) fd.append('new_pic', form.new_pic);
        fd.append('transfer_date', form.transfer_date);
        if (form.transfer_time) fd.append('transfer_time', form.transfer_time);
        fd.append('transfer_reason', form.transfer_reason);
        if (form.description) fd.append('description', form.description);
        fd.append('status', form.status);

        if (files.photo_before) fd.append('photo_before', files.photo_before);
        if (files.photo_after) fd.append('photo_after', files.photo_after);
        if (files.supporting_doc) fd.append('supporting_doc', files.supporting_doc);

        const { data } = await api.post('/asset-transfers', fd, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        auth.notify(data.message, 'success');
        emit('saved', data.transfer);
        emit('close');
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Gagal menyimpan transaksi pemindahan aset.';
    } finally {
        submitting.value = false;
    }
}
</script>

