<template>
    <div class="space-y-6">
        <!-- Header Bar -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-sky-500/15 border border-sky-500/30 flex items-center justify-center text-sky-400">
                    <Building2 class="w-5 h-5" />
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-white">Manajemen Master Data Ruangan</h2>
                    <p class="text-xs text-slate-400">
                        Pengelompokan lokasi penempatan perangkat IT berdasarkan Instalasi &amp; Gedung Rumah Sakit
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <div class="relative">
                    <Search class="w-4 h-4 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        @input="fetchRooms"
                        type="text"
                        placeholder="Cari ruangan / gedung..."
                        class="bg-navy-950 border border-slate-700 rounded-xl pl-9 pr-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                    />
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20"
                >
                    <Plus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Ruangan</span>
                </button>
            </div>
        </div>

        <!-- Room Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="room in rooms"
                :key="room.id"
                class="bg-navy-900/90 border border-slate-800/90 hover:border-sky-500/40 rounded-2xl p-5 flex flex-col justify-between transition group"
            >
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="font-mono text-xs font-bold px-2.5 py-0.5 rounded bg-sky-500/15 text-sky-300 border border-sky-500/30">
                                {{ room.code }}
                            </span>
                            <h3 class="text-base font-extrabold text-white mt-2">{{ room.name }}</h3>
                            <p class="text-xs text-cyan-400/90 font-medium">{{ room.building }}</p>
                        </div>
                        <div class="text-right bg-navy-950 border border-slate-800 px-3 py-2 rounded-xl">
                            <div class="text-xl font-mono font-black text-white">{{ room.assets_count || 0 }}</div>
                            <div class="text-[10px] text-slate-400">Unit Aset</div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400 line-clamp-2">
                        {{ room.description || 'Lokasi penempatan perangkat IT rumah sakit.' }}
                    </p>

                    <div class="text-xs text-slate-300">
                        <span class="text-slate-500">Penanggung Jawab / Ka. Ruang:</span>
                        <strong class="ml-1">{{ room.pic_name || '-' }}</strong>
                    </div>

                    <!-- Condition Breakdown Pills -->
                    <div class="grid grid-cols-3 gap-2 pt-2">
                        <div class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-center">
                            <div class="text-xs font-bold text-emerald-400">{{ room.baik_count || 0 }}</div>
                            <div class="text-[10px] text-slate-400">Baik</div>
                        </div>
                        <div class="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-center">
                            <div class="text-xs font-bold text-amber-400">{{ room.maintenance_count || 0 }}</div>
                            <div class="text-[10px] text-slate-400">Maintenance</div>
                        </div>
                        <div class="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-center">
                            <div class="text-xs font-bold text-rose-400">{{ room.rusak_count || 0 }}</div>
                            <div class="text-[10px] text-slate-400">Rusak</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between">
                    <router-link
                        :to="`/assets?room_id=${room.id}`"
                        class="text-xs font-bold text-cyan-400 hover:underline"
                    >
                        Lihat Aset Ruangan &rarr;
                    </router-link>

                    <div class="flex items-center gap-1.5">
                        <button
                            @click="openEdit(room)"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300"
                            title="Edit Ruangan"
                        >
                            <Edit3 class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click="handleDelete(room)"
                            :disabled="room.assets_count > 0"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300 disabled:opacity-35 disabled:cursor-not-allowed"
                            :title="room.assets_count > 0 ? 'Tidak dapat dihapus karena memiliki aset terkait' : 'Hapus Ruangan'"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Ruangan -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">
                        {{ editingRoom ? 'Edit Data Ruangan' : 'Tambah Ruangan Baru' }}
                    </h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitRoom" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Kode Ruangan</label>
                        <input v-model="form.code" type="text" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 font-mono text-cyan-300" />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Nama Ruangan / Instalasi <span class="text-rose-400">*</span></label>
                        <input v-model="form.name" type="text" required placeholder="Contoh: Instalasi Radiologi" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Gedung / Lantai <span class="text-rose-400">*</span></label>
                        <input v-model="form.building" type="text" required placeholder="Contoh: Gedung Diagnostik Terpadu Lt. 1" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Kepala Ruangan / Koordinator</label>
                        <input v-model="form.pic_name" type="text" placeholder="Nama penanggung jawab ruangan" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Keterangan</label>
                        <textarea v-model="form.description" rows="2" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                        <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-500 text-slate-950 font-bold">Simpan Ruangan</button>
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
import { Building2, Search, Plus, Edit3, Trash2, X } from 'lucide-vue-next';

const auth = useAuthStore();
const rooms = ref([]);
const nextCode = ref('RM-010');
const search = ref('');

const showModal = ref(false);
const editingRoom = ref(null);
const form = reactive({
    code: '',
    name: '',
    building: '',
    pic_name: '',
    description: '',
});

async function fetchRooms() {
    const { data } = await api.get('/rooms', { params: { search: search.value } });
    rooms.value = data.data || [];
    nextCode.value = data.next_code || 'RM-010';
}

function openCreate() {
    editingRoom.value = null;
    form.code = nextCode.value;
    form.name = '';
    form.building = '';
    form.pic_name = '';
    form.description = '';
    showModal.value = true;
}

function openEdit(room) {
    editingRoom.value = room;
    form.code = room.code;
    form.name = room.name;
    form.building = room.building;
    form.pic_name = room.pic_name || '';
    form.description = room.description || '';
    showModal.value = true;
}

async function submitRoom() {
    try {
        if (editingRoom.value) {
            const { data } = await api.put(`/rooms/${editingRoom.value.id}`, form);
            auth.notify(data.message, 'success');
        } else {
            const { data } = await api.post('/rooms', form);
            auth.notify(data.message, 'success');
        }
        showModal.value = false;
        fetchRooms();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyimpan ruangan.', 'error');
    }
}

async function handleDelete(room) {
    if (room.assets_count > 0) {
        auth.notify('Ruangan tidak dapat dihapus karena masih memiliki aset terkait.', 'warning');
        return;
    }
    if (!confirm(`Hapus ruangan ${room.name}?`)) return;
    try {
        const { data } = await api.delete(`/rooms/${room.id}`);
        auth.notify(data.message, 'success');
        fetchRooms();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menghapus ruangan.', 'error');
    }
}

onMounted(() => {
    fetchRooms();
});
</script>

