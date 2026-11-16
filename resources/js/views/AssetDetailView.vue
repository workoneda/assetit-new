<template>
    <div class="space-y-6">
        <!-- Top Navigation & Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 no-print">
            <button
                @click="router.push('/assets')"
                class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-cyan-400 transition w-fit"
            >
                <ArrowLeft class="w-4 h-4" />
                <span>Kembali ke Asset Directory</span>
            </button>

            <div v-if="asset" class="flex flex-wrap items-center gap-2">
                <button
                    @click="showQrModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 border border-purple-500/30 text-xs font-semibold text-purple-300 transition"
                >
                    <QrCode class="w-4 h-4" />
                    <span>Cetak Label QR (40&times;20 mm)</span>
                </button>
                <button
                    @click="printAssetDetail"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-navy-900 hover:bg-slate-800 border border-slate-700 text-xs font-semibold text-slate-200 transition"
                >
                    <Printer class="w-4 h-4" />
                    <span>Cetak Detail Aset</span>
                </button>
                <button
                    @click="showTransferModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-teal-500/15 hover:bg-teal-500/25 border border-teal-500/30 text-xs font-semibold text-teal-300 transition"
                >
                    <ArrowLeftRight class="w-4 h-4" />
                    <span>Pindahkan Aset</span>
                </button>
                <button
                    @click="showMaintenanceModal = true"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-xs font-semibold text-amber-300 transition"
                >
                    <Wrench class="w-4 h-4" />
                    <span>Catat Pemeliharaan</span>
                </button>
                <button
                    @click="showEditModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                >
                    <Edit3 class="w-4 h-4" />
                    <span>Edit Aset</span>
                </button>
            </div>
        </div>

        <!-- Loading / Not Found -->
        <div v-if="loading" class="bg-navy-900 border border-slate-800 rounded-2xl p-12 text-center">
            <div class="w-8 h-8 border-2 border-cyan-400 border-t-transparent rounded-full animate-spin mx-auto mb-3"></div>
            <p class="text-xs text-slate-400">Memuat detail lengkap aset...</p>
        </div>

        <div v-else-if="!asset" class="bg-navy-900 border border-rose-500/30 rounded-2xl p-12 text-center space-y-3">
            <h3 class="text-base font-bold text-white">Aset Tidak Ditemukan</h3>
            <p class="text-xs text-slate-400">Kode aset atau ID yang Anda pindai tidak terdaftar dalam database aktif.</p>
            <router-link to="/assets" class="inline-block px-4 py-2 rounded-xl bg-cyan-500 text-slate-950 text-xs font-bold">
                Kembali ke Daftar Aset
            </router-link>
        </div>

        <!-- Asset Detail Content -->
        <template v-else>
            <!-- Print Header (Only visible when printing) -->
            <div class="hidden print:block border-b-2 border-slate-900 pb-4 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-extrabold uppercase">KARTU DETAIL INVENTARIS ASET IT RUMAH SAKIT</h1>
                        <p class="text-xs text-slate-600">Sistem Informasi Manajemen Aset Teknologi Informasi (SIM-ASET IT RSUD)</p>
                    </div>
                    <div class="text-right font-mono text-sm font-bold">
                        <div>Kode: {{ asset.asset_code }}</div>
                        <div class="text-xs font-normal">Dicetak: {{ new Date().toLocaleDateString('id-ID') }}</div>
                    </div>
                </div>
            </div>

            <!-- Main Hero Card -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Left Column: Photo & QR Code Card -->
                <div class="lg:col-span-4 space-y-5">
                    <!-- Device Photo -->
                    <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden">
                        <div class="h-60 bg-navy-950 flex items-center justify-center relative">
                            <img
                                v-if="asset.photo_url"
                                :src="asset.photo_url"
                                :alt="asset.name"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="text-center space-y-2 text-slate-600">
                                <Monitor class="w-14 h-14 mx-auto text-cyan-500/40" />
                                <p class="text-xs font-mono">{{ asset.category }}</p>
                            </div>
                            <span
                                class="absolute top-3 right-3 px-3 py-1 rounded-full text-xs font-bold border backdrop-blur-md"
                                :class="conditionBadgeClass(asset.condition)"
                            >
                                Kondisi: {{ asset.condition }}
                            </span>
                        </div>
                        <div class="p-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
                            <span class="text-slate-400">Tahun Perolehan</span>
                            <span class="font-mono font-bold text-white">{{ asset.acquisition_year || '-' }}</span>
                        </div>
                    </div>

                    <!-- Scannable QR Code Card -->
                    <div class="bg-navy-900/90 border border-cyan-500/30 rounded-2xl p-5 flex items-center gap-4">
                        <div class="bg-white p-2.5 rounded-xl shrink-0 shadow-lg">
                            <img v-if="qrDataUrl" :src="qrDataUrl" :alt="asset.asset_code" class="w-24 h-24 object-contain" />
                        </div>
                        <div class="space-y-1.5 min-w-0">
                            <div class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-cyan-400">
                                <QrCode class="w-3.5 h-3.5" />
                                <span>Identitas QR Aset</span>
                            </div>
                            <div class="text-lg font-mono font-black text-white">{{ asset.asset_code }}</div>
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Pindai QR Code ini untuk membuka halaman detail &amp; riwayat pemeliharaan perangkat secara langsung.
                            </p>
                            <button
                                @click="showQrModal = true"
                                class="text-xs font-bold text-cyan-400 hover:underline pt-1 inline-block no-print"
                            >
                                Cetak Stiker 40&times;20 mm &rarr;
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Complete Specifications, Location, Ownership -->
                <div class="lg:col-span-8 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-6 space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-4 border-b border-slate-800">
                        <div>
                            <div class="flex items-center gap-2.5 flex-wrap">
                                <span class="font-mono text-sm font-extrabold px-3 py-1 rounded-lg bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">
                                    {{ asset.asset_code }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-200 text-xs font-semibold">
                                    {{ asset.category }}
                                </span>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border" :class="ownershipBadgeClass(asset.ownership)">
                                    {{ asset.ownership }}
                                </span>
                            </div>
                            <h2 class="text-xl font-extrabold text-white mt-2.5">{{ asset.name }}</h2>
                        </div>
                    </div>

                    <!-- Technical Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
                        <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90">
                            <div class="text-slate-400 mb-1">Merk / Pabrikan</div>
                            <div class="font-bold text-white text-sm">{{ asset.brand || '-' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90">
                            <div class="text-slate-400 mb-1">Model / Tipe</div>
                            <div class="font-bold text-white text-sm">{{ asset.model || '-' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90">
                            <div class="text-slate-400 mb-1">Nomor Seri (Serial Number)</div>
                            <div class="font-mono font-bold text-cyan-300 text-sm">{{ asset.serial_number || '-' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90">
                            <div class="text-slate-400 mb-1">Lokasi Ruangan</div>
                            <div class="font-bold text-white text-sm">{{ asset.room?.name || '-' }}</div>
                            <div class="text-[11px] text-slate-400">{{ asset.room?.building || '' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90">
                            <div class="text-slate-400 mb-1">Penanggung Jawab (PIC)</div>
                            <div class="font-bold text-white text-sm">{{ asset.pic || '-' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90">
                            <div class="text-slate-400 mb-1">IP &amp; MAC Address</div>
                            <div class="font-mono font-bold text-cyan-300 text-sm">{{ asset.ip_address || 'Non-IP' }}</div>
                            <div class="font-mono text-[11px] text-slate-400">MAC: {{ asset.mac_address || '-' }}</div>
                        </div>
                    </div>

                    <!-- Spesifikasi Lengkap -->
                    <div class="p-4 rounded-xl bg-navy-950/90 border border-slate-800/90 space-y-1.5">
                        <div class="text-xs font-bold uppercase tracking-wider text-cyan-400">
                            Spesifikasi Perangkat (Processor, RAM, Storage, dan lainnya)
                        </div>
                        <p class="text-sm text-slate-200 leading-relaxed">
                            {{ asset.specifications || 'Tidak ada rincian spesifikasi khusus yang dicatat.' }}
                        </p>
                    </div>

                    <!-- Informasi Kepemilikan KSO / Pihak Ketiga (Section 9) -->
                    <div
                        v-if="asset.ownership !== 'Milik RSUD' || asset.partner_name"
                        class="p-4 rounded-xl bg-purple-950/25 border border-purple-500/40 space-y-1.5"
                    >
                        <div class="text-xs font-bold uppercase tracking-wider text-purple-300">
                            Informasi Mitra Kepemilikan ({{ asset.ownership }})
                        </div>
                        <div class="text-sm font-bold text-white">
                            Mitra: {{ asset.partner_name || '-' }}
                        </div>
                        <p class="text-xs text-purple-200/80">
                            {{ asset.ownership_notes || 'Tidak ada catatan kontrak tambahan.' }}
                        </p>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div v-if="asset.notes || asset.followup_note" class="p-4 rounded-xl bg-navy-950/90 border border-slate-800/90 space-y-1">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-400">Catatan &amp; Keterangan</div>
                        <p class="text-xs text-slate-300">{{ asset.notes }}</p>
                        <p v-if="asset.followup_note" class="text-xs text-amber-300 font-medium pt-1">
                            Tindak Lanjut: {{ asset.followup_note }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation: Riwayat Pemindahan (Section 4), Pemeliharaan & Perubahan Data -->
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-3 no-print">
                <button
                    @click="activeHistoryTab = 'transfers'"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition border"
                    :class="activeHistoryTab === 'transfers'
                        ? 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40 shadow-lg shadow-cyan-950/40'
                        : 'bg-navy-900 text-slate-400 hover:text-white border-slate-800'"
                >
                    <ArrowLeftRight class="w-4 h-4" />
                    <span>Riwayat Pemindahan</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-cyan-500/20 text-cyan-300">
                        {{ asset.transfers?.length || 0 }}
                    </span>
                </button>

                <button
                    @click="activeHistoryTab = 'maintenance'"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition border"
                    :class="activeHistoryTab === 'maintenance'
                        ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 shadow-lg shadow-amber-950/40'
                        : 'bg-navy-900 text-slate-400 hover:text-white border-slate-800'"
                >
                    <Wrench class="w-4 h-4" />
                    <span>Riwayat Pemeliharaan &amp; Perbaikan</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-amber-500/20 text-amber-300">
                        {{ asset.maintenances?.length || 0 }}
                    </span>
                </button>

                <button
                    @click="activeHistoryTab = 'audit'"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition border"
                    :class="activeHistoryTab === 'audit'
                        ? 'bg-purple-500/20 text-purple-300 border-purple-500/40 shadow-lg shadow-purple-950/40'
                        : 'bg-navy-900 text-slate-400 hover:text-white border-slate-800'"
                >
                    <History class="w-4 h-4" />
                    <span>Riwayat Perubahan Data</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-mono bg-purple-500/20 text-purple-300">
                        {{ asset.histories?.length || 0 }}
                    </span>
                </button>
            </div>

            <!-- TAB 1: RIWAYAT PEMINDAHAN ASET / ASSET MOVEMENT HISTORY TIMELINE (Section 4) -->
            <div v-if="activeHistoryTab === 'transfers'" class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-6 space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-800">
                    <div>
                        <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                            <ArrowLeftRight class="w-5 h-5 text-cyan-400" />
                            <span>Asset Movement History &mdash; {{ asset.asset_code }} - {{ asset.name }}</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Riwayat perpindahan lokasi perangkat secara kronologis dari penempatan paling awal hingga lokasi aktif saat ini.
                        </p>
                    </div>
                    <button
                        @click="showTransferModal = true"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 no-print shrink-0"
                    >
                        <ArrowLeftRight class="w-4 h-4" />
                        <span>+ Catat Pemindahan Baru</span>
                    </button>
                </div>

                <!-- Timeline Kronologis (Dari Paling Lama ke Terbaru) -->
                <div class="max-w-3xl mx-auto py-2">
                    <!-- Node 0: Penempatan Awal -->
                    <div class="p-4 rounded-2xl bg-navy-950/90 border border-slate-800 space-y-1.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2 text-xs font-bold text-slate-200">
                                <span class="w-3 h-3 rounded-full bg-slate-400 inline-block"></span>
                                <span>{{ initialPlacementDate }}</span>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                Penempatan Awal
                            </span>
                        </div>
                        <div class="pl-5 space-y-1 text-xs">
                            <div>
                                <span class="text-slate-400">Lokasi:</span>
                                <strong class="text-white ml-1.5">{{ initialRoomName }}</strong>
                            </div>
                            <div>
                                <span class="text-slate-400">PIC:</span>
                                <strong class="text-slate-200 ml-1.5">{{ initialPicName }}</strong>
                            </div>
                            <div class="text-slate-400">
                                Keterangan: <span class="text-slate-300">Penempatan awal inventaris perangkat IT di RSUD Majenang.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chronological Transfers List -->
                    <template v-for="(trf, idx) in asset.transfers" :key="trf.id">
                        <!-- Connector Arrow -->
                        <div class="flex items-center gap-2 py-3 pl-6 text-xs font-bold" :class="trf.status === 'Dibatalkan' ? 'text-rose-400/70' : 'text-cyan-400'">
                            <span>&darr; Pemindahan {{ idx + 1 }}</span>
                            <span class="font-mono text-[11px] text-slate-500">({{ trf.transfer_number }})</span>
                        </div>

                        <!-- Transfer Node -->
                        <div
                            class="p-5 rounded-2xl border transition space-y-3"
                            :class="trf.status === 'Dibatalkan'
                                ? 'bg-rose-950/15 border-rose-500/30 opacity-80'
                                : idx === asset.transfers.length - 1 && trf.status === 'Selesai'
                                    ? 'bg-cyan-950/20 border-cyan-500/50 shadow-lg shadow-cyan-950/30'
                                    : 'bg-navy-950/90 border-slate-800'"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2.5 border-b border-slate-800/80">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <span
                                        class="w-3 h-3 rounded-full inline-block"
                                        :class="trf.status === 'Dibatalkan' ? 'bg-rose-400' : 'bg-cyan-400'"
                                    ></span>
                                    <span class="text-xs font-extrabold text-white">
                                        {{ formatTransferDate(trf.transfer_date) }}
                                    </span>
                                    <button
                                        @click="openTransferDetail(trf)"
                                        class="font-mono text-xs font-bold px-2.5 py-0.5 rounded-lg bg-cyan-500/15 text-cyan-300 border border-cyan-500/30 hover:bg-cyan-500/25 transition"
                                    >
                                        {{ trf.transfer_number }}
                                    </button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="{
                                            'bg-emerald-500/15 text-emerald-300 border-emerald-500/30': trf.status === 'Selesai',
                                            'bg-amber-500/15 text-amber-300 border-amber-500/30': trf.status === 'Menunggu',
                                            'bg-rose-500/15 text-rose-300 border-rose-500/30': trf.status === 'Dibatalkan',
                                        }"
                                    >
                                        {{ trf.status }}
                                    </span>
                                    <button
                                        @click="openTransferDetail(trf)"
                                        class="text-[11px] font-semibold text-cyan-400 hover:underline no-print"
                                    >
                                        Detail / Cetak Bukti &rarr;
                                    </button>
                                </div>
                            </div>

                            <div class="pl-5 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div class="space-y-1">
                                    <div>
                                        <span class="text-slate-400">Dari:</span>
                                        <strong class="text-rose-300 ml-1.5">{{ trf.from_room?.name || '-' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Ke:</span>
                                        <strong class="text-emerald-300 ml-1.5">{{ trf.to_room?.name || '-' }}</strong>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div>
                                        <span class="text-slate-400">PIC:</span>
                                        <span class="text-slate-300 ml-1.5">{{ trf.previous_pic || '-' }}</span>
                                        <span class="text-cyan-400 mx-1.5">&rarr;</span>
                                        <strong class="text-white">{{ trf.new_pic || '-' }}</strong>
                                    </div>
                                    <div>
                                        <span class="text-slate-400">Petugas Pencatat:</span>
                                        <strong class="text-slate-200 ml-1.5">{{ trf.creator?.name || 'Petugas IT' }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="pl-5 text-xs space-y-1 pt-1">
                                <div>
                                    <span class="text-slate-400">Alasan:</span>
                                    <strong class="text-amber-300 ml-1.5">{{ trf.transfer_reason }}</strong>
                                </div>
                                <div v-if="trf.description" class="text-slate-300">
                                    <span class="text-slate-400">Keterangan:</span> {{ trf.description }}
                                </div>
                                <div v-if="trf.status === 'Dibatalkan' && trf.cancellation_reason" class="text-rose-300 font-medium pt-1">
                                    Alasan Pembatalan: {{ trf.cancellation_reason }}
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Active Current Location Footer Banner -->
                    <div class="mt-5 p-4 rounded-2xl bg-gradient-to-r from-emerald-950/50 via-navy-950 to-cyan-950/40 border border-emerald-500/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center text-emerald-300 font-bold text-xs shrink-0">
                                AKTIF
                            </div>
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-400">
                                    Lokasi Aktif Aset Saat Ini
                                </div>
                                <div class="text-sm font-extrabold text-white">
                                    {{ asset.room?.name || '-' }}
                                    <span class="text-xs font-normal text-slate-400">({{ asset.room?.building || '-' }})</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-xs text-slate-300 sm:text-right">
                            <div class="text-slate-400 text-[11px]">Penanggung Jawab Aktif</div>
                            <div class="font-bold text-cyan-300">{{ asset.pic || '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2 & 3: Riwayat Pemeliharaan & Riwayat Perubahan Data -->
            <div v-if="activeHistoryTab !== 'transfers'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Riwayat Perbaikan dan Pemeliharaan (Section 5 & 6) -->
                <div class="lg:col-span-7 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-white flex items-center gap-2">
                                <Wrench class="w-4 h-4 text-amber-400" />
                                Riwayat Perbaikan &amp; Pemeliharaan
                            </h3>
                            <p class="text-xs text-slate-400">Catatan historis servis, pergantian sparepart &amp; upgrade pada perangkat ini</p>
                        </div>
                        <button
                            @click="showMaintenanceModal = true"
                            class="px-3 py-1.5 rounded-xl bg-amber-500/15 hover:bg-amber-500/25 border border-amber-500/30 text-amber-300 text-xs font-semibold no-print"
                        >
                            + Tambah Riwayat
                        </button>
                    </div>

                    <div v-if="asset.maintenances?.length" class="space-y-3">
                        <div
                            v-for="mnt in asset.maintenances"
                            :key="mnt.id"
                            class="p-4 rounded-xl bg-navy-950/90 border border-slate-800 space-y-2"
                        >
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-xs font-bold text-amber-300">{{ mnt.ticket_number }}</span>
                                    <span class="text-xs font-bold text-white">&bull; {{ mnt.job_type }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-mono text-slate-400">{{ mnt.maintenance_date }}</span>
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                        :class="{
                                            'bg-rose-500/15 text-rose-300 border-rose-500/30': mnt.status === 'Menunggu',
                                            'bg-amber-500/15 text-amber-300 border-amber-500/30': mnt.status === 'Sedang Dikerjakan',
                                            'bg-emerald-500/15 text-emerald-300 border-emerald-500/30': mnt.status === 'Selesai',
                                        }"
                                    >
                                        {{ mnt.status }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-xs text-slate-300">
                                <strong class="text-slate-400">Keluhan/Kerusakan:</strong> {{ mnt.complaint }}
                            </div>
                            <div v-if="mnt.action_taken" class="text-xs text-emerald-300/90">
                                <strong class="text-slate-400">Tindakan:</strong> {{ mnt.action_taken }}
                            </div>
                            <div class="pt-2 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                                <span>Teknisi: <strong class="text-slate-200">{{ mnt.technician }}</strong></span>
                                <span v-if="mnt.cost > 0" class="font-mono text-cyan-300">Biaya: Rp {{ Number(mnt.cost).toLocaleString('id-ID') }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-10 text-center text-xs text-slate-400">
                        Belum ada riwayat perbaikan atau pemeliharaan pada perangkat ini.
                    </div>
                </div>

                <!-- Riwayat Perubahan Data Aset (Audit Trail - Section 5) -->
                <div class="lg:col-span-5 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-6 space-y-4">
                    <div>
                        <h3 class="text-base font-bold text-white flex items-center gap-2">
                            <History class="w-4 h-4 text-cyan-400" />
                            Riwayat Perubahan Data
                        </h3>
                        <p class="text-xs text-slate-400">Log otomatis setiap perubahan lokasi, kondisi &amp; pembaruan spesifikasi</p>
                    </div>

                    <div v-if="asset.histories?.length" class="space-y-3 relative before:absolute before:left-3 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-800">
                        <div
                            v-for="hist in asset.histories"
                            :key="hist.id"
                            class="pl-8 relative"
                        >
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 border-2 border-navy-900 absolute left-[7px] top-1.5"></span>
                            <div class="p-3.5 rounded-xl bg-navy-950/90 border border-slate-800/90 space-y-1">
                                <div class="flex items-center justify-between text-[11px] text-slate-400">
                                    <span class="font-semibold text-cyan-300 uppercase">{{ hist.action }}</span>
                                    <span class="font-mono">{{ formatDateTime(hist.created_at) }}</span>
                                </div>
                                <p class="text-xs text-slate-200 leading-relaxed">{{ hist.description }}</p>
                                <div v-if="hist.user" class="text-[10px] text-slate-500">
                                    Oleh: {{ hist.user.name }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-10 text-center text-xs text-slate-400">
                        Belum ada log perubahan data.
                    </div>
                </div>
            </div>
        </template>

        <!-- Edit Modal -->
        <AssetFormModal
            v-if="asset"
            :show="showEditModal"
            :asset="asset"
            :rooms="rooms"
            @close="showEditModal = false"
            @saved="fetchDetail"
        />

        <!-- QR Label Modal -->
        <QrLabelModal
            v-if="asset"
            :show="showQrModal"
            :assets="[asset]"
            @close="showQrModal = false"
        />

        <!-- Asset Transfer Form Modal (Preselected with this asset) -->
        <AssetTransferFormModal
            v-if="asset"
            :show="showTransferModal"
            :rooms="rooms"
            :preselectedAsset="asset"
            @close="showTransferModal = false"
            @saved="fetchDetail"
        />

        <!-- Asset Transfer Detail & Print Modal -->
        <AssetTransferDetailModal
            :show="showTransferDetailModal"
            :transfer="selectedTransfer"
            @close="showTransferDetailModal = false"
            @updated="fetchDetail"
        />

        <!-- Quick Add Maintenance Modal for this Asset -->
        <div v-if="showMaintenanceModal && asset" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm no-print">
            <div class="bg-navy-900 border border-slate-700 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-white">Catat Pemeliharaan: {{ asset.asset_code }}</h3>
                    <button @click="showMaintenanceModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitMaintenance" class="space-y-3.5 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Tanggal Pemeliharaan</label>
                            <input v-model="mntForm.maintenance_date" type="date" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Jenis Pekerjaan</label>
                            <select v-model="mntForm.job_type" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
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
                        <label class="block text-slate-300 font-semibold mb-1">Keluhan atau Kerusakan</label>
                        <textarea v-model="mntForm.complaint" rows="2" required placeholder="Jelaskan keluhan atau kerusakan..." class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Tindakan yang Dilakukan</label>
                        <textarea v-model="mntForm.action_taken" rows="2" placeholder="Langkah perbaikan yang dikerjakan..." class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Teknisi / Petugas</label>
                            <input v-model="mntForm.technician" type="text" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Biaya (Rp - Opsional)</label>
                            <input v-model.number="mntForm.cost" type="number" min="0" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Status Pekerjaan</label>
                            <select v-model="mntForm.status" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                <option value="Menunggu">Menunggu</option>
                                <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Kondisi Aset Setelahnya</label>
                            <select v-model="mntForm.condition_after" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                <option value="Baik">Baik</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Rusak">Rusak</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-3">
                        <button type="button" @click="showMaintenanceModal = false" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-500 text-slate-950 font-bold">Simpan Pemeliharaan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import QRCode from 'qrcode';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import AssetFormModal from '@/components/AssetFormModal.vue';
import QrLabelModal from '@/components/QrLabelModal.vue';
import AssetTransferFormModal from '@/components/AssetTransferFormModal.vue';
import AssetTransferDetailModal from '@/components/AssetTransferDetailModal.vue';
import {
    ArrowLeft,
    ArrowLeftRight,
    QrCode,
    Printer,
    Wrench,
    Edit3,
    Monitor,
    History,
    X,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const loading = ref(false);
const asset = ref(null);
const rooms = ref([]);
const qrDataUrl = ref('');

const activeHistoryTab = ref('transfers');
const showEditModal = ref(false);
const showQrModal = ref(false);
const showMaintenanceModal = ref(false);
const showTransferModal = ref(false);
const showTransferDetailModal = ref(false);
const selectedTransfer = ref(null);

const initialRoomName = computed(() => {
    if (asset.value?.transfers?.length) {
        return asset.value.transfers[0].from_room?.name || asset.value.room?.name || '-';
    }
    return asset.value?.room?.name || '-';
});

const initialPicName = computed(() => {
    if (asset.value?.transfers?.length) {
        return asset.value.transfers[0].previous_pic || asset.value.pic || '-';
    }
    return asset.value?.pic || '-';
});

const initialPlacementDate = computed(() => {
    if (asset.value?.acquisition_year) {
        return `Tahun Perolehan ${asset.value.acquisition_year}`;
    }
    return 'Penempatan Awal';
});

function formatTransferDate(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
}

async function openTransferDetail(trf) {
    try {
        const { data } = await api.get(`/asset-transfers/${trf.id}`);
        selectedTransfer.value = data.transfer;
    } catch {
        selectedTransfer.value = trf;
    }
    showTransferDetailModal.value = true;
}

const mntForm = reactive({
    maintenance_date: new Date().toISOString().slice(0, 10),
    job_type: 'Perbaikan Hardware',
    complaint: '',
    action_taken: '',
    technician: auth.user?.name || 'Petugas IT',
    cost: 0,
    status: 'Selesai',
    condition_after: 'Baik',
});

async function fetchDetail() {
    loading.value = true;
    try {
        const { data } = await api.get(`/assets/${encodeURIComponent(route.params.id)}`);
        asset.value = data.asset;
        if (asset.value?.asset_code) {
            const url = `${window.location.origin}/scan/${encodeURIComponent(asset.value.asset_code)}`;
            qrDataUrl.value = await QRCode.toDataURL(url, { width: 240, margin: 1 });
        }
    } catch {
        asset.value = null;
    } finally {
        loading.value = false;
    }
}

async function fetchRooms() {
    const { data } = await api.get('/rooms');
    rooms.value = data.data || [];
}

function printAssetDetail() {
    window.print();
}

async function submitMaintenance() {
    if (!asset.value) return;
    try {
        const { data } = await api.post('/maintenances', {
            ...mntForm,
            asset_id: asset.value.id,
            sync_asset_condition: true,
        });
        auth.notify(data.message, 'success');
        showMaintenanceModal.value = false;
        mntForm.complaint = '';
        mntForm.action_taken = '';
        mntForm.cost = 0;
        await fetchDetail();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyimpan catatan pemeliharaan.', 'error');
    }
}

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
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
    () => route.params.id,
    () => fetchDetail()
);

onMounted(() => {
    fetchRooms();
    fetchDetail();
});
</script>

