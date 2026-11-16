<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetTransfer;
use App\Models\Maintenance;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // 8 Kartu Statistik Utama (Section 3)
        $totalAssets = Asset::count();
        $totalPcLaptop = Asset::whereIn('category', ['Komputer / PC', 'Laptop'])->count();
        $totalPrinter = Asset::where('category', 'Printer')->count();
        $totalNetwork = Asset::whereIn('category', ['Switch', 'Router', 'Server', 'Access Point'])->count();
        $totalRsud = Asset::where('ownership', 'Milik RSUD')->count();
        $totalKsoPartner = Asset::whereIn('ownership', ['KSO', 'Milik Pihak Ketiga'])->count();
        $totalDamaged = Asset::where('condition', 'Rusak')->count();
        $totalMaintenance = Asset::where('condition', 'Maintenance')->count();

        // Grafik jumlah aset berdasarkan kategori
        $categoryRaw = Asset::select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        $byCategory = [];
        foreach (Asset::CATEGORIES as $cat) {
            $byCategory[] = [
                'category' => $cat,
                'total' => (int) ($categoryRaw[$cat] ?? 0),
            ];
        }

        // Grafik kondisi aset (Baik, Rusak, Maintenance)
        $conditionRaw = Asset::select('condition', DB::raw('COUNT(*) as total'))
            ->groupBy('condition')
            ->pluck('total', 'condition')
            ->toArray();

        $byCondition = [
            'Baik' => (int) ($conditionRaw['Baik'] ?? 0),
            'Rusak' => (int) ($conditionRaw['Rusak'] ?? 0),
            'Maintenance' => (int) ($conditionRaw['Maintenance'] ?? 0),
        ];

        // Grafik kepemilikan aset (Milik RSUD, KSO, Milik Pihak Ketiga)
        $ownershipRaw = Asset::select('ownership', DB::raw('COUNT(*) as total'))
            ->groupBy('ownership')
            ->pluck('total', 'ownership')
            ->toArray();

        $byOwnership = [
            'Milik RSUD' => (int) ($ownershipRaw['Milik RSUD'] ?? 0),
            'KSO' => (int) ($ownershipRaw['KSO'] ?? 0),
            'Milik Pihak Ketiga' => (int) ($ownershipRaw['Milik Pihak Ketiga'] ?? 0),
        ];

        // Ringkasan Mitra KSO & Pihak Ketiga (Section 9)
        $partnerBreakdown = Asset::whereIn('ownership', ['KSO', 'Milik Pihak Ketiga'])
            ->select(
                'ownership',
                DB::raw(" COALESCE(NULLIF(partner_name, ''), 'Mitra Belum Ditentukan') as partner_name "),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('ownership', 'partner_name')
            ->orderByDesc('total')
            ->get();

        // Distribusi per Ruangan
        $byRoom = Room::withCount('assets')
            ->orderByDesc('assets_count')
            ->take(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'building' => $r->building,
                'total' => $r->assets_count,
            ]);

        // Daftar aset terbaru
        $latestAssets = Asset::with('room:id,code,name,building')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        // Daftar aset yang sedang dalam perbaikan (Maintenance Radar aktif)
        $activeMaintenances = Maintenance::with(['asset.room'])
            ->whereIn('status', ['Menunggu', 'Sedang Dikerjakan'])
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->take(6)
            ->get();

        // Informasi aset yang perlu ditindaklanjuti (Rusak / Maintenance / needs_followup)
        $followupAssets = Asset::with('room:id,code,name,building')
            ->where(function ($q) {
                $q->whereIn('condition', ['Rusak', 'Maintenance'])
                  ->orWhere('needs_followup', true);
            })
            ->orderByRaw("FIELD(`condition`, 'Rusak', 'Maintenance', 'Baik')")
            ->orderByDesc('updated_at')
            ->take(8)
            ->get();

        // Statistik & Riwayat Pemindahan Aset (Integrasi Modul Asset Transfer - Section 11)
        $totalTransfers = AssetTransfer::where('status', 'Selesai')->count();
        $monthTransfers = AssetTransfer::where('status', 'Selesai')
            ->whereYear('transfer_date', now()->year)
            ->whereMonth('transfer_date', now()->month)
            ->count();
        $pendingTransfers = AssetTransfer::where('status', 'Menunggu')->count();
        $recentTransfers = AssetTransfer::with(['asset:id,asset_code,name', 'fromRoom:id,name', 'toRoom:id,name', 'creator:id,name'])
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => [
                'total_assets' => $totalAssets,
                'total_pc_laptop' => $totalPcLaptop,
                'total_printer' => $totalPrinter,
                'total_network' => $totalNetwork,
                'total_rsud' => $totalRsud,
                'total_kso_partner' => $totalKsoPartner,
                'total_damaged' => $totalDamaged,
                'total_maintenance' => $totalMaintenance,
                'total_good' => $byCondition['Baik'],
                'total_transfers' => $totalTransfers,
                'month_transfers' => $monthTransfers,
                'pending_transfers' => $pendingTransfers,
            ],
            'charts' => [
                'by_category' => $byCategory,
                'by_condition' => $byCondition,
                'by_ownership' => $byOwnership,
                'by_room' => $byRoom,
                'partner_breakdown' => $partnerBreakdown,
            ],
            'lists' => [
                'latest_assets' => $latestAssets,
                'active_maintenances' => $activeMaintenances,
                'followup_assets' => $followupAssets,
                'recent_transfers' => $recentTransfers,
            ],
        ]);
    }
}
