<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['room:id,code,name,building'])
            ->withCount('maintenances');

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($roomId = $request->input('room_id')) {
            $query->where('room_id', $roomId);
        }

        if ($ownership = $request->input('ownership')) {
            $query->where('ownership', $ownership);
        }

        if ($condition = $request->input('condition')) {
            $query->where('condition', $condition);
        }

        if ($year = $request->input('acquisition_year')) {
            $query->where('acquisition_year', $year);
        }

        $assets = $query->orderBy('asset_code')->get();

        // Rekapitulasi jumlah aset per kategori
        $rekapKategori = [];
        foreach (Asset::CATEGORIES as $cat) {
            $subset = $assets->where('category', $cat);
            $rekapKategori[] = [
                'category' => $cat,
                'total' => $subset->count(),
                'baik' => $subset->where('condition', 'Baik')->count(),
                'rusak' => $subset->where('condition', 'Rusak')->count(),
                'maintenance' => $subset->where('condition', 'Maintenance')->count(),
                'milik_rsud' => $subset->where('ownership', 'Milik RSUD')->count(),
                'kso_mitra' => $subset->whereIn('ownership', ['KSO', 'Milik Pihak Ketiga'])->count(),
            ];
        }

        // Rekap kondisi aset
        $rekapKondisi = [
            'Baik' => $assets->where('condition', 'Baik')->count(),
            'Rusak' => $assets->where('condition', 'Rusak')->count(),
            'Maintenance' => $assets->where('condition', 'Maintenance')->count(),
            'Total' => $assets->count(),
        ];

        // Rekap kepemilikan aset
        $rekapKepemilikan = [];
        foreach (Asset::OWNERSHIPS as $own) {
            $subset = $assets->where('ownership', $own);
            $rekapKepemilikan[] = [
                'ownership' => $own,
                'total' => $subset->count(),
                'baik' => $subset->where('condition', 'Baik')->count(),
                'rusak' => $subset->where('condition', 'Rusak')->count(),
                'maintenance' => $subset->where('condition', 'Maintenance')->count(),
            ];
        }

        // Rekap aset per ruangan
        $rooms = Room::orderBy('code')->get();
        $rekapRuangan = [];
        foreach ($rooms as $r) {
            $subset = $assets->where('room_id', $r->id);
            if ($request->filled('room_id') && (int) $request->input('room_id') !== (int) $r->id) {
                continue;
            }
            $rekapRuangan[] = [
                'room_id' => $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'building' => $r->building,
                'pic_name' => $r->pic_name,
                'total' => $subset->count(),
                'baik' => $subset->where('condition', 'Baik')->count(),
                'rusak' => $subset->where('condition', 'Rusak')->count(),
                'maintenance' => $subset->where('condition', 'Maintenance')->count(),
            ];
        }

        // Available years for filter dropdown
        $availableYears = Asset::whereNotNull('acquisition_year')
            ->distinct()
            ->orderByDesc('acquisition_year')
            ->pluck('acquisition_year');

        return response()->json([
            'assets' => $assets,
            'rekap_kategori' => $rekapKategori,
            'rekap_kondisi' => $rekapKondisi,
            'rekap_kepemilikan' => $rekapKepemilikan,
            'rekap_ruangan' => $rekapRuangan,
            'available_years' => $availableYears,
        ]);
    }
}
