<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/75 backdrop-blur-sm">
        <div class="bg-navy-900 border-t sm:border border-slate-700/80 rounded-t-2xl sm:rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[94dvh] sm:max-h-[92vh]">
            <!-- Modal Header -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-800 flex items-center justify-between bg-navy-950/70">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                        <Monitor class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">
                            {{ isEdit ? `Edit Data Aset (${form.asset_code})` : 'Tambah Perangkat Aset IT Baru' }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            Seluruh data tersimpan secara permanen di database MySQL rumah sakit
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submitForm" class="p-6 overflow-y-auto flex-1 space-y-6">
                <!-- Error Alert -->
                <div v-if="errorMessage" class="p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2.5">
                    <AlertCircle class="w-4 h-4 text-rose-400 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- Section 1: Identitas Utama -->
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-cyan-400 mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-cyan-400"></span>
                        1. Identitas & Klasifikasi Perangkat
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Kode Aset (Otomatis)
                            </label>
                            <input
                                v-model="form.asset_code"
                                type="text"
                                placeholder="Otomatis: AST-0001"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm font-mono font-bold text-cyan-300 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Nama Aset / Perangkat <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="Contoh: PC All-in-One Loket Pendaftaran #1"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Kategori Perangkat <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.category"
                                required
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Merk</label>
                            <input
                                v-model="form.brand"
                                type="text"
                                placeholder="Contoh: Dell, HP, Lenovo, Cisco"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Model / Tipe</label>
                            <input
                                v-model="form.model"
                                type="text"
                                placeholder="Contoh: OptiPlex 7010 / Catalyst 9300"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Serial Number (SN)</label>
                            <input
                                v-model="form.serial_number"
                                type="text"
                                placeholder="Nomor seri pabrikan"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm font-mono text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Tahun Perolehan</label>
                            <input
                                v-model.number="form.acquisition_year"
                                type="number"
                                min="2000"
                                max="2099"
                                placeholder="2025"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Kondisi Saat Ini <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.condition"
                                required
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option value="Baik">Baik (Operasional Normal)</option>
                                <option value="Maintenance">Maintenance (Perbaikan / Upgrade)</option>
                                <option value="Rusak">Rusak (Tidak Berfungsi)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Lokasi, PIC & Kepemilikan -->
                <div class="pt-2 border-t border-slate-800/80">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-400 mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        2. Penempatan Ruangan, PIC & Status Kepemilikan
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Ruangan / Instalasi <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.room_id"
                                required
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option value="" disabled>Pilih Ruangan...</option>
                                <option v-for="room in rooms" :key="room.id" :value="room.id">
                                    {{ room.name }} ({{ room.building }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Penanggung Jawab (PIC)</label>
                            <input
                                v-model="form.pic"
                                type="text"
                                placeholder="Nama pengguna / Kepala Ruangan"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Kepemilikan Aset <span class="text-rose-400">*</span>
                            </label>
                            <select
                                v-model="form.ownership"
                                required
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option value="Milik RSUD">Milik RSUD</option>
                                <option value="KSO">KSO (Kerja Sama Operasional)</option>
                                <option value="Milik Pihak Ketiga">Milik Pihak Ketiga</option>
                            </select>
                        </div>

                        <!-- Conditional Fields for KSO / Pihak Ketiga (Section 9) -->
                        <template v-if="form.ownership !== 'Milik RSUD'">
                            <div>
                                <label class="block text-xs font-semibold text-purple-300 mb-1.5">
                                    Nama Mitra KSO / Pihak Ketiga <span class="text-rose-400">*</span>
                                </label>
                                <input
                                    v-model="form.partner_name"
                                    type="text"
                                    :required="form.ownership !== 'Milik RSUD'"
                                    placeholder="Contoh: PT Medika Digital Nusantara"
                                    class="w-full bg-purple-950/30 border border-purple-500/40 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-purple-400"
                                />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-purple-300 mb-1.5">
                                    Keterangan Kontrak / Kerja Sama Mitra
                                </label>
                                <input
                                    v-model="form.ownership_notes"
                                    type="text"
                                    placeholder="Nomor kontrak KSO / masa berlaku / ketentuan pemeliharaan mitra"
                                    class="w-full bg-purple-950/30 border border-purple-500/40 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-purple-400"
                                />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Section 3: Konfigurasi Jaringan (IP Network Map) -->
                <div class="pt-2 border-t border-slate-800/80">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-purple-400 mb-3 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-purple-400"></span>
                        3. Parameter Jaringan & Spesifikasi Teknis
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">IP Address</label>
                            <input
                                v-model="form.ip_address"
                                type="text"
                                placeholder="Contoh: 10.10.20.11"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm font-mono text-cyan-300 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">MAC Address</label>
                            <input
                                v-model="form.mac_address"
                                type="text"
                                placeholder="Contoh: 3C:D9:2B:10:20:11"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Segmen VLAN / Subnet</label>
                            <input
                                v-model="form.vlan"
                                type="text"
                                placeholder="Contoh: VLAN 20 - Pelayanan"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status Monitoring IP</label>
                            <select
                                v-model="form.network_status"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            >
                                <option value="online">Online (Terhubung)</option>
                                <option value="offline">Offline (Terputus)</option>
                                <option value="unknown">Non-IP / Standalone</option>
                            </select>
                        </div>

                        <div class="sm:col-span-4">
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                Spesifikasi Perangkat (Processor, RAM, Storage, OS, Port, dll.)
                            </label>
                            <textarea
                                v-model="form.specifications"
                                rows="2"
                                placeholder="Contoh: Intel Core i5-13500T, RAM 16GB DDR4, SSD 512GB NVMe, Layar 23.8 FHD, Windows 11 Pro"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-cyan-500"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Upload Foto & Catatan -->
                <div class="pt-2 border-t border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Foto Fisik Perangkat</label>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-xl bg-navy-950 border border-slate-700 flex items-center justify-center overflow-hidden shrink-0">
                                <img v-if="photoPreview" :src="photoPreview" alt="Preview" class="w-full h-full object-cover" />
                                <Camera v-else class="w-6 h-6 text-slate-600" />
                            </div>
                            <div class="flex-1">
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept="image/*"
                                    @change="onFileChange"
                                    class="block w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-cyan-500/15 file:text-cyan-300 hover:file:bg-cyan-500/25 cursor-pointer"
                                />
                                <p class="text-[11px] text-slate-500 mt-1">Format JPG, PNG, WEBP (Maks. 5MB).</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Keterangan / Catatan Tambahan</label>
                        <textarea
                            v-model="form.notes"
                            rows="3"
                            placeholder="Catatan penggunaan, kelengkapan unit, atau tindak lanjut yang diperlukan..."
                            class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3.5 py-2 text-sm text-white focus:outline-none focus:border-cyan-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="pt-4 border-t border-slate-800 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 text-xs font-semibold transition"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 disabled:opacity-50 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ submitting ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan Aset Baru' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { Monitor, X, Camera, Save, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    asset: { type: Object, default: null },
    rooms: { type: Array, default: () => [] },
    nextCode: { type: String, default: 'AST-0001' },
});

const emit = defineEmits(['close', 'saved']);
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

const isEdit = computed(() => !!props.asset?.id);
const submitting = ref(false);
const errorMessage = ref('');
const photoFile = ref(null);
const photoPreview = ref(null);

const form = reactive({
    asset_code: '',
    name: '',
    category: 'Komputer / PC',
    brand: '',
    model: '',
    serial_number: '',
    ip_address: '',
    mac_address: '',
    vlan: '',
    network_status: 'online',
    specifications: '',
    room_id: '',
    pic: '',
    ownership: 'Milik RSUD',
    partner_name: '',
    ownership_notes: '',
    condition: 'Baik',
    acquisition_year: new Date().getFullYear(),
    notes: '',
});

watch(
    () => [props.show, props.asset],
    ([isOpen, item]) => {
        if (!isOpen) return;
        errorMessage.value = '';
        photoFile.value = null;

        if (item && item.id) {
            form.asset_code = item.asset_code || '';
            form.name = item.name || '';
            form.category = item.category || 'Komputer / PC';
            form.brand = item.brand || '';
            form.model = item.model || '';
            form.serial_number = item.serial_number || '';
            form.ip_address = item.ip_address || '';
            form.mac_address = item.mac_address || '';
            form.vlan = item.vlan || '';
            form.network_status = item.network_status || 'online';
            form.specifications = item.specifications || '';
            form.room_id = item.room_id || (props.rooms[0]?.id ?? '');
            form.pic = item.pic || '';
            form.ownership = item.ownership || 'Milik RSUD';
            form.partner_name = item.partner_name || '';
            form.ownership_notes = item.ownership_notes || '';
            form.condition = item.condition || 'Baik';
            form.acquisition_year = item.acquisition_year || new Date().getFullYear();
            form.notes = item.notes || '';
            photoPreview.value = item.photo_url || null;
        } else {
            form.asset_code = props.nextCode || '';
            form.name = '';
            form.category = 'Komputer / PC';
            form.brand = '';
            form.model = '';
            form.serial_number = '';
            form.ip_address = '';
            form.mac_address = '';
            form.vlan = '';
            form.network_status = 'online';
            form.specifications = '';
            form.room_id = props.rooms[0]?.id ?? '';
            form.pic = '';
            form.ownership = 'Milik RSUD';
            form.partner_name = '';
            form.ownership_notes = '';
            form.condition = 'Baik';
            form.acquisition_year = new Date().getFullYear();
            form.notes = '';
            photoPreview.value = null;
        }
    },
    { immediate: true }
);

function onFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    photoFile.value = file;
    photoPreview.value = URL.createObjectURL(file);
}

async function submitForm() {
    submitting.value = true;
    errorMessage.value = '';

    try {
        const fd = new FormData();
        Object.entries(form).forEach(([key, val]) => {
            if (val !== null && val !== undefined && val !== '') {
                fd.append(key, val);
            }
        });

        if (photoFile.value) {
            fd.append('photo', photoFile.value);
        }

        let response;
        if (isEdit.value) {
            response = await api.post(`/assets/${props.asset.id}`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        } else {
            response = await api.post('/assets', fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
        }

        auth.notify(response.data.message || 'Data aset berhasil disimpan.', 'success');
        emit('saved', response.data.asset);
        emit('close');
    } catch (err) {
        errorMessage.value =
            err.response?.data?.message || 'Gagal menyimpan data aset. Periksa kembali isian form.';
    } finally {
        submitting.value = false;
    }
}
</script>
