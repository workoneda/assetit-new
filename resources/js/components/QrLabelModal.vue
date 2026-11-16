<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
        <div class="bg-navy-900 border border-slate-700/80 rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-800 flex items-center justify-between bg-navy-950/60">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                        <QrCode class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Cetak Label & Kode QR Aset IT</h3>
                        <p class="text-xs text-slate-400">
                            Standar stiker thermal 40 &times; 20 mm &bull; QR Code mengarah langsung ke Detail Aset
                        </p>
                    </div>
                </div>
                <button @click="$emit('close')" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Configuration Bar -->
            <div class="px-6 py-3.5 bg-navy-950/40 border-b border-slate-800/80 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Ukuran Format Label</label>
                    <select
                        v-model="labelSize"
                        class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                    >
                        <option value="40x20">40 &times; 20 mm (Thermal Standar)</option>
                        <option value="50x30">50 &times; 30 mm (Thermal Medium)</option>
                        <option value="70x35">70 &times; 35 mm (Label Inventaris Besar)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Jumlah Salinan per Aset</label>
                    <input
                        v-model.number="copies"
                        type="number"
                        min="1"
                        max="20"
                        class="w-full bg-navy-900 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-cyan-500"
                    />
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1">Tampilkan Informasi Tambahan</label>
                    <div class="flex items-center gap-3 pt-1.5">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="showRoom" class="rounded bg-navy-900 border-slate-700 text-cyan-500" />
                            <span>Ruangan</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="showHospitalName" class="rounded bg-navy-900 border-slate-700 text-cyan-500" />
                            <span>Header RSUD</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Preview Area -->
            <div class="p-6 overflow-y-auto flex-1 bg-slate-950/60">
                <div class="text-xs text-slate-400 mb-4 flex items-center justify-between">
                    <span>Pratinjau Label ({{ expandedItems.length }} stiker siap cetak):</span>
                    <span class="font-mono text-[11px] text-cyan-400">Target URL Scan: {{ originUrl }}/scan/[KODE]</span>
                </div>

                <div class="flex flex-wrap gap-4 justify-center sm:justify-start">
                    <div
                        v-for="(item, idx) in expandedItems"
                        :key="`${item.id}-${idx}`"
                        class="bg-white text-slate-950 rounded-lg shadow-xl border-2 border-slate-300 p-2 flex items-center gap-2.5 overflow-hidden"
                        :style="previewDimensionStyle"
                    >
                        <img
                            v-if="qrDataUrls[item.asset_code]"
                            :src="qrDataUrls[item.asset_code]"
                            :alt="item.asset_code"
                            class="shrink-0 object-contain"
                            :style="qrImageStyle"
                        />
                        <div class="min-w-0 flex-1 leading-tight">
                            <div
                                v-if="showHospitalName"
                                class="font-extrabold uppercase tracking-wider text-slate-700 border-b border-slate-300 pb-0.5 mb-0.5 truncate"
                                :style="{ fontSize: labelSize === '40x20' ? '7px' : '9px' }"
                            >
                                INVENTARIS IT RSUD
                            </div>
                            <div
                                class="font-mono font-black text-slate-950 tracking-tight"
                                :style="{ fontSize: labelSize === '40x20' ? '11px' : labelSize === '50x30' ? '13px' : '15px' }"
                            >
                                {{ item.asset_code }}
                            </div>
                            <div
                                class="font-bold text-slate-800 line-clamp-2"
                                :style="{ fontSize: labelSize === '40x20' ? '8px' : '10px' }"
                            >
                                {{ item.name }}
                            </div>
                            <div
                                v-if="showRoom && item.room?.name"
                                class="text-slate-600 font-medium truncate mt-0.5"
                                :style="{ fontSize: labelSize === '40x20' ? '7px' : '9px' }"
                            >
                                {{ item.room.name }} &bull; {{ item.ownership }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="px-6 py-4 border-t border-slate-800 bg-navy-950/80 flex flex-wrap items-center justify-between gap-3">
                <div class="text-xs text-slate-400">
                    Mendukung printer thermal label (40&times;20 mm) maupun printer kertas stiker A4.
                </div>
                <div class="flex items-center gap-2.5">
                    <button
                        @click="$emit('close')"
                        class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300 hover:text-white hover:bg-slate-800 text-xs font-semibold transition"
                    >
                        Tutup
                    </button>
                    <button
                        @click="printLabels"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak Label Sekarang ({{ expandedItems.length }})</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import QRCode from 'qrcode';
import { QrCode, Printer, X } from 'lucide-vue-next';

const props = defineProps({
    show: { type: Boolean, default: false },
    assets: { type: Array, default: () => [] },
});

defineEmits(['close']);

const labelSize = ref('40x20');
const copies = ref(1);
const showRoom = ref(true);
const showHospitalName = ref(true);
const qrDataUrls = ref({});

const originUrl = computed(() => window.location.origin);

const expandedItems = computed(() => {
    const count = Math.max(1, Math.min(20, Number(copies.value) || 1));
    const result = [];
    for (const item of props.assets) {
        for (let i = 0; i < count; i++) {
            result.push(item);
        }
    }
    return result;
});

const previewDimensionStyle = computed(() => {
    if (labelSize.value === '40x20') {
        return { width: '210px', height: '105px' }; // Scaled 40x20mm (2:1 ratio) for screen preview
    }
    if (labelSize.value === '50x30') {
        return { width: '250px', height: '150px' };
    }
    return { width: '320px', height: '160px' };
});

const qrImageStyle = computed(() => {
    if (labelSize.value === '40x20') {
        return { width: '78px', height: '78px' };
    }
    if (labelSize.value === '50x30') {
        return { width: '105px', height: '105px' };
    }
    return { width: '120px', height: '120px' };
});

async function generateAllQrCodes() {
    const map = {};
    for (const item of props.assets) {
        if (!item?.asset_code) continue;
        const targetUrl = `${window.location.origin}/scan/${encodeURIComponent(item.asset_code)}`;
        try {
            map[item.asset_code] = await QRCode.toDataURL(targetUrl, {
                width: 256,
                margin: 1,
                color: {
                    dark: '#000000',
                    light: '#ffffff',
                },
            });
        } catch (e) {
            console.error(e);
        }
    }
    qrDataUrls.value = map;
}

watch(
    () => [props.show, props.assets],
    ([isOpen]) => {
        if (isOpen) {
            generateAllQrCodes();
        }
    },
    { immediate: true, deep: true }
);

function printLabels() {
    const [wMm, hMm] = labelSize.value.split('x').map(Number);
    const qrMm = labelSize.value === '40x20' ? 15 : labelSize.value === '50x30' ? 22 : 26;
    const codeFontPt = labelSize.value === '40x20' ? 8.5 : labelSize.value === '50x30' ? 10 : 12;
    const nameFontPt = labelSize.value === '40x20' ? 6 : labelSize.value === '50x30' ? 7.5 : 8.5;
    const metaFontPt = labelSize.value === '40x20' ? 5.2 : 6.5;

    const labelsHtml = expandedItems.value
        .map((item) => {
            const qrSrc = qrDataUrls.value[item.asset_code] || '';
            const roomText = showRoom.value && item.room?.name ? `<div class="meta">${item.room.name}</div>` : '';
            const headerText = showHospitalName.value ? `<div class="hdr">INVENTARIS IT RSUD</div>` : '';
            return `
            <div class="label-box">
                <img class="qr" src="${qrSrc}" alt="${item.asset_code}" />
                <div class="info">
                    ${headerText}
                    <div class="code">${item.asset_code}</div>
                    <div class="name">${item.name}</div>
                    ${roomText}
                </div>
            </div>`;
        })
        .join('');

    const printWin = window.open('', '_blank', 'width=800,height=600');
    if (!printWin) return;

    printWin.document.write(`<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Cetak Label QR Aset (${labelSize.value} mm)</title>
    <style>
        @page {
            size: ${wMm}mm ${hMm}mm;
            margin: 0;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            color: #000;
            display: flex;
            flex-wrap: wrap;
            gap: 2mm;
            padding: 1mm;
        }
        .label-box {
            width: ${wMm}mm;
            height: ${hMm}mm;
            border: 0.3mm solid #222;
            border-radius: 1mm;
            padding: 1.2mm;
            display: flex;
            align-items: center;
            gap: 1.5mm;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .qr {
            width: ${qrMm}mm;
            height: ${qrMm}mm;
            object-fit: contain;
            flex-shrink: 0;
        }
        .info {
            flex: 1;
            min-width: 0;
            line-height: 1.15;
        }
        .hdr {
            font-size: ${metaFontPt}pt;
            font-weight: 800;
            border-bottom: 0.2mm solid #444;
            padding-bottom: 0.4mm;
            margin-bottom: 0.5mm;
            letter-spacing: 0.2px;
        }
        .code {
            font-family: 'Courier New', monospace;
            font-size: ${codeFontPt}pt;
            font-weight: 900;
            letter-spacing: -0.2px;
        }
        .name {
            font-size: ${nameFontPt}pt;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .meta {
            font-size: ${metaFontPt}pt;
            color: #333;
            margin-top: 0.4mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    ${labelsHtml}
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 250);
        };
    <\/script>
</body>
</html>`);
    printWin.document.close();
}
</script>
