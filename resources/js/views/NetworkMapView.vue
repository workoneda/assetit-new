<template>
    <div class="space-y-6">
        <!-- Top Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div class="bg-navy-900/90 border border-cyan-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Total Perangkat IP</div>
                <div class="text-2xl font-mono font-extrabold text-white mt-1">{{ stats.total_devices }}</div>
                <div class="text-[11px] text-cyan-400 mt-1">Terdaftar pada Alokasi IP</div>
            </div>
            <div class="bg-navy-900/90 border border-emerald-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Status Online</div>
                <div class="text-2xl font-mono font-extrabold text-emerald-400 mt-1">{{ stats.online }}</div>
                <div class="text-[11px] text-emerald-300/80 mt-1">Koneksi Aktif</div>
            </div>
            <div class="bg-navy-900/90 border border-rose-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Status Offline</div>
                <div class="text-2xl font-mono font-extrabold text-rose-400 mt-1">{{ stats.offline }}</div>
                <div class="text-[11px] text-rose-300/80 mt-1">Perlu Pengecekan</div>
            </div>
            <div class="bg-navy-900/90 border border-purple-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Core Infra / Network</div>
                <div class="text-2xl font-mono font-extrabold text-purple-400 mt-1">{{ stats.core_infra }}</div>
                <div class="text-[11px] text-purple-300/80 mt-1">Router, Switch, Server, AP</div>
            </div>
            <div class="col-span-2 sm:col-span-1 bg-navy-900/90 border border-blue-500/30 rounded-2xl p-4">
                <div class="text-xs text-slate-400">Endpoint Client</div>
                <div class="text-2xl font-mono font-extrabold text-blue-400 mt-1">{{ stats.endpoints }}</div>
                <div class="text-[11px] text-blue-300/80 mt-1">PC, Laptop, Printer, Kiosk</div>
            </div>
        </div>

        <!-- Visualisasi Topologi Jaringan Sederhana (Section 7) -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-800 pb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400">
                        <Network class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Visualisasi Topologi Jaringan Rumah Sakit</h3>
                        <p class="text-xs text-slate-400">
                            Hirarki Core Gateway &rarr; Core/Distribution Switch &rarr; Server Farm, Access Point &amp; Perangkat Ruangan
                        </p>
                    </div>
                </div>

                <button
                    @click="pingAllDevices"
                    :disabled="pingingAll"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                >
                    <Activity class="w-4 h-4" :class="{ 'animate-spin': pingingAll }" />
                    <span>{{ pingingAll ? 'Memeriksa Koneksi...' : 'Cek Status Seluruh IP' }}</span>
                </button>
            </div>

            <!-- Interactive Layered Topology Diagram (Responsive on Mobile & Desktop) -->
            <div class="bg-navy-950/90 border border-slate-800/90 rounded-2xl p-3.5 sm:p-5 overflow-x-auto">
                <div class="space-y-6">
                    <!-- Layer 1: Gateway & Core Routers -->
                    <div class="text-center">
                        <div class="inline-block text-[10px] font-mono font-bold uppercase tracking-wider px-2.5 py-0.5 rounded bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 mb-3">
                            Layer 1 &bull; Core Router &amp; Gateway Utama
                        </div>
                        <div class="flex justify-center flex-wrap gap-3 sm:gap-4">
                            <div
                                v-for="node in routerNodes"
                                :key="node.id"
                                @click="router.push(`/assets/${node.id}`)"
                                class="p-3.5 rounded-xl bg-navy-900 border-2 border-cyan-500/50 hover:border-cyan-400 cursor-pointer w-full sm:w-64 text-left shadow-lg shadow-cyan-950/40 transition"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-bold text-cyan-300">{{ node.ip_address || 'No-IP' }}</span>
                                    <span class="flex items-center gap-1 text-[10px] font-bold" :class="node.network_status === 'online' ? 'text-emerald-400' : 'text-rose-400'">
                                        <span class="w-2 h-2 rounded-full" :class="node.network_status === 'online' ? 'bg-emerald-400 animate-pulse' : 'bg-rose-500'"></span>
                                        {{ (node.network_status || 'online').toUpperCase() }}
                                    </span>
                                </div>
                                <div class="text-xs font-bold text-white truncate mt-1">{{ node.name }}</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ node.room?.name }} &bull; {{ node.asset_code }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Connector Line -->
                    <div class="flex justify-center">
                        <div class="w-0.5 h-6 bg-gradient-to-b from-cyan-500 to-purple-500"></div>
                    </div>

                    <!-- Layer 2: Core & Distribution Switches -->
                    <div class="text-center">
                        <div class="inline-block text-[10px] font-mono font-bold uppercase tracking-wider px-2.5 py-0.5 rounded bg-purple-500/15 text-purple-300 border border-purple-500/30 mb-3">
                            Layer 2 &bull; Core &amp; Distribution Switch
                        </div>
                        <div class="flex justify-center flex-wrap gap-3 sm:gap-4">
                            <div
                                v-for="node in switchNodes"
                                :key="node.id"
                                @click="router.push(`/assets/${node.id}`)"
                                class="p-3.5 rounded-xl bg-navy-900 border border-purple-500/50 hover:border-purple-400 cursor-pointer w-full sm:w-64 text-left transition"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-bold text-purple-300">{{ node.ip_address || 'No-IP' }}</span>
                                    <span class="flex items-center gap-1 text-[10px] font-bold" :class="node.network_status === 'online' ? 'text-emerald-400' : 'text-rose-400'">
                                        <span class="w-2 h-2 rounded-full" :class="node.network_status === 'online' ? 'bg-emerald-400' : 'bg-rose-500'"></span>
                                        {{ node.latency_ms ? `${node.latency_ms}ms` : node.network_status }}
                                    </span>
                                </div>
                                <div class="text-xs font-bold text-white truncate mt-1">{{ node.name }}</div>
                                <div class="text-[11px] text-slate-400 truncate">{{ node.room?.name }} &bull; {{ node.vlan || 'Trunk' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Connector Line -->
                    <div class="flex justify-center">
                        <div class="w-0.5 h-6 bg-gradient-to-b from-purple-500 to-emerald-500"></div>
                    </div>

                    <!-- Layer 3: Servers, Access Points, UPS & Endpoints -->
                    <div>
                        <div class="text-center mb-3">
                            <span class="inline-block text-[10px] font-mono font-bold uppercase tracking-wider px-2.5 py-0.5 rounded bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                                Layer 3 &bull; Server Farm, Access Point &amp; Endpoint Pelayanan
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <div
                                v-for="node in endpointAndServerNodes"
                                :key="node.id"
                                @click="router.push(`/assets/${node.id}`)"
                                class="p-3 rounded-xl bg-navy-900 border hover:border-cyan-500/50 cursor-pointer transition"
                                :class="node.network_status === 'offline' ? 'border-rose-500/40 bg-rose-950/10' : 'border-slate-800'"
                            >
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-mono font-bold text-cyan-300">{{ node.ip_address || 'Non-IP' }}</span>
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                                        :class="node.network_status === 'offline'
                                            ? 'bg-rose-500/20 text-rose-300'
                                            : 'bg-emerald-500/20 text-emerald-300'"
                                    >
                                        {{ node.network_status || 'online' }}
                                    </span>
                                </div>
                                <div class="text-xs font-bold text-white truncate mt-1">{{ node.name }}</div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                    <span class="truncate">{{ node.room?.name }}</span>
                                    <span class="font-mono text-slate-500">{{ node.category }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Perangkat Berdasarkan IP Address (Table View) -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-white">Daftar Perangkat Berdasarkan IP Address &amp; MAC Address</h3>
                    <p class="text-xs text-slate-400">Klik tombol Ping pada baris perangkat untuk menguji/memperbarui status koneksi</p>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <Search class="w-3.5 h-3.5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="filters.search"
                            @input="fetchNetworkMap"
                            type="text"
                            placeholder="Cari IP, MAC, Nama, VLAN..."
                            class="bg-navy-950 border border-slate-700 rounded-xl pl-8 pr-3 py-1.5 text-xs text-white focus:outline-none focus:border-cyan-500"
                        />
                    </div>
                    <select
                        v-model="filters.network_status"
                        @change="fetchNetworkMap"
                        class="bg-navy-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-slate-200"
                    >
                        <option value="">Semua Status</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-3 px-3">IP Address</th>
                            <th class="py-3 px-3">MAC Address</th>
                            <th class="py-3 px-3">Nama Perangkat</th>
                            <th class="py-3 px-3">Jenis / Kategori</th>
                            <th class="py-3 px-3">Lokasi Ruangan</th>
                            <th class="py-3 px-3">Segmen VLAN / Uplink</th>
                            <th class="py-3 px-3">Status Koneksi</th>
                            <th class="py-3 px-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-xs">
                        <tr
                            v-for="dev in devices"
                            :key="dev.id"
                            class="hover:bg-slate-800/40 transition"
                        >
                            <td class="py-3 px-3 font-mono font-bold text-cyan-400">
                                {{ dev.ip_address || '-' }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-300">
                                {{ dev.mac_address || '-' }}
                            </td>
                            <td class="py-3 px-3">
                                <router-link :to="`/assets/${dev.id}`" class="font-bold text-white hover:text-cyan-300">
                                    {{ dev.name }}
                                </router-link>
                                <div class="text-[10px] font-mono text-slate-400">{{ dev.asset_code }} &bull; {{ dev.brand }} {{ dev.model }}</div>
                            </td>
                            <td class="py-3 px-3 text-slate-300">{{ dev.category }}</td>
                            <td class="py-3 px-3 text-slate-200 font-medium">{{ dev.room?.name || '-' }}</td>
                            <td class="py-3 px-3">
                                <div class="text-slate-300">{{ dev.vlan || 'Default LAN' }}</div>
                                <div v-if="dev.uplink" class="text-[10px] font-mono text-purple-300">
                                    Uplink: {{ dev.uplink.name }} ({{ dev.uplink.ip_address }})
                                </div>
                            </td>
                            <td class="py-3 px-3">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                    :class="dev.network_status === 'offline'
                                        ? 'bg-rose-500/15 text-rose-300 border-rose-500/30'
                                        : 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <span>{{ (dev.network_status || 'online').toUpperCase() }}</span>
                                    <span v-if="dev.latency_ms" class="font-mono opacity-80">({{ dev.latency_ms }}ms)</span>
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        @click="togglePingDevice(dev)"
                                        class="px-2.5 py-1 rounded-lg bg-navy-950 hover:bg-cyan-500/20 border border-slate-700 text-[11px] font-semibold text-cyan-300 transition"
                                    >
                                        Ping / Cek
                                    </button>
                                    <router-link
                                        :to="`/assets/${dev.id}`"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[11px] text-slate-200"
                                    >
                                        Detail
                                    </router-link>
                                </div>
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
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { Network, Activity, Search } from 'lucide-vue-next';

const router = useRouter();
const auth = useAuthStore();

const devices = ref([]);
const stats = ref({
    total_devices: 0,
    online: 0,
    offline: 0,
    core_infra: 0,
    endpoints: 0,
});
const pingingAll = ref(false);

const filters = reactive({
    search: '',
    network_status: '',
});

const routerNodes = computed(() => devices.value.filter((d) => d.category === 'Router'));
const switchNodes = computed(() => devices.value.filter((d) => d.category === 'Switch'));
const endpointAndServerNodes = computed(() =>
    devices.value.filter((d) => !['Router', 'Switch'].includes(d.category))
);

async function fetchNetworkMap() {
    const { data } = await api.get('/network-map', { params: filters });
    devices.value = data.devices || [];
    stats.value = data.stats || stats.value;
}

async function togglePingDevice(dev) {
    try {
        const { data } = await api.post(`/network-map/${dev.id}/ping`);
        auth.notify(data.message, 'info');
        await fetchNetworkMap();
    } catch {
        auth.notify('Gagal memeriksa status perangkat.', 'error');
    }
}

async function pingAllDevices() {
    pingingAll.value = true;
    try {
        const { data } = await api.post('/network-map/ping-all');
        auth.notify(data.message, 'success');
        await fetchNetworkMap();
    } finally {
        pingingAll.value = false;
    }
}

onMounted(() => {
    fetchNetworkMap();
});
</script>

