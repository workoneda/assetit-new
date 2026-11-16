<template>
    <div class="space-y-5">
        <!-- Top Action Bar -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 sm:p-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shrink-0">
                    <Boxes class="w-5 h-5" />
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold text-white">Asset Directory</h2>
                        <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">
                            {{ meta.total }} Perangkat
                        </span>
                    </div>
                    <p class="text-xs text-slate-400">
                        Kelola seluruh inventaris perangkat IT rumah sakit, cetak QR label 40&times;20 mm, serta Export &amp; Import Excel.
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                <!-- View Mode Toggle: Table View vs Grid View -->
                <div class="col-span-2 sm:col-span-1 flex rounded-xl bg-navy-950 p-1 border border-slate-800">
                    <button
                        @click="viewMode = 'table'"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="viewMode === 'table' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white'"
                    >
                        <List class="w-3.5 h-3.5" />
                        <span>Table View</span>
                    </button>
                    <button
                        @click="viewMode = 'grid'"
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                        :class="viewMode === 'grid' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30' : 'text-slate-400 hover:text-white'"
                    >
                        <LayoutGrid class="w-3.5 h-3.5" />
                        <span>Grid View</span>
                    </button>
                </div>

                <!-- Cetak QR Massal -->
                <button
                    @click="openBatchQrModal"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-navy-950 hover:bg-slate-800 border border-slate-700/80 text-xs font-semibold text-cyan-300 transition"
                >
                    <QrCode class="w-4 h-4 shrink-0" />
                    <span class="truncate">Cetak QR ({{ assets.length }})</span>
                </button>

                <!-- Import Excel -->
                <button
                    @click="showImportModal = true"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 border border-purple-500/30 text-xs font-semibold text-purple-300 transition"
                >
                    <Upload class="w-4 h-4 shrink-0" />
                    <span class="truncate">Import Excel</span>
                </button>

                <!-- Export Excel -->
                <button
                    @click="exportToExcel"
                    :disabled="exporting"
                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-xs font-semibold text-emerald-300 transition"
                >
                    <Download class="w-4 h-4 shrink-0" />
                    <span class="truncate">{{ exporting ? 'Mengekspor...' : 'Export Excel' }}</span>
                </button>

                <!-- Tambah Aset -->
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                >
                    <Plus class="w-4 h-4 stroke-[2.5] shrink-0" />
                    <span class="truncate">Tambah Aset</span>
                </button>
            </div>
        </div>

        <!-- Search & Filter Bar -->
        <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
            <!-- Search Input -->
            <div class="sm:col-span-2 lg:col-span-4 relative">
                <Search class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                    v-model="filters.search"
                    @input="debouncedFetch"
                    type="text"
                    placeholder="Cari nama aset, kode AST-XXXX, IP Address, Serial Number..."
                    class="w-full bg-navy-950 border border-slate-700/90 rounded-xl pl-10 pr-8 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
                />
                <button
                    v-if="filters.search"
                    @click="filters.search = ''; fetchAssets(1)"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>

            <!-- Filter Kategori -->
            <div class="lg:col-span-2">
                <select
                    v-model="filters.category"
                    @change="fetchAssets(1)"
                    class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500"
                >
                    <option value="">Semua Kategori</option>
                    <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
                </select>
            </div>

            <!-- Filter Ruangan -->
            <div class="lg:col-span-2">
                <select
                    v-model="filters.room_id"
                    @change="fetchAssets(1)"
                    class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500"
                >
                    <option value="">Semua Ruangan</option>
                    <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                </select>
            </div>

            <!-- Filter Kepemilikan -->
            <div class="lg:col-span-2">
                <select
                    v-model="filters.ownership"
                    @change="fetchAssets(1)"
                    class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500"
                >
                    <option value="">Semua Kepemilikan</option>
                    <option value="Milik RSUD">Milik RSUD</option>
                    <option value="KSO">KSO</option>
                    <option value="Milik Pihak Ketiga">Milik Pihak Ketiga</option>
                </select>
            </div>

            <!-- Filter Kondisi & Reset -->
            <div class="lg:col-span-2 flex gap-2">
                <select
                    v-model="filters.condition"
                    @change="fetchAssets(1)"
                    class="w-full bg-navy-950 border border-slate-700/90 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-cyan-500"
                >
                    <option value="">Semua Kondisi</option>
                    <option value="Baik">Baik</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Rusak">Rusak</option>
                </select>
                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    title="Reset Filter"
                    class="px-2.5 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-rose-300 text-xs font-semibold shrink-0"
                >
                    Reset
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="bg-navy-900/90 border border-slate-800 rounded-2xl p-12 text-center">
            <div class="w-8 h-8 border-2 border-cyan-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-xs text-slate-400">Memuat data inventaris aset dari MySQL...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!assets.length" class="bg-navy-900/90 border border-slate-800 rounded-2xl p-12 text-center space-y-3">
            <div class="w-12 h-12 rounded-2xl bg-slate-800/80 flex items-center justify-center mx-auto text-slate-400">
                <Boxes class="w-6 h-6" />
            </div>
            <h3 class="text-sm font-bold text-white">Tidak Ada Data Aset Ditemukan</h3>
            <p class="text-xs text-slate-400 max-w-md mx-auto">
                Tidak ada perangkat yang sesuai dengan kata kunci pencarian atau filter Anda. Silakan ubah filter atau tambahkan aset baru.
            </p>
            <div class="pt-2 flex justify-center gap-2">
                <button
                    v-if="hasActiveFilters"
                    @click="resetFilters"
                    class="px-4 py-2 rounded-xl border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white"
                >
                    Reset Filter
                </button>
                <button
                    @click="openCreateModal"
                    class="px-4 py-2 rounded-xl bg-cyan-500 text-slate-950 text-xs font-bold"
                >
                    + Tambah Aset Baru
                </button>
            </div>
        </div>

        <!-- MODE 1: TABLE VIEW (Responsive Mobile Cards + Desktop Full Table) -->
        <div v-else-if="viewMode === 'table'" class="space-y-3">
            <!-- Mobile Card List (Shown on Smartphones < 768px) -->
            <div class="md:hidden space-y-3">
                <div
                    v-for="item in assets"
                    :key="`mob-asset-${item.id}`"
                    class="bg-navy-900/95 border border-slate-800/90 rounded-2xl p-4 space-y-3 shadow-lg"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <router-link
                                :to="`/assets/${item.id}`"
                                class="inline-flex items-center gap-1 font-mono text-xs font-extrabold text-cyan-400 bg-cyan-500/10 border border-cyan-500/25 px-2.5 py-1 rounded-lg"
                            >
                                <QrCode class="w-3.5 h-3.5" />
                                <span>{{ item.asset_code }}</span>
                            </router-link>
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold border" :class="ownershipBadgeClass(item.ownership)">
                                {{ item.ownership }}
                            </span>
                        </div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border inline-flex items-center gap-1 shrink-0" :class="conditionBadgeClass(item.condition)">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            <span>{{ item.condition }}</span>
                        </span>
                    </div>

                    <div>
                        <router-link :to="`/assets/${item.id}`" class="font-extrabold text-sm text-white hover:text-cyan-300 block">
                            {{ item.name }}
                        </router-link>
                        <div class="text-xs text-slate-400 mt-0.5">
                            {{ item.category }} &bull; {{ item.brand || '-' }} {{ item.model || '' }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 p-2.5 rounded-xl bg-navy-950/90 border border-slate-800/80 text-xs">
                        <div>
                            <span class="text-[10px] text-slate-500 block">Lokasi Ruangan</span>
                            <span class="font-semibold text-slate-200 truncate block">{{ item.room?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">Penanggung Jawab</span>
                            <span class="font-semibold text-slate-200 truncate block">{{ item.pic || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">IP Address</span>
                            <span class="font-mono text-cyan-300">{{ item.ip_address || 'Non-IP' }}</span>
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">Serial Number</span>
                            <span class="font-mono text-slate-300 truncate block">{{ item.serial_number || '-' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-2 pt-1">
                        <router-link
                            :to="`/assets/${item.id}`"
                            class="flex-1 py-2 px-3 rounded-xl bg-cyan-500/15 border border-cyan-500/30 text-cyan-300 font-bold text-xs text-center"
                        >
                            Detail &amp; Mutasi
                        </router-link>
                        <button
                            @click="openSingleQrModal(item)"
                            class="p-2 rounded-xl bg-slate-800 text-purple-300 border border-slate-700"
                            title="Cetak QR"
                        >
                            <QrCode class="w-4 h-4" />
                        </button>
                        <button
                            @click="openEditModal(item)"
                            class="p-2 rounded-xl bg-slate-800 text-amber-300 border border-slate-700"
                            title="Edit"
                        >
                            <Edit3 class="w-4 h-4" />
                        </button>
                        <button
                            @click="confirmDelete(item)"
                            class="p-2 rounded-xl bg-slate-800 text-rose-300 border border-slate-700"
                            title="Hapus"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Table (Shown on Tablet/Desktop >= 768px) -->
            <div class="hidden md:block bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-navy-950/90 border-b border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            <th class="py-3.5 px-4">Kode Aset</th>
                            <th class="py-3.5 px-4">Nama Perangkat &amp; Spesifikasi</th>
                            <th class="py-3.5 px-4">Kategori</th>
                            <th class="py-3.5 px-4">IP / Serial Number</th>
                            <th class="py-3.5 px-4">Ruangan &amp; PIC</th>
                            <th class="py-3.5 px-4">Kepemilikan</th>
                            <th class="py-3.5 px-4">Kondisi</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70 text-xs">
                        <tr
                            v-for="item in assets"
                            :key="item.id"
                            class="hover:bg-slate-800/35 transition group"
                        >
                            <!-- Kode Aset -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <router-link
                                    :to="`/assets/${item.id}`"
                                    class="inline-flex items-center gap-1.5 font-mono font-bold text-cyan-400 hover:text-cyan-300 bg-cyan-500/10 border border-cyan-500/25 px-2.5 py-1 rounded-lg"
                                >
                                    <QrCode class="w-3.5 h-3.5" />
                                    <span>{{ item.asset_code }}</span>
                                </router-link>
                                <div class="text-[10px] text-slate-500 mt-1 font-mono">
                                    Thn: {{ item.acquisition_year || '-' }}
                                </div>
                            </td>

                            <!-- Nama Perangkat & Merk -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="flex items-center gap-3">
                                    <div
                                        v-if="item.photo_url"
                                        class="w-10 h-10 rounded-xl overflow-hidden border border-slate-700 shrink-0"
                                    >
                                        <img :src="item.photo_url" :alt="item.name" class="w-full h-full object-cover" />
                                    </div>
                                    <div
                                        v-else
                                        class="w-10 h-10 rounded-xl bg-navy-950 border border-slate-800 flex items-center justify-center text-cyan-400 shrink-0"
                                    >
                                        <Monitor class="w-4 h-4" />
                                    </div>
                                    <div class="min-w-0">
                                        <router-link :to="`/assets/${item.id}`" class="font-bold text-white hover:text-cyan-300 transition block truncate">
                                            {{ item.name }}
                                        </router-link>
                                        <div class="text-[11px] text-slate-400 truncate">
                                            {{ item.brand || '-' }} {{ item.model ? `&bull; ${item.model}` : '' }}
                                        </div>
                                        <div v-if="item.specifications" class="text-[10px] text-slate-500 truncate mt-0.5">
                                            {{ item.specifications }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800/90 border border-slate-700 text-slate-200 font-medium text-[11px]">
                                    {{ item.category }}
                                </span>
                            </td>

                            <!-- IP & SN -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono">
                                <div v-if="item.ip_address" class="text-xs text-cyan-300 font-semibold flex items-center gap-1.5">
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="item.network_status === 'offline' ? 'bg-rose-400' : 'bg-emerald-400'"
                                    ></span>
                                    <span>{{ item.ip_address }}</span>
                                </div>
                                <div v-else class="text-[11px] text-slate-500">Non-IP</div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    SN: {{ item.serial_number || '-' }}
                                </div>
                            </td>

                            <!-- Ruangan & PIC -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-200">{{ item.room?.name || '-' }}</div>
                                <div class="text-[11px] text-slate-400">PIC: {{ item.pic || '-' }}</div>
                            </td>

                            <!-- Kepemilikan -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold border" :class="ownershipBadgeClass(item.ownership)">
                                    {{ item.ownership }}
                                </span>
                                <div v-if="item.partner_name" class="text-[10px] text-purple-300 mt-1 truncate max-w-[140px]">
                                    {{ item.partner_name }}
                                </div>
                            </td>

                            <!-- Kondisi -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border inline-flex items-center gap-1" :class="conditionBadgeClass(item.condition)">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    <span>{{ item.condition }}</span>
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1">
                                    <router-link
                                        :to="`/assets/${item.id}`"
                                        title="Detail Aset"
                                        class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-cyan-500/20 text-slate-300 hover:text-cyan-300 border border-slate-700/70 transition"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                    </router-link>
                                    <button
                                        @click="openSingleQrModal(item)"
                                        title="Cetak Label QR 40x20mm"
                                        class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-purple-500/20 text-slate-300 hover:text-purple-300 border border-slate-700/70 transition"
                                    >
                                        <QrCode class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="openEditModal(item)"
                                        title="Edit Aset"
                                        class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300 border border-slate-700/70 transition"
                                    >
                                        <Edit3 class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="confirmDelete(item)"
                                        title="Hapus ke Recycle Bin"
                                        class="p-1.5 rounded-lg bg-slate-800/80 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300 border border-slate-700/70 transition"
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

        <!-- MODE 2: GRID VIEW (Kartu Aset Modern) -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div
                v-for="item in assets"
                :key="item.id"
                class="bg-navy-900/90 border border-slate-800/90 hover:border-cyan-500/40 rounded-2xl overflow-hidden flex flex-col justify-between transition-all duration-200 hover:-translate-y-0.5 shadow-lg group"
            >
                <div>
                    <!-- Top Card Image / Banner -->
                    <div class="h-36 bg-navy-950 relative border-b border-slate-800/80 flex items-center justify-center overflow-hidden">
                        <img
                            v-if="item.photo_url"
                            :src="item.photo_url"
                            :alt="item.name"
                            class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                        />
                        <div v-else class="flex flex-col items-center gap-1.5 text-slate-600">
                            <Monitor class="w-10 h-10 text-cyan-500/40" />
                            <span class="text-[10px] font-mono uppercase tracking-wider">{{ item.category }}</span>
                        </div>

                        <!-- Top Floating Badges -->
                        <div class="absolute top-2.5 left-2.5 flex items-center gap-1.5">
                            <span class="font-mono text-xs font-extrabold px-2.5 py-1 rounded-lg bg-navy-950/90 backdrop-blur-md text-cyan-300 border border-cyan-500/30">
                                {{ item.asset_code }}
                            </span>
                        </div>
                        <div class="absolute top-2.5 right-2.5">
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border backdrop-blur-md" :class="conditionBadgeClass(item.condition)">
                                {{ item.condition }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-4 space-y-2.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="text-slate-400 font-medium">{{ item.category }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="ownershipBadgeClass(item.ownership)">
                                {{ item.ownership }}
                            </span>
                        </div>

                        <router-link :to="`/assets/${item.id}`" class="block font-bold text-sm text-white hover:text-cyan-300 line-clamp-1">
                            {{ item.name }}
                        </router-link>

                        <p class="text-xs text-slate-400 line-clamp-1">
                            {{ item.brand || '-' }} {{ item.model || '' }}
                        </p>

                        <div class="pt-2 border-t border-slate-800/80 space-y-1 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">Ruangan:</span>
                                <span class="text-slate-200 font-medium truncate max-w-[160px]">{{ item.room?.name || '-' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">IP Address:</span>
                                <span class="font-mono text-cyan-300">{{ item.ip_address || 'Non-IP' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">PIC:</span>
                                <span class="text-slate-300 truncate max-w-[160px]">{{ item.pic || '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Actions -->
                <div class="px-4 py-3 bg-navy-950/60 border-t border-slate-800/80 flex items-center justify-between gap-2">
                    <router-link
                        :to="`/assets/${item.id}`"
                        class="text-xs font-bold text-cyan-400 hover:text-cyan-300 flex items-center gap-1"
                    >
                        <span>Detail Lengkap</span>
                    </router-link>
                    <div class="flex items-center gap-1">
                        <button
                            @click="openSingleQrModal(item)"
                            title="Cetak QR Label"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-purple-500/20 text-slate-300 hover:text-purple-300"
                        >
                            <QrCode class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click="openEditModal(item)"
                            title="Edit"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300"
                        >
                            <Edit3 class="w-3.5 h-3.5" />
                        </button>
                        <button
                            @click="confirmDelete(item)"
                            title="Hapus"
                            class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination Bar -->
        <div
            v-if="meta.total > 0"
            class="bg-navy-900/90 border border-slate-800/90 rounded-2xl px-5 py-3.5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
        >
            <div class="text-slate-400">
                Menampilkan <strong class="text-white">{{ meta.from || 1 }}</strong> &ndash;
                <strong class="text-white">{{ meta.to || assets.length }}</strong> dari total
                <strong class="text-cyan-400">{{ meta.total }}</strong> aset IT
            </div>

            <div class="flex items-center gap-1.5">
                <button
                    @click="fetchAssets(meta.current_page - 1)"
                    :disabled="meta.current_page <= 1"
                    class="px-3 py-1.5 rounded-lg bg-navy-950 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40"
                >
                    &larr; Sebelumnya
                </button>
                <span class="px-3 py-1.5 rounded-lg bg-cyan-500/15 border border-cyan-500/30 text-cyan-300 font-mono font-bold">
                    Hal {{ meta.current_page }} / {{ meta.last_page }}
                </span>
                <button
                    @click="fetchAssets(meta.current_page + 1)"
                    :disabled="meta.current_page >= meta.last_page"
                    class="px-3 py-1.5 rounded-lg bg-navy-950 border border-slate-700 text-slate-300 hover:text-white disabled:opacity-40"
                >
                    Berikutnya &rarr;
                </button>
            </div>
        </div>

        <!-- Modal Tambah / Edit Aset -->
        <AssetFormModal
            :show="showFormModal"
            :asset="editingAsset"
            :rooms="rooms"
            :nextCode="meta.next_code"
            @close="showFormModal = false"
            @saved="fetchAssets(meta.current_page)"
        />

        <!-- Modal Cetak Label QR -->
        <QrLabelModal
            :show="showQrModal"
            :assets="qrTargetAssets"
            @close="showQrModal = false"
        />

        <!-- Modal Konfirmasi Hapus (Soft Delete ke Recycle Bin) -->
        <div v-if="deletingAsset" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-rose-500/40 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                        <Trash2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Konfirmasi Hapus Aset</h3>
                        <p class="text-xs text-slate-400">Data akan dipindahkan ke Recycle Bin (Soft Delete)</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-xl bg-navy-950 border border-slate-800 text-xs space-y-1">
                    <div class="font-mono font-bold text-cyan-400">{{ deletingAsset.asset_code }}</div>
                    <div class="font-bold text-white text-sm">{{ deletingAsset.name }}</div>
                    <div class="text-slate-400">Lokasi: {{ deletingAsset.room?.name || '-' }}</div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Apakah Anda yakin ingin menghapus aset ini? Anda masih dapat memulihkannya kapan saja melalui menu <strong>Recycle Bin</strong>.
                </p>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        @click="deletingAsset = null"
                        class="px-4 py-2 rounded-xl border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDelete"
                        class="px-4 py-2 rounded-xl bg-rose-500 hover:bg-rose-400 text-slate-950 font-bold text-xs shadow-lg shadow-rose-500/20"
                    >
                        Ya, Pindahkan ke Recycle Bin
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Import Excel (.xlsx) -->
        <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-purple-500/40 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400">
                            <Upload class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white">Import Data Awal Aset via Excel (.xlsx)</h3>
                            <p class="text-xs text-slate-400">Unggah file Excel inventaris untuk menambahkan banyak aset sekaligus</p>
                        </div>
                    </div>
                    <button @click="showImportModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-4 rounded-xl bg-navy-950 border border-slate-800 space-y-3 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-300 font-semibold">Belum memiliki format template Excel?</span>
                        <button
                            @click="downloadImportTemplate"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-300 border border-cyan-500/30 font-semibold transition"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>Unduh Template Excel (.xlsx)</span>
                        </button>
                    </div>

                    <div class="pt-2 border-t border-slate-800">
                        <label class="block text-xs font-semibold text-slate-300 mb-2">Pilih File Excel (.xlsx / .xls / .csv)</label>
                        <input
                            type="file"
                            accept=".xlsx,.xls,.csv"
                            @change="handleExcelFileSelect"
                            class="block w-full text-xs text-slate-400 file:mr-3 file:py-2 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-500/20 file:text-purple-300 hover:file:bg-purple-500/30 cursor-pointer"
                        />
                    </div>

                    <div v-if="parsedImportRows.length" class="p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-300">
                        Terbaca <strong>{{ parsedImportRows.length }}</strong> baris data perangkat siap diimpor ke database MySQL.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button
                        @click="showImportModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white"
                    >
                        Batal
                    </button>
                    <button
                        @click="submitImportExcel"
                        :disabled="!parsedImportRows.length || importing"
                        class="px-5 py-2 rounded-xl bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-400 hover:to-indigo-500 disabled:opacity-40 text-white font-bold text-xs shadow-lg"
                    >
                        {{ importing ? 'Mengimpor ke MySQL...' : `Import Sekarang (${parsedImportRows.length} Baris)` }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import * as XLSX from 'xlsx';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import AssetFormModal from '@/components/AssetFormModal.vue';
import QrLabelModal from '@/components/QrLabelModal.vue';
import {
    Boxes,
    List,
    LayoutGrid,
    QrCode,
    Upload,
    Download,
    Plus,
    Search,
    X,
    Monitor,
    Eye,
    Edit3,
    Trash2,
} from 'lucide-vue-next';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const viewMode = ref('table');
const loading = ref(false);
const exporting = ref(false);
const importing = ref(false);

const assets = ref([]);
const rooms = ref([]);
const meta = ref({
    current_page: 1,
    last_page: 1,
    per_page: 12,
    total: 0,
    from: 0,
    to: 0,
    next_code: 'AST-0001',
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
    category: route.query.category || '',
    room_id: route.query.room_id || '',
    ownership: route.query.ownership || '',
    condition: route.query.condition || '',
    needs_followup: route.query.needs_followup || '',
});

const showFormModal = ref(false);
const editingAsset = ref(null);
const deletingAsset = ref(null);

const showQrModal = ref(false);
const qrTargetAssets = ref([]);

const showImportModal = ref(false);
const parsedImportRows = ref([]);

const hasActiveFilters = computed(() => {
    return !!(
        filters.search ||
        filters.category ||
        filters.room_id ||
        filters.ownership ||
        filters.condition ||
        filters.needs_followup
    );
});

let debounceTimer = null;
function debouncedFetch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchAssets(1);
    }, 300);
}

function resetFilters() {
    filters.search = '';
    filters.category = '';
    filters.room_id = '';
    filters.ownership = '';
    filters.condition = '';
    filters.needs_followup = '';
    router.replace({ query: {} });
    fetchAssets(1);
}

async function fetchRooms() {
    const { data } = await api.get('/rooms');
    rooms.value = data.data || [];
}

async function fetchAssets(page = 1) {
    loading.value = true;
    try {
        const { data } = await api.get('/assets', {
            params: {
                page,
                per_page: 12,
                search: filters.search,
                category: filters.category,
                room_id: filters.room_id,
                ownership: filters.ownership,
                condition: filters.condition,
                needs_followup: filters.needs_followup,
            },
        });
        assets.value = data.data || [];
        meta.value = data.meta || meta.value;
    } finally {
        loading.value = false;
    }
}

function openCreateModal() {
    editingAsset.value = null;
    showFormModal.value = true;
}

function openEditModal(item) {
    editingAsset.value = item;
    showFormModal.value = true;
}

function openSingleQrModal(item) {
    qrTargetAssets.value = [item];
    showQrModal.value = true;
}

function openBatchQrModal() {
    qrTargetAssets.value = assets.value;
    showQrModal.value = true;
}

function confirmDelete(item) {
    deletingAsset.value = item;
}

async function executeDelete() {
    if (!deletingAsset.value) return;
    try {
        const { data } = await api.delete(`/assets/${deletingAsset.value.id}`);
        auth.notify(data.message, 'success');
        deletingAsset.value = null;
        fetchAssets(meta.value.current_page);
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menghapus aset.', 'error');
    }
}

async function exportToExcel() {
    exporting.value = true;
    try {
        const { data } = await api.get('/assets', {
            params: {
                all: 1,
                search: filters.search,
                category: filters.category,
                room_id: filters.room_id,
                ownership: filters.ownership,
                condition: filters.condition,
            },
        });

        const rows = (data.data || []).map((item, idx) => ({
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
            'Penanggung Jawab (PIC)': item.pic || '-',
            Kepemilikan: item.ownership,
            'Nama Mitra KSO/Pihak Ke-3': item.partner_name || '-',
            Kondisi: item.condition,
            'Tahun Perolehan': item.acquisition_year || '-',
            Keterangan: item.notes || '-',
        }));

        const ws = XLSX.utils.json_to_sheet(rows);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Inventaris Aset IT');
        XLSX.writeFile(wb, `Data_Inventaris_Aset_IT_RSUD_${new Date().toISOString().slice(0, 10)}.xlsx`);
        auth.notify(`Export Excel berhasil (${rows.length} baris aset).`, 'success');
    } catch {
        auth.notify('Gagal mengekspor data ke Excel.', 'error');
    } finally {
        exporting.value = false;
    }
}

function downloadImportTemplate() {
    const sampleRows = [
        {
            'Kode Aset': '',
            'Nama Aset': 'PC Loket Pendaftaran Poliklinik #2',
            Kategori: 'Komputer / PC',
            Merk: 'HP',
            Model: 'ProOne 440 G9',
            'Serial Number': '8CC342199B',
            'IP Address': '10.10.20.12',
            'MAC Address': '3C:D9:2B:10:20:12',
            Spesifikasi: 'Core i5-13500, RAM 16GB, SSD 512GB, Win 11 Pro',
            Ruangan: 'Instalasi Rekam Medis',
            'Penanggung Jawab': 'Siti Aminah, A.Md.RMIK',
            Kepemilikan: 'Milik RSUD',
            'Nama Mitra': '',
            Kondisi: 'Baik',
            'Tahun Perolehan': 2025,
            Keterangan: 'Unit komputer baru poliklinik spesialis',
        },
    ];
    const ws = XLSX.utils.json_to_sheet(sampleRows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Template Import Aset');
    XLSX.writeFile(wb, 'Template_Import_Aset_IT_RSUD.xlsx');
}

function handleExcelFileSelect(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (evt) => {
        const data = new Uint8Array(evt.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
        const rawJson = XLSX.utils.sheet_to_json(firstSheet, { defval: '' });

        parsedImportRows.value = rawJson
            .map((r) => {
                const rawJenis = String(r['Kategori'] || r['JENIS PERANGKAT'] || r['category'] || 'Komputer / PC').trim().toUpperCase();
                let mappedCat = 'Komputer / PC';
                if (rawJenis.includes('LAPTOP')) mappedCat = 'Laptop';
                else if (rawJenis.includes('PRINTER')) mappedCat = 'Printer';
                else if (rawJenis.includes('SWITCH')) mappedCat = 'Switch';
                else if (rawJenis.includes('ROUTER')) mappedCat = 'Router';
                else if (rawJenis.includes('SERVER')) mappedCat = 'Server';
                else if (rawJenis.includes('ACCESS POINT')) mappedCat = 'Access Point';
                else if (rawJenis.includes('UPS')) mappedCat = 'UPS';
                else if (rawJenis.includes('LAIN') || rawJenis.includes('SCANNER')) mappedCat = 'Perangkat IT lainnya';

                const rawOwner = String(r['Kepemilikan'] || r['STATUS KEPEMILIKAN '] || r['STATUS KEPEMILIKAN'] || r['ownership'] || 'Milik RSUD').trim();
                let mappedOwner = 'Milik RSUD';
                let mappedPartner = String(r['Nama Mitra'] || r['Nama Mitra KSO/Pihak Ke-3'] || r['partner_name'] || '').trim();
                if (rawOwner.toUpperCase().includes('KSO')) {
                    mappedOwner = 'KSO';
                    const m = rawOwner.match(/\(([^)]+)\)/);
                    if (m && !mappedPartner) mappedPartner = m[1].trim();
                } else if (rawOwner.toUpperCase().includes('PIHAK KETIGA') || rawOwner.toUpperCase() === 'LAINNYA') {
                    mappedOwner = 'Milik Pihak Ketiga';
                }

                const rawCond = String(r['Kondisi'] || r['KONDISI'] || r['condition'] || 'Baik').trim().toUpperCase();
                const rawNotes = String(r['Keterangan'] || r['KETERANGAN'] || r['notes'] || '').trim();
                let mappedCond = 'Baik';
                if (rawCond === 'RUSAK') mappedCond = 'Rusak';
                else if (rawCond === 'MAINTENANCE' || rawNotes.toUpperCase().includes('NEED UPGRADE') || rawNotes.toUpperCase().includes('SEDANG DIPERBAIKI')) {
                    mappedCond = 'Maintenance';
                }

                const rawName = String(r['Nama Aset'] || r['PERANGKAT IT'] || r['name'] || '').trim();
                const rawSpec = String(r['Spesifikasi'] || r['SPESIFIKASI'] || r['specifications'] || '').trim();

                return {
                    asset_code: String(r['Kode Aset'] || r['asset_code'] || '').trim(),
                    name: rawName || (rawSpec ? `Unit PC (${rawSpec})` : ''),
                    category: mappedCat,
                    brand: String(r['Merk'] || r['brand'] || '').trim(),
                    model: String(r['Model'] || r['model'] || '').trim(),
                    serial_number: String(r['Serial Number'] || r['NO SERI'] || r['serial_number'] || '').trim(),
                    ip_address: String(r['IP Address'] || r['IP '] || r['IP'] || r['ip_address'] || '').trim(),
                    mac_address: String(r['MAC Address'] || r['mac_address'] || '').trim(),
                    specifications: rawSpec,
                    room_name: String(r['Ruangan'] || r['RUANGAN'] || r['room_name'] || 'IT').trim(),
                    pic: String(r['Penanggung Jawab'] || r['Penanggung Jawab (PIC)'] || r['PENGGUNA'] || r['pic'] || '').trim(),
                    ownership: mappedOwner,
                    partner_name: mappedPartner,
                    condition: mappedCond,
                    acquisition_year: parseInt(r['Tahun Perolehan'] || r['acquisition_year'] || new Date().getFullYear(), 10) || new Date().getFullYear(),
                    notes: rawNotes,
                };
            })
            .filter((r) => r.name.length > 0);
    };
    reader.readAsArrayBuffer(file);
}

async function submitImportExcel() {
    if (!parsedImportRows.value.length) return;
    importing.value = true;
    try {
        const { data } = await api.post('/assets/import', {
            rows: parsedImportRows.value,
        });
        auth.notify(data.message, 'success');
        showImportModal.value = false;
        parsedImportRows.value = [];
        await fetchRooms();
        await fetchAssets(1);
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal mengimpor file Excel.', 'error');
    } finally {
        importing.value = false;
    }
}

function conditionBadgeClass(cond) {
    if (cond === 'Baik') return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30';
    if (cond === 'Rusak') return 'bg-rose-500/15 text-rose-300 border-rose-500/30';
    return 'bg-amber-500/15 text-amber-300 border-amber-500/30';
}

function ownershipBadgeClass(own) {
    if (own === 'Milik RSUD') return 'bg-cyan-500/15 text-cyan-300 border-cyan-500/30';
    if (own === 'KSO') return 'bg-purple-500/15 text-purple-300 border-purple-500/30';
    return 'bg-amber-500/15 text-amber-300 border-amber-500/30';
}

watch(
    () => route.query.action,
    (act) => {
        if (act === 'create') {
            openCreateModal();
            router.replace({ query: {} });
        }
    }
);

onMounted(async () => {
    await fetchRooms();
    await fetchAssets(1);
    if (route.query.action === 'create') {
        openCreateModal();
        router.replace({ query: {} });
    }
});
</script>

