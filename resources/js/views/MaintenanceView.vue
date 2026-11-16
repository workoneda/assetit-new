<template>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-navy-900/90 border border-cyan-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Total Tiket Pemeliharaan</div>
                <div class="text-2xl font-mono font-extrabold text-white mt-1">{{ summary.total }}</div>
                <div class="text-[11px] text-cyan-400 mt-1">Riwayat Servis &amp; Upgrade</div>
            </div>
            <div class="bg-navy-900/90 border border-rose-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Status: Menunggu</div>
                <div class="text-2xl font-mono font-extrabold text-rose-400 mt-1">{{ summary.menunggu }}</div>
                <div class="text-[11px] text-rose-300/80 mt-1">Menunggu Tindakan / Part</div>
            </div>
            <div class="bg-navy-900/90 border border-amber-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Status: Sedang Dikerjakan</div>
                <div class="text-2xl font-mono font-extrabold text-amber-400 mt-1">{{ summary.sedang_dikerjakan }}</div>
                <div class="text-[11px] text-amber-300/80 mt-1">Dalam Penanganan Teknisi</div>
            </div>
            <div class="bg-navy-900/90 border border-emerald-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Status: Selesai</div>
                <div class="text-2xl font-mono font-extrabold text-emerald-400 mt-1">{{ summary.selesai }}</div>
                <div class="text-[11px] text-emerald-300/80 mt-1">Perangkat Kembali Normal</div>
            </div>
        </div>

        <!-- Toolbar & Filter -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2.5 flex-1">
                <div class="relative flex-1 min-w-[220px] max-w-md">
                    <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="filters.search"
                        @input="fetchMaintenances"
                        type="text"
                        placeholder="Cari nomor tiket, nama aset, keluhan, teknisi..."
                        class="w-full bg-navy-950 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                    />
                </div>

                <select
                    v-model="filters.status"
                    @change="fetchMaintenances"
                    class="bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-slate-200"
                >
                    <option value="">Semua Status Pekerjaan</option>
                    <option value="Menunggu">Menunggu</option>
                    <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                    <option value="Selesai">Selesai</option>
                </select>
            </div>

            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-bold text-xs shadow-lg shadow-amber-500/20 transition"
            >
                <Plus class="w-4 h-4 stroke-[2.5]" />
                <span>Catat Pemeliharaan Baru</span>
            </button>
        </div>

        <!-- Mobile Card List & Desktop Table of Maintenance Logs -->
        <div class="space-y-3">
            <!-- Mobile Cards (< 768px) -->
            <div class="md:hidden space-y-3">
                <div v-if="!maintenances.length" class="bg-navy-900 border border-slate-800 rounded-2xl p-8 text-center text-xs text-slate-400">
                    Belum ada catatan pemeliharaan perangkat.
                </div>
                <div
                    v-for="item in maintenances"
                    :key="`mob-mnt-${item.id}`"
                    class="bg-navy-900/95 border border-slate-800/90 rounded-2xl p-4 space-y-3 shadow-lg text-xs"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div>
                            <span class="font-mono font-bold text-amber-300">{{ item.ticket_number }}</span>
                            <span class="text-[11px] text-slate-400 ml-2">{{ item.maintenance_date }}</span>
                        </div>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                            :class="{
                                'bg-rose-500/15 text-rose-300 border-rose-500/30': item.status === 'Menunggu',
                                'bg-amber-500/15 text-amber-300 border-amber-500/30': item.status === 'Sedang Dikerjakan',
                                'bg-emerald-500/15 text-emerald-300 border-emerald-500/30': item.status === 'Selesai',
                            }"
                        >
                            {{ item.status }}
                        </span>
                    </div>

                    <div>
                        <router-link
                            v-if="item.asset"
                            :to="`/assets/${item.asset.id}`"
                            class="font-extrabold text-sm text-white hover:text-cyan-300 block"
                        >
                            {{ item.asset.name }}
                        </router-link>
                        <div class="text-[11px] font-mono text-cyan-400 mt-0.5">
                            {{ item.asset?.asset_code }} &bull; {{ item.asset?.room?.name || '-' }} &bull; {{ item.job_type }}
                        </div>
                    </div>

                    <div class="p-2.5 rounded-xl bg-navy-950 border border-slate-800/80 space-y-1">
                        <div class="text-slate-200"><strong class="text-slate-400">Keluhan:</strong> {{ item.complaint }}</div>
                        <div v-if="item.action_taken" class="text-emerald-300/90">
                            <strong class="text-slate-400">Tindakan:</strong> {{ item.action_taken }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <div class="text-[11px] text-slate-400">
                            Teknisi: <strong class="text-white">{{ item.technician }}</strong>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <button
                                @click="openEditModal(item)"
                                class="px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 font-bold text-xs"
                            >
                                Edit / Status
                            </button>
                            <button
                                @click="deleteMaintenance(item)"
                                class="p-1.5 rounded-xl bg-slate-800 text-rose-300 border border-slate-700"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Table (>= 768px) -->
            <div class="hidden md:block bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-navy-950/90 border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-3.5 px-4">Tiket &amp; Tanggal</th>
                            <th class="py-3.5 px-4">Nama Aset</th>
                            <th class="py-3.5 px-4">Jenis Pekerjaan</th>
                            <th class="py-3.5 px-4">Keluhan &amp; Tindakan</th>
                            <th class="py-3.5 px-4">Teknisi &amp; Biaya</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70 text-xs">
                        <tr v-if="!maintenances.length">
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                Belum ada catatan pemeliharaan perangkat.
                            </td>
                        </tr>
                        <tr
                            v-for="item in maintenances"
                            :key="item.id"
                            class="hover:bg-slate-800/35 transition"
                        >
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-mono font-bold text-amber-300">{{ item.ticket_number }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">{{ item.maintenance_date }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <router-link
                                    v-if="item.asset"
                                    :to="`/assets/${item.asset.id}`"
                                    class="font-bold text-white hover:text-cyan-300 block"
                                >
                                    {{ item.asset.name }}
                                </router-link>
                                <div class="text-[11px] font-mono text-cyan-400">
                                    {{ item.asset?.asset_code }} &bull; {{ item.asset?.room?.name || '-' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-slate-200 font-medium text-[11px]">
                                    {{ item.job_type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 max-w-md">
                                <div class="text-slate-200"><strong class="text-slate-400">Keluhan:</strong> {{ item.complaint }}</div>
                                <div v-if="item.action_taken" class="text-emerald-300/90 mt-1">
                                    <strong class="text-slate-400">Tindakan:</strong> {{ item.action_taken }}
                                </div>
                                <div v-if="item.notes" class="text-[11px] text-slate-400 mt-0.5 italic">
                                    Catatan: {{ item.notes }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-semibold text-white">{{ item.technician }}</div>
                                <div class="text-[11px] font-mono text-cyan-300 mt-0.5">
                                    {{ item.cost > 0 ? `Rp ${Number(item.cost).toLocaleString('id-ID')}` : 'Internal (Rp 0)' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-1 rounded-full text-[10px] font-bold border"
                                    :class="{
                                        'bg-rose-500/15 text-rose-300 border-rose-500/30': item.status === 'Menunggu',
                                        'bg-amber-500/15 text-amber-300 border-amber-500/30': item.status === 'Sedang Dikerjakan',
                                        'bg-emerald-500/15 text-emerald-300 border-emerald-500/30': item.status === 'Selesai',
                                    }"
                                >
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        @click="openEditModal(item)"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300"
                                        title="Edit / Update Status"
                                    >
                                        <Edit3 class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="deleteMaintenance(item)"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300"
                                        title="Hapus"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Modal Form Pemeliharaan -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-slate-700 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 max-h-[92vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">
                        {{ editingId ? 'Update Data Pemeliharaan' : 'Catat Pemeliharaan & Perbaikan Aset' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Pilih Perangkat Aset IT <span class="text-rose-400">*</span></label>
                        <select v-model="form.asset_id" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white">
                            <option value="" disabled>Pilih Aset...</option>
                            <option v-for="a in allAssets" :key="a.id" :value="a.id">
                                {{ a.asset_code }} — {{ a.name }} ({{ a.room?.name || '-' }})
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Tanggal Pemeliharaan <span class="text-rose-400">*</span></label>
                            <input v-model="form.maintenance_date" type="date" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Jenis Pekerjaan <span class="text-rose-400">*</span></label>
                            <select v-model="form.job_type" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                <option value="Perbaikan Hardware">Perbaikan Hardware</option>
                                <option value="Upgrade Komponen">Upgrade Komponen</option>
                                <option value="Preventive Maintenance">Preventive Maintenance</option>
                                <option value="Penggantian Sparepart">Penggantian Sparepart</option>
                                <option value="Instalasi OS / Software">Instalasi OS / Software</option>
                                <option value="Perbaikan Jaringan">Perbaikan Jaringan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Keluhan atau Kerusakan <span class="text-rose-400">*</span></label>
                        <textarea v-model="form.complaint" rows="2" required placeholder="Rincian keluhan pengguna atau temuan kerusakan..." class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Tindakan yang Dilakukan</label>
                        <textarea v-model="form.action_taken" rows="2" placeholder="Tindakan teknis yang dilakukan..." class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Teknisi atau Petugas <span class="text-rose-400">*</span></label>
                            <input v-model="form.technician" type="text" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Biaya (Rp - Opsional)</label>
                            <input v-model.number="form.cost" type="number" min="0" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Status Pekerjaan <span class="text-rose-400">*</span></label>
                            <select v-model="form.status" @change="onStatusChange" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                <option value="Menunggu">Menunggu</option>
                                <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Update Kondisi Aset</label>
                            <select v-model="form.condition_after" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                <option value="Baik">Baik</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Rusak">Rusak</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Catatan Tambahan</label>
                        <input v-model="form.notes" type="text" placeholder="Catatan opsional..." class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold">
                            {{ editingId ? 'Simpan Perubahan' : 'Simpan Riwayat Pemeliharaan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { Search, Plus, Edit3, Trash2, X } from 'lucide-vue-next';

const auth = useAuthStore();

const maintenances = ref([]);
const allAssets = ref([]);
const summary = ref({
    total: 0,
    menunggu: 0,
    sedang_dikerjakan: 0,
    selesai: 0,
    total_cost: 0,
});

const filters = reactive({
    search: '',
    status: '',
});

const showModal = ref(false);
const editingId = ref(null);
const form = reactive({
    asset_id: '',
    maintenance_date: new Date().toISOString().slice(0, 10),
    job_type: 'Perbaikan Hardware',
    complaint: '',
    action_taken: '',
    technician: auth.user?.name || 'Petugas IT',
    cost: 0,
    status: 'Sedang Dikerjakan',
    condition_after: 'Maintenance',
    notes: '',
});

function onStatusChange() {
    if (form.status === 'Selesai') {
        form.condition_after = 'Baik';
    } else if (form.status === 'Sedang Dikerjakan') {
        form.condition_after = 'Maintenance';
    }
}

async function fetchMaintenances() {
    const { data } = await api.get('/maintenances', { params: filters });
    maintenances.value = data.data || [];
    summary.value = data.summary || summary.value;
}

async function fetchAssetList() {
    const { data } = await api.get('/assets', { params: { all: 1 } });
    allAssets.value = data.data || [];
}

function openCreateModal() {
    editingId.value = null;
    form.asset_id = allAssets.value[0]?.id || '';
    form.maintenance_date = new Date().toISOString().slice(0, 10);
    form.job_type = 'Perbaikan Hardware';
    form.complaint = '';
    form.action_taken = '';
    form.technician = auth.user?.name || 'Petugas IT';
    form.cost = 0;
    form.status = 'Sedang Dikerjakan';
    form.condition_after = 'Maintenance';
    form.notes = '';
    showModal.value = true;
}

function openEditModal(item) {
    editingId.value = item.id;
    form.asset_id = item.asset_id;
    form.maintenance_date = item.maintenance_date;
    form.job_type = item.job_type;
    form.complaint = item.complaint;
    form.action_taken = item.action_taken || '';
    form.technician = item.technician;
    form.cost = item.cost || 0;
    form.status = item.status;
    form.condition_after = item.condition_after || (item.status === 'Selesai' ? 'Baik' : 'Maintenance');
    form.notes = item.notes || '';
    showModal.value = true;
}

async function submitForm() {
    try {
        if (editingId.value) {
            const { data } = await api.put(`/maintenances/${editingId.value}`, {
                ...form,
                sync_asset_condition: true,
            });
            auth.notify(data.message, 'success');
        } else {
            const { data } = await api.post('/maintenances', {
                ...form,
                sync_asset_condition: true,
            });
            auth.notify(data.message, 'success');
        }
        showModal.value = false;
        fetchMaintenances();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyimpan data pemeliharaan.', 'error');
    }
}

async function deleteMaintenance(item) {
    if (!confirm(`Hapus catatan pemeliharaan ${item.ticket_number}?`)) return;
    try {
        const { data } = await api.delete(`/maintenances/${item.id}`);
        auth.notify(data.message, 'success');
        fetchMaintenances();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menghapus data.', 'error');
    }
}

onMounted(() => {
    fetchMaintenances();
    fetchAssetList();
});
</script>

