<template>
    <div v-if="show && transfer" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/75 backdrop-blur-sm no-print">
        <div class="bg-navy-900 border-t sm:border border-slate-700/80 rounded-t-2xl sm:rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[94dvh] sm:max-h-[92vh]">
            <!-- Header -->
            <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-800 flex items-center justify-between bg-navy-950/70">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                        <FileText class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-white font-mono">{{ transfer.transfer_number }}</h3>
                            <span
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border"
                                :class="statusBadgeClass(transfer.status)"
                            >
                                {{ transfer.status }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-400">Detail Transaksi Pemindahan Aset IT Rumah Sakit</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1 space-y-5 text-xs">
                <!-- Cancellation Alert if Cancelled -->
                <div
                    v-if="transfer.status === 'Dibatalkan'"
                    class="p-4 rounded-xl bg-rose-950/40 border border-rose-500/40 space-y-1"
                >
                    <div class="font-bold text-rose-300 flex items-center gap-2">
                        <AlertTriangle class="w-4 h-4" />
                        <span>Transaksi Pemindahan Ini Telah Dibatalkan</span>
                    </div>
                    <p class="text-rose-200/90">
                        <strong>Alasan Pembatalan:</strong> {{ transfer.cancellation_reason || '-' }}
                    </p>
                    <p class="text-[11px] text-slate-400">
                        Dibatalkan oleh: {{ transfer.canceller?.name || 'Administrator' }} pada {{ formatDateTime(transfer.cancelled_at) }}
                    </p>
                </div>

                <!-- 1. Informasi Lengkap Aset -->
                <div class="p-4 rounded-xl bg-navy-950/90 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-cyan-400">
                            Informasi Perangkat Aset IT
                        </span>
                        <router-link
                            v-if="transfer.asset"
                            :to="`/assets/${transfer.asset.id}`"
                            @click="$emit('close')"
                            class="text-cyan-400 hover:underline font-semibold"
                        >
                            Buka Halaman Detail Aset &rarr;
                        </router-link>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Kode Aset</span>
                            <span class="font-mono font-bold text-cyan-300 text-sm">{{ transfer.asset?.asset_code || '-' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-400 block text-[11px]">Nama Perangkat</span>
                            <span class="font-bold text-white text-sm">{{ transfer.asset?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Kategori</span>
                            <span class="font-semibold text-slate-200">{{ transfer.asset?.category || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Merk / Model</span>
                            <span class="text-slate-200">{{ transfer.asset?.brand || '-' }} {{ transfer.asset?.model || '' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Serial Number</span>
                            <span class="font-mono text-slate-200">{{ transfer.asset?.serial_number || '-' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-400 block text-[11px]">Lokasi Aktif Aset Saat Ini</span>
                            <span class="font-bold text-emerald-400">{{ transfer.asset?.room?.name || '-' }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Skema Perpindahan Lokasi & PIC -->
                <div class="grid grid-cols-1 sm:grid-cols-11 gap-3 items-center">
                    <div class="sm:col-span-5 p-4 rounded-xl bg-navy-950 border border-slate-800 space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-amber-400">Lokasi Asal (Sebelumnya)</span>
                        <div class="text-sm font-extrabold text-white">{{ transfer.from_room?.name || '-' }}</div>
                        <div class="text-[11px] text-slate-400">{{ transfer.from_room?.building || '' }}</div>
                        <div class="pt-2 border-t border-slate-800/80 text-slate-300">
                            <span class="text-slate-500">PIC Sebelumnya:</span>
                            <strong class="ml-1">{{ transfer.previous_pic || '-' }}</strong>
                        </div>
                    </div>

                    <div class="sm:col-span-1 flex justify-center">
                        <div class="w-9 h-9 rounded-full bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                            <ArrowRight class="w-4 h-4" />
                        </div>
                    </div>

                    <div class="sm:col-span-5 p-4 rounded-xl bg-navy-950 border border-emerald-500/40 space-y-1.5">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400">Lokasi Tujuan (Baru)</span>
                        <div class="text-sm font-extrabold text-white">{{ transfer.to_room?.name || '-' }}</div>
                        <div class="text-[11px] text-slate-400">{{ transfer.to_room?.building || '' }}</div>
                        <div class="pt-2 border-t border-slate-800/80 text-slate-300">
                            <span class="text-slate-500">PIC Baru:</span>
                            <strong class="ml-1 text-emerald-300">{{ transfer.new_pic || '-' }}</strong>
                        </div>
                    </div>
                </div>

                <!-- 3. Rincian Transaksi, Alasan & Petugas -->
                <div class="p-4 rounded-xl bg-navy-950/90 border border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Tanggal Pemindahan</span>
                        <span class="font-mono font-bold text-white">{{ formatDateTime(transfer.transfer_date) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Alasan Pemindahan</span>
                        <span class="font-bold text-cyan-300">{{ transfer.transfer_reason }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Petugas Pencatat</span>
                        <span class="font-semibold text-white">{{ transfer.creator?.name || 'Administrator IT' }}</span>
                        <span class="block text-[10px] text-slate-500">Dicatat: {{ formatDateTime(transfer.created_at) }}</span>
                    </div>
                    <div class="sm:col-span-3 pt-2 border-t border-slate-800/80">
                        <span class="text-slate-400 block text-[11px] mb-0.5">Keterangan Tambahan</span>
                        <p class="text-slate-200 leading-relaxed">{{ transfer.description || 'Tidak ada catatan tambahan.' }}</p>
                    </div>
                </div>

                <!-- 4. Dokumentasi Pemindahan (Section 6) -->
                <div v-if="transfer.documents?.length" class="p-4 rounded-xl bg-navy-950/90 border border-slate-800 space-y-3">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-purple-400">
                        Dokumentasi Pemindahan Terlampir
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div
                            v-for="doc in transfer.documents"
                            :key="doc.id"
                            class="p-3 rounded-xl bg-navy-900 border border-slate-800 space-y-2"
                        >
                            <div class="text-[11px] font-bold text-slate-300">
                                {{ docTypeLabel(doc.document_type) }}
                            </div>
                            <a
                                :href="doc.file_url"
                                target="_blank"
                                class="block text-cyan-400 hover:underline truncate font-mono text-[11px]"
                            >
                                {{ doc.original_name || 'Lihat File Lampiran' }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 5. Form Pembatalan / Koreksi Pemindahan (Section 7 - Admin Only) -->
                <div
                    v-if="showCancelForm && auth.isAdmin && transfer.status !== 'Dibatalkan'"
                    class="p-4 rounded-xl bg-rose-950/30 border border-rose-500/40 space-y-3"
                >
                    <div class="font-bold text-rose-300">
                        Formulir Pembatalan &amp; Koreksi Pemindahan Aset
                    </div>
                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        Pembatalan akan dicatat pada audit log. Apabila ini adalah pemindahan terakhir dan aset masih berada di
                        <strong>{{ transfer.to_room?.name }}</strong>, maka lokasi aset akan dikembalikan ke
                        <strong>{{ transfer.from_room?.name }}</strong>.
                    </p>
                    <textarea
                        v-model="cancellationReason"
                        rows="2"
                        required
                        placeholder="Wajib diisi: Tuliskan alasan pembatalan atau koreksi pemindahan (minimal 5 karakter)..."
                        class="w-full bg-navy-950 border border-rose-500/40 rounded-xl px-3 py-2 text-xs text-white focus:outline-none"
                    ></textarea>
                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            @click="showCancelForm = false"
                            class="px-3 py-1.5 rounded-lg border border-slate-700 text-slate-300"
                        >
                            Tutup
                        </button>
                        <button
                            type="button"
                            @click="executeCancelTransfer"
                            :disabled="cancelling || cancellationReason.trim().length < 5"
                            class="px-4 py-1.5 rounded-lg bg-rose-500 hover:bg-rose-400 disabled:opacity-40 text-slate-950 font-bold"
                        >
                            {{ cancelling ? 'Memproses...' : 'Konfirmasi Batalkan Pemindahan' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 border-t border-slate-800 bg-navy-950/80 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <button
                        v-if="auth.isAdmin && transfer.status !== 'Dibatalkan' && !showCancelForm"
                        @click="showCancelForm = true"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-500/15 hover:bg-rose-500/25 border border-rose-500/30 text-xs font-semibold text-rose-300 transition"
                    >
                        <XCircle class="w-4 h-4" />
                        <span>Batalkan / Koreksi</span>
                    </button>

                    <button
                        v-if="transfer.status === 'Menunggu'"
                        @click="executeCompleteTransfer"
                        :disabled="completing"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition"
                    >
                        <CheckCircle2 class="w-4 h-4" />
                        <span>{{ completing ? 'Memproses...' : 'Konfirmasi Selesai Pemindahan' }}</span>
                    </button>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:text-white text-xs font-semibold"
                    >
                        Tutup
                    </button>
                    <button
                        @click="printTransferReceipt"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-extrabold text-xs shadow-lg shadow-cyan-500/20 transition"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak Bukti Pemindahan (PDF)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { FileText, X, ArrowRight, Printer, AlertTriangle, XCircle, CheckCircle2 } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    transfer: { type: Object, default: null },
});

const emit = defineEmits(['close', 'updated']);
const auth = useAuthStore();

const showCancelForm = ref(false);
const cancellationReason = ref('');
const cancelling = ref(false);
const completing = ref(false);

watch(
    () => props.show,
    (val) => {
        if (val) {
            showCancelForm.value = false;
            cancellationReason.value = '';
        }
    }
);

function statusBadgeClass(st) {
    if (st === 'Selesai') return 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30';
    if (st === 'Menunggu') return 'bg-amber-500/15 text-amber-300 border-amber-500/30';
    return 'bg-rose-500/15 text-rose-300 border-rose-500/30';
}

function docTypeLabel(type) {
    if (type === 'photo_before') return 'Foto Sebelum Dipindahkan';
    if (type === 'photo_after') return 'Foto Setelah Dipindahkan';
    return 'Dokumen Pendukung';
}

function formatDateTime(dt) {
    if (!dt) return '-';
    return new Date(dt).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

async function executeCompleteTransfer() {
    if (!props.transfer) return;
    completing.value = true;
    try {
        const { data } = await api.post(`/asset-transfers/${props.transfer.id}/complete`);
        auth.notify(data.message, 'success');
        emit('updated', data.transfer);
        emit('close');
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyelesaikan pemindahan.', 'error');
    } finally {
        completing.value = false;
    }
}

async function executeCancelTransfer() {
    if (!props.transfer || cancellationReason.value.trim().length < 5) return;
    cancelling.value = true;
    try {
        const { data } = await api.post(`/asset-transfers/${props.transfer.id}/cancel`, {
            cancellation_reason: cancellationReason.value.trim(),
        });
        auth.notify(data.message, 'success');
        emit('updated', data.transfer);
        emit('close');
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal membatalkan pemindahan.', 'error');
    } finally {
        cancelling.value = false;
    }
}

function printTransferReceipt() {
    const t = props.transfer;
    if (!t) return;

    const win = window.open('', '_blank', 'width=900,height=700');
    if (!win) return;

    win.document.write(`<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title>Bukti Pemindahan Aset - ${t.transfer_number}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            padding: 24px 32px;
            line-height: 1.5;
        }
        .header {
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 12px;
            color: #475569;
            margin: 2px 0 0;
        }
        .doc-title {
            text-align: center;
            margin: 18px 0;
        }
        .doc-title h2 {
            font-size: 16px;
            margin: 0;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doc-title span {
            font-family: monospace;
            font-size: 13px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #94a3b8;
            padding: 8px 10px;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            width: 28%;
            font-weight: bold;
        }
        .signatures {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            text-align: center;
            font-size: 12px;
        }
        .sig-space {
            height: 65px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>RSUD MAJENANG — INSTALASI SIMRS &amp; TEKNOLOGI INFORMASI</h1>
            <p>Sistem Inventarisasi dan Monitoring Aset Teknologi Informasi Rumah Sakit</p>
        </div>
        <div style="text-align:right; font-size:12px;">
            <strong>Status: ${t.status.toUpperCase()}</strong><br/>
            Dicetak: ${new Date().toLocaleDateString('id-ID')}
        </div>
    </div>

    <div class="doc-title">
        <h2>BERITA ACARA PEMINDAHAN / MUTASI ASET IT</h2>
        <span>Nomor Transaksi: ${t.transfer_number}</span>
    </div>

    <table>
        <tr>
            <th>Kode Aset</th>
            <td><strong>${t.asset?.asset_code || '-'}</strong></td>
            <th>Tanggal Pemindahan</th>
            <td>${formatDateTime(t.transfer_date)}</td>
        </tr>
        <tr>
            <th>Nama Perangkat</th>
            <td><strong>${t.asset?.name || '-'}</strong></td>
            <th>Kategori Perangkat</th>
            <td>${t.asset?.category || '-'}</td>
        </tr>
        <tr>
            <th>Merk / Model</th>
            <td>${t.asset?.brand || '-'} ${t.asset?.model || ''}</td>
            <th>Serial Number (SN)</th>
            <td>${t.asset?.serial_number || '-'}</td>
        </tr>
        <tr>
            <th>Lokasi Ruangan Asal</th>
            <td><strong>${t.from_room?.name || '-'}</strong> (${t.from_room?.building || '-'})</td>
            <th>PIC Sebelumnya</th>
            <td>${t.previous_pic || '-'}</td>
        </tr>
        <tr>
            <th>Lokasi Ruangan Tujuan</th>
            <td><strong>${t.to_room?.name || '-'}</strong> (${t.to_room?.building || '-'})</td>
            <th>PIC Baru</th>
            <td><strong>${t.new_pic || '-'}</strong></td>
        </tr>
        <tr>
            <th>Alasan Pemindahan</th>
            <td>${t.transfer_reason}</td>
            <th>Petugas Pencatat</th>
            <td>${t.creator?.name || 'Petugas IT'}</td>
        </tr>
        <tr>
            <th>Keterangan Tambahan</th>
            <td colspan="3">${t.description || '-'}</td>
        </tr>
    </table>

    <div class="signatures">
        <div>
            <div>Menyerahkan (PIC Asal)</div>
            <div><strong>${t.from_room?.name || ''}</strong></div>
            <div class="sig-space"></div>
            <div>( ${t.previous_pic || '....................................'} )</div>
        </div>
        <div>
            <div>Petugas IT / Pencatat</div>
            <div><strong>Unit SIMRS &amp; IT</strong></div>
            <div class="sig-space"></div>
            <div>( ${t.creator?.name || '....................................'} )</div>
        </div>
        <div>
            <div>Menerima (PIC Tujuan)</div>
            <div><strong>${t.to_room?.name || ''}</strong></div>
            <div class="sig-space"></div>
            <div>( ${t.new_pic || '....................................'} )</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() { window.print(); }, 250);
        };
    <\/script>
</body>
</html>`);
    win.document.close();
}
</script>

