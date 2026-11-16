<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RecycleBinController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Asset::onlyTrashed()->with(['room:id,code,name,building', 'updater:id,name,role']);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $items = $query->orderByDesc('deleted_at')->get();

        return response()->json([
            'data' => $items,
            'total' => $items->count(),
        ]);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $asset = Asset::onlyTrashed()->findOrFail($id);
        $asset->restore();

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => $request->user()?->id,
            'action' => 'restored',
            'description' => "Aset {$asset->asset_code} ({$asset->name}) dipulihkan dari Recycle Bin oleh " . ($request->user()?->name ?? 'Petugas') . ".",
            'changes' => ['restored_at' => now()->toDateTimeString()],
        ]);

        return response()->json([
            'message' => "Aset {$asset->asset_code} ({$asset->name}) berhasil dipulihkan ke inventaris aktif.",
            'asset' => $asset->load('room'),
        ]);
    }

    public function forceDestroy(int $id): JsonResponse
    {
        $asset = Asset::onlyTrashed()->findOrFail($id);
        $code = $asset->asset_code;
        $name = $asset->name;

        if ($asset->photo_path) {
            Storage::disk('public')->delete($asset->photo_path);
        }

        $asset->forceDelete();

        return response()->json([
            'message' => "Aset {$code} ({$name}) telah dihapus secara permanen dari database.",
        ]);
    }

    public function restoreAll(Request $request): JsonResponse
    {
        $trashed = Asset::onlyTrashed()->get();
        $count = $trashed->count();

        foreach ($trashed as $asset) {
            $asset->restore();
            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user()?->id,
                'action' => 'restored',
                'description' => "Aset {$asset->asset_code} dipulihkan secara massal dari Recycle Bin.",
                'changes' => ['restored_at' => now()->toDateTimeString()],
            ]);
        }

        return response()->json([
            'message' => "Seluruh {$count} aset di Recycle Bin berhasil dipulihkan.",
        ]);
    }

    public function emptyBin(): JsonResponse
    {
        $trashed = Asset::onlyTrashed()->get();
        $count = $trashed->count();

        foreach ($trashed as $asset) {
            if ($asset->photo_path) {
                Storage::disk('public')->delete($asset->photo_path);
            }
            $asset->forceDelete();
        }

        return response()->json([
            'message' => "Recycle Bin berhasil dikosongkan ({$count} aset dihapus permanen).",
        ]);
    }
}
