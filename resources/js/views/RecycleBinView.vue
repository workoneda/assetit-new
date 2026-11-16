<template>
    <div class="space-y-6">
        <!-- Header Banner -->
        <div class="bg-navy-900/90 border border-rose-500/30 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400">
                    <Trash2 class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold text-white">Recycle Bin (Aset Dihapus Sementara)</h2>
                        <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full bg-rose-500/15 text-rose-300 border border-rose-500/30">
                            {{ items.length }} Aset
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Pulihkan kembali aset ke daftar inventaris aktif atau hapus secara permanen dari database MySQL.
                    </p>
                </div>
            </div>

            <div v-if="items.length" class="flex items-center gap-2.5">
                <button
                    @click="restoreAllItems"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-xs font-semibold text-emerald-300 transition"
                >
                    <RotateCcw class="w-4 h-4" />
                    <span>Pulihkan Semua</span>
                </button>
                <button
                    @click="confirmEmptyBin = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-xs font-semibold text-rose-300 transition"
                >
                    <Trash2 class="w-4 h-4" />
                    <span>Kosongkan Recycle Bin</span>
                </button>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 flex items-center gap-3">
            <div class="relative flex-1 max-w-md">
                <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                    v-model="search"
                    @input="fetchTrashed"
                    type="text"
                    placeholder="Cari kode aset, nama perangkat, atau serial number..."
                    class="w-full bg-navy-950 border border-slate-700 rounded-xl pl-10 pr-4 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                />
            </div>
        </div>

        <!-- Trashed Items: Mobile Cards (< 768px) & Desktop Table (>= 768px) -->
        <div class="space-y-3">
            <div class="md:hidden space-y-3">
                <div v-if="!items.length" class="bg-navy-900 border border-slate-800 rounded-2xl p-8 text-center text-xs text-slate-400">
                    Recycle Bin kosong. Tidak ada data aset yang dihapus sementara.
                </div>
                <div
                    v-for="item in items"
                    :key="`mob-bin-${item.id}`"
                    class="bg-navy-900/95 border border-slate-800/90 rounded-2xl p-4 space-y-3 text-xs shadow-lg"
                >
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-mono font-bold text-cyan-400">{{ item.asset_code }}</span>
                        <span class="font-mono text-[11px] text-rose-300">{{ formatDate(item.deleted_at) }}</span>
                    </div>
                    <div>
                        <div class="font-bold text-sm text-white">{{ item.name }}</div>
                        <div class="text-slate-400 mt-0.5">{{ item.category }} &bull; {{ item.room?.name || '-' }}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <button
                            @click="restoreItem(item)"
                            class="inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-300 font-bold"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span>Pulihkan</span>
                        </button>
                        <button
                            @click="targetPermanentDelete = item"
                            class="inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-300 font-bold"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                            <span>Hapus Permanen</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="hidden md:block bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-navy-950/90 border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-3.5 px-4">Kode Aset</th>
                            <th class="py-3.5 px-4">Nama Perangkat</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">Ruangan Terakhir</th>
                            <th class="py-3.5 px-4">Waktu Dihapus</th>
                            <th class="py-3.5 px-4 text-right">Aksi Pemulihan / Hapus Permanen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        <tr v-if="!items.length">
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                Recycle Bin kosong. Tidak ada data aset yang dihapus sementara.
                            </td>
                        </tr>
                        <tr v-for="item in items" :key="item.id" class="hover:bg-slate-800/35">
                            <td class="py-3.5 px-4 font-mono font-bold text-cyan-400">{{ item.asset_code }}</td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white">{{ item.name }}</div>
                                <div class="text-[11px] text-slate-400">{{ item.brand }} {{ item.model }} &bull; SN: {{ item.serial_number || '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-300">{{ item.category }}</td>
                            <td class="py-3.5 px-4 text-slate-300">{{ item.room?.name || '-' }}</td>
                            <td class="py-3.5 px-4 font-mono text-rose-300">{{ formatDate(item.deleted_at) }}</td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        @click="restoreItem(item)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-300 font-semibold transition"
                                    >
                                        <RotateCcw class="w-3.5 h-3.5" />
                                        <span>Pulihkan</span>
                                    </button>
                                    <button
                                        @click="targetPermanentDelete = item"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-rose-300 font-semibold transition"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                        <span>Hapus Permanen</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Hapus Permanen -->
        <div v-if="targetPermanentDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-rose-500/50 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-white">Hapus Permanen dari Database MySQL?</h3>
                <div class="p-3.5 rounded-xl bg-navy-950 border border-slate-800 text-xs">
                    <div class="font-mono font-bold text-cyan-400">{{ targetPermanentDelete.asset_code }}</div>
                    <div class="font-bold text-white">{{ targetPermanentDelete.name }}</div>
                </div>
                <p class="text-xs text-rose-200/90 leading-relaxed">
                    Perhatian: Tindakan ini akan menghapus data aset beserta riwayatnya secara permanen dari tabel MySQL dan <strong>tidak dapat dibatalkan</strong>.
                </p>
                <div class="flex justify-end gap-2.5 pt-2">
                    <button @click="targetPermanentDelete = null" class="px-4 py-2 rounded-xl border border-slate-700 text-xs text-slate-300">
                        Batal
                    </button>
                    <button @click="executePermanentDelete" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-slate-950 font-bold text-xs">
                        Ya, Hapus Permanen
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Kosongkan Recycle Bin -->
        <div v-if="confirmEmptyBin" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-rose-500/50 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <h3 class="text-base font-bold text-white">Kosongkan Seluruh Recycle Bin?</h3>
                <p class="text-xs text-slate-300 leading-relaxed">
                    Seluruh <strong>{{ items.length }} aset</strong> di Recycle Bin akan dihapus permanen dari MySQL.
                </p>
                <div class="flex justify-end gap-2.5 pt-2">
                    <button @click="confirmEmptyBin = false" class="px-4 py-2 rounded-xl border border-slate-700 text-xs text-slate-300">
                        Batal
                    </button>
                    <button @click="executeEmptyBin" class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-slate-950 font-bold text-xs">
                        Kosongkan Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { Trash2, RotateCcw, Search } from 'lucide-vue-next';

const auth = useAuthStore();
const items = ref([]);
const search = ref('');
const targetPermanentDelete = ref(null);
const confirmEmptyBin = ref(false);

async function fetchTrashed() {
    const { data } = await api.get('/recycle-bin', { params: { search: search.value } });
    items.value = data.data || [];
}

async function restoreItem(item) {
    try {
        const { data } = await api.post(`/recycle-bin/${item.id}/restore`);
        auth.notify(data.message, 'success');
        fetchTrashed();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal memulihkan aset.', 'error');
    }
}

async function restoreAllItems() {
    try {
        const { data } = await api.post('/recycle-bin/restore-all');
        auth.notify(data.message, 'success');
        fetchTrashed();
    } catch {
        auth.notify('Gagal memulihkan aset.', 'error');
    }
}

async function executePermanentDelete() {
    if (!targetPermanentDelete.value) return;
    try {
        const { data } = await api.delete(`/recycle-bin/${targetPermanentDelete.value.id}`);
        auth.notify(data.message, 'success');
        targetPermanentDelete.value = null;
        fetchTrashed();
    } catch {
        auth.notify('Gagal menghapus aset permanen.', 'error');
    }
}

async function executeEmptyBin() {
    try {
        const { data } = await api.delete('/recycle-bin/empty/all');
        auth.notify(data.message, 'success');
        confirmEmptyBin.value = false;
        fetchTrashed();
    } catch {
        auth.notify('Gagal mengosongkan Recycle Bin.', 'error');
    }
}

function formatDate(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID');
}

onMounted(() => {
    fetchTrashed();
});
</script>

