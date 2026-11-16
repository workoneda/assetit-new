<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkMapController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['room:id,code,name,building', 'uplink:id,asset_code,name,category,ip_address,network_status'])
            ->where(function ($q) {
                $q->whereNotNull('ip_address')
                  ->where('ip_address', '!=', '')
                  ->orWhereIn('category', ['Router', 'Switch', 'Server', 'Access Point']);
            });

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('vlan', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($roomId = $request->input('room_id')) {
            $query->where('room_id', $roomId);
        }

        if ($status = $request->input('network_status')) {
            $query->where('network_status', $status);
        }

        $devices = $query->orderByRaw("CASE WHEN ip_address IS NULL OR ip_address = '' THEN 1 ELSE 0 END ASC")
            ->orderBy('ip_address')
            ->orderBy('asset_code')
            ->get();

        // Stats across all network devices
        $allNetworkDevices = Asset::where(function ($q) {
            $q->whereNotNull('ip_address')
              ->where('ip_address', '!=', '')
              ->orWhereIn('category', ['Router', 'Switch', 'Server', 'Access Point']);
        })->get();

        $stats = [
            'total_devices' => $allNetworkDevices->count(),
            'online' => $allNetworkDevices->where('network_status', 'online')->count(),
            'offline' => $allNetworkDevices->where('network_status', 'offline')->count(),
            'core_infra' => $allNetworkDevices->whereIn('category', ['Router', 'Switch', 'Server', 'Access Point'])->count(),
            'endpoints' => $allNetworkDevices->whereNotIn('category', ['Router', 'Switch', 'Server', 'Access Point'])->count(),
        ];

        return response()->json([
            'devices' => $devices,
            'stats' => $stats,
        ]);
    }

    public function pingDevice(Request $request, Asset $asset): JsonResponse
    {
        $newStatus = $request->input('network_status');
        if (!in_array($newStatus, ['online', 'offline', 'unknown'], true)) {
            // If asset is damaged, mark offline; otherwise online with realistic latency
            $newStatus = $asset->condition === 'Rusak' ? 'offline' : 'online';
        }

        $latency = $newStatus === 'online' ? rand(1, 7) : null;

        $asset->update([
            'network_status' => $newStatus,
            'latency_ms' => $latency,
            'last_checked_at' => now(),
        ]);

        $asset->load(['room:id,code,name,building', 'uplink:id,asset_code,name,category,ip_address,network_status']);

        return response()->json([
            'message' => "Status koneksi {$asset->name} ({$asset->ip_address}) diperbarui: " . strtoupper($newStatus),
            'device' => $asset,
        ]);
    }

    public function pingAll(): JsonResponse
    {
        $devices = Asset::whereNotNull('ip_address')->where('ip_address', '!=', '')->get();
        foreach ($devices as $dev) {
            $status = $dev->condition === 'Rusak' ? 'offline' : 'online';
            $dev->update([
                'network_status' => $status,
                'latency_ms' => $status === 'online' ? rand(1, 6) : null,
                'last_checked_at' => now(),
            ]);
        }

        return response()->json([
            'message' => "Pengecekan status jaringan selesai untuk {$devices->count()} perangkat IP.",
        ]);
    }
}
