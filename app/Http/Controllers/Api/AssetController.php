<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Asset::with(['room:id,code,name,building', 'uplink:id,asset_code,name,ip_address'])
            ->withCount(['maintenances', 'transfers']);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_code', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('mac_address', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('pic', 'like', "%{$search}%");
            });
        }

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

        if ($request->boolean('needs_followup')) {
            $query->where(function ($q) {
                $q->whereIn('condition', ['Rusak', 'Maintenance'])
                  ->orWhere('needs_followup', true);
            });
        }

        $sortBy = $request->input('sort_by', 'asset_code');
        $sortDir = strtolower($request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['asset_code', 'name', 'category', 'condition', 'acquisition_year', 'created_at', 'ip_address'];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'asset_code';
        }

        $query->orderBy($sortBy, $sortDir);

        if ($request->boolean('all')) {
            $items = $query->get();
            return response()->json([
                'data' => $items,
                'meta' => [
                    'total' => $items->count(),
                    'next_code' => Asset::generateNextCode(),
                ],
            ]);
        }

        $perPage = max(6, min(100, (int) $request->input('per_page', 12)));
        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
                'next_code' => Asset::generateNextCode(),
            ],
        ]);
    }

    public function nextCode(): JsonResponse
    {
        return response()->json([
            'next_code' => Asset::generateNextCode(),
        ]);
    }

    public function show(string $idOrCode): JsonResponse
    {
        $asset = Asset::with([
            'room',
            'uplink:id,asset_code,name,category,ip_address,network_status',
            'downlinks:id,asset_code,name,category,ip_address,network_status,uplink_asset_id,room_id',
            'downlinks.room:id,name',
            'histories.user:id,name,role',
            'maintenances.creator:id,name,role',
            'transfers.fromRoom:id,code,name,building',
            'transfers.toRoom:id,code,name,building',
            'transfers.creator:id,name,role',
            'transfers.canceller:id,name,role',
            'transfers.documents',
            'creator:id,name,role',
            'updater:id,name,role',
        ])
        ->where('id', $idOrCode)
        ->orWhere('asset_code', $idOrCode)
        ->first();

        if (!$asset) {
            return response()->json([
                'message' => 'Data aset tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'asset' => $asset,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_code' => ['nullable', 'string', 'max:30', Rule::unique('assets', 'asset_code')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Asset::CATEGORIES)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:30'],
            'subnet_mask' => ['nullable', 'string', 'max:30'],
            'vlan' => ['nullable', 'string', 'max:60'],
            'uplink_asset_id' => ['nullable', 'exists:assets,id'],
            'network_status' => ['nullable', Rule::in(['online', 'offline', 'unknown'])],
            'specifications' => ['nullable', 'string'],
            'room_id' => ['required', 'exists:rooms,id'],
            'pic' => ['nullable', 'string', 'max:255'],
            'ownership' => ['required', Rule::in(Asset::OWNERSHIPS)],
            'partner_name' => ['nullable', 'string', 'max:255'],
            'ownership_notes' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
            'acquisition_year' => ['nullable', 'integer', 'min:1990', 'max:2099'],
            'notes' => ['nullable', 'string'],
            'needs_followup' => ['nullable', 'boolean'],
            'followup_note' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        if (empty($validated['asset_code'])) {
            $validated['asset_code'] = Asset::generateNextCode();
        } else {
            $validated['asset_code'] = strtoupper(trim($validated['asset_code']));
        }

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('assets/photos', 'public');
            $validated['photo_path'] = $path;
        }
        unset($validated['photo']);

        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;
        $validated['needs_followup'] = (bool) ($validated['needs_followup'] ?? in_array($validated['condition'], ['Rusak', 'Maintenance'], true));

        if (!empty($validated['ip_address'])) {
            $validated['network_status'] = $validated['network_status'] ?? 'online';
            $validated['latency_ms'] = $validated['network_status'] === 'online' ? rand(1, 5) : null;
            $validated['last_checked_at'] = now();
        }

        $asset = Asset::create($validated);
        $asset->load('room');

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => $request->user()?->id,
            'action' => 'created',
            'description' => "Aset baru {$asset->asset_code} ({$asset->name}) ditambahkan pada ruangan {$asset->room?->name}.",
            'changes' => [
                'category' => $asset->category,
                'condition' => $asset->condition,
                'ownership' => $asset->ownership,
                'room' => $asset->room?->name,
            ],
        ]);

        return response()->json([
            'message' => "Aset {$asset->asset_code} berhasil ditambahkan.",
            'asset' => $asset,
        ], 201);
    }

    public function update(Request $request, Asset $asset): JsonResponse
    {
        $validated = $request->validate([
            'asset_code' => ['nullable', 'string', 'max:30', Rule::unique('assets', 'asset_code')->ignore($asset->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(Asset::CATEGORIES)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:150'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'ip_address' => ['nullable', 'string', 'max:45'],
            'mac_address' => ['nullable', 'string', 'max:30'],
            'subnet_mask' => ['nullable', 'string', 'max:30'],
            'vlan' => ['nullable', 'string', 'max:60'],
            'uplink_asset_id' => ['nullable', 'exists:assets,id'],
            'network_status' => ['nullable', Rule::in(['online', 'offline', 'unknown'])],
            'specifications' => ['nullable', 'string'],
            'room_id' => ['required', 'exists:rooms,id'],
            'pic' => ['nullable', 'string', 'max:255'],
            'ownership' => ['required', Rule::in(Asset::OWNERSHIPS)],
            'partner_name' => ['nullable', 'string', 'max:255'],
            'ownership_notes' => ['nullable', 'string'],
            'condition' => ['required', Rule::in(Asset::CONDITIONS)],
            'acquisition_year' => ['nullable', 'integer', 'min:1990', 'max:2099'],
            'notes' => ['nullable', 'string'],
            'needs_followup' => ['nullable', 'boolean'],
            'followup_note' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $oldValues = $asset->only([
            'name', 'category', 'brand', 'model', 'serial_number',
            'ip_address', 'mac_address', 'room_id', 'pic', 'ownership',
            'partner_name', 'condition', 'acquisition_year', 'specifications',
        ]);
        $oldRoomName = $asset->room?->name;

        if ($request->boolean('remove_photo') && $asset->photo_path) {
            Storage::disk('public')->delete($asset->photo_path);
            $validated['photo_path'] = null;
        }

        if ($request->hasFile('photo')) {
            if ($asset->photo_path) {
                Storage::disk('public')->delete($asset->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('assets/photos', 'public');
        }
        unset($validated['photo'], $validated['remove_photo']);

        $validated['updated_by'] = $request->user()?->id;

        if (isset($validated['uplink_asset_id']) && (int) $validated['uplink_asset_id'] === (int) $asset->id) {
            $validated['uplink_asset_id'] = null;
        }

        $asset->update($validated);
        $asset->load('room');

        $diff = [];
        foreach ($oldValues as $key => $oldVal) {
            $newVal = $asset->{$key};
            if ((string) $oldVal !== (string) $newVal) {
                if ($key === 'room_id') {
                    $diff['ruangan'] = ['from' => $oldRoomName, 'to' => $asset->room?->name];
                } else {
                    $diff[$key] = ['from' => $oldVal, 'to' => $newVal];
                }
            }
        }

        if (!empty($diff) || $request->hasFile('photo')) {
            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user()?->id,
                'action' => isset($diff['condition']) ? 'condition_changed' : (isset($diff['ruangan']) ? 'room_moved' : 'updated'),
                'description' => "Data aset {$asset->asset_code} diperbarui oleh " . ($request->user()?->name ?? 'Petugas') . ".",
                'changes' => $diff,
            ]);
        }

        return response()->json([
            'message' => "Data aset {$asset->asset_code} berhasil diperbarui.",
            'asset' => $asset,
        ]);
    }

    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        $code = $asset->asset_code;
        $name = $asset->name;

        AssetHistory::create([
            'asset_id' => $asset->id,
            'user_id' => $request->user()?->id,
            'action' => 'soft_deleted',
            'description' => "Aset {$code} ({$name}) dipindahkan ke Recycle Bin oleh " . ($request->user()?->name ?? 'Petugas') . ".",
            'changes' => ['deleted_at' => now()->toDateTimeString()],
        ]);

        $asset->delete();

        return response()->json([
            'message' => "Aset {$code} berhasil dipindahkan ke Recycle Bin.",
        ]);
    }

    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.name' => ['required', 'string', 'max:255'],
            'rows.*.asset_code' => ['nullable', 'string', 'max:30'],
            'rows.*.category' => ['nullable', 'string'],
            'rows.*.brand' => ['nullable', 'string'],
            'rows.*.model' => ['nullable', 'string'],
            'rows.*.serial_number' => ['nullable', 'string'],
            'rows.*.ip_address' => ['nullable', 'string'],
            'rows.*.mac_address' => ['nullable', 'string'],
            'rows.*.specifications' => ['nullable', 'string'],
            'rows.*.room_name' => ['nullable', 'string'],
            'rows.*.room_id' => ['nullable', 'integer'],
            'rows.*.pic' => ['nullable', 'string'],
            'rows.*.ownership' => ['nullable', 'string'],
            'rows.*.partner_name' => ['nullable', 'string'],
            'rows.*.ownership_notes' => ['nullable', 'string'],
            'rows.*.condition' => ['nullable', 'string'],
            'rows.*.acquisition_year' => ['nullable', 'integer'],
            'rows.*.notes' => ['nullable', 'string'],
        ]);

        $createdCount = 0;
        $updatedCount = 0;
        $defaultRoom = Room::first();

        DB::beginTransaction();
        try {
            foreach ($validated['rows'] as $row) {
                $roomId = $row['room_id'] ?? null;
                if (!$roomId && !empty($row['room_name'])) {
                    $roomName = trim($row['room_name']);
                    $room = Room::where('name', 'like', $roomName)->first();
                    if (!$room) {
                        $room = Room::create([
                            'code' => Room::generateNextCode(),
                            'name' => $roomName,
                            'building' => 'Gedung Pelayanan RSUD',
                            'description' => 'Dibuat otomatis dari Import Excel',
                        ]);
                    }
                    $roomId = $room->id;
                }
                if (!$roomId) {
                    if (!$defaultRoom) {
                        $defaultRoom = Room::create([
                            'code' => 'RM-001',
                            'name' => 'Ruang Server',
                            'building' => 'Gedung Utama',
                        ]);
                    }
                    $roomId = $defaultRoom->id;
                }

                $category = in_array($row['category'] ?? '', Asset::CATEGORIES, true)
                    ? $row['category']
                    : 'Komputer / PC';

                $ownership = in_array($row['ownership'] ?? '', Asset::OWNERSHIPS, true)
                    ? $row['ownership']
                    : 'Milik RSUD';

                $condition = in_array($row['condition'] ?? '', Asset::CONDITIONS, true)
                    ? $row['condition']
                    : 'Baik';

                $code = !empty($row['asset_code']) ? strtoupper(trim($row['asset_code'])) : null;
                $existing = $code ? Asset::withTrashed()->where('asset_code', $code)->first() : null;

                $payload = [
                    'name' => trim($row['name']),
                    'category' => $category,
                    'brand' => $row['brand'] ?? null,
                    'model' => $row['model'] ?? null,
                    'serial_number' => $row['serial_number'] ?? null,
                    'ip_address' => $row['ip_address'] ?? null,
                    'mac_address' => $row['mac_address'] ?? null,
                    'specifications' => $row['specifications'] ?? null,
                    'room_id' => $roomId,
                    'pic' => $row['pic'] ?? null,
                    'ownership' => $ownership,
                    'partner_name' => $row['partner_name'] ?? null,
                    'ownership_notes' => $row['ownership_notes'] ?? null,
                    'condition' => $condition,
                    'acquisition_year' => !empty($row['acquisition_year']) ? (int) $row['acquisition_year'] : (int) date('Y'),
                    'notes' => $row['notes'] ?? null,
                    'needs_followup' => in_array($condition, ['Rusak', 'Maintenance'], true),
                    'updated_by' => $request->user()?->id,
                ];

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                    $existing->update($payload);
                    $updatedCount++;
                } else {
                    $payload['asset_code'] = $code ?: Asset::generateNextCode();
                    $payload['created_by'] = $request->user()?->id;
                    $newAsset = Asset::create($payload);

                    AssetHistory::create([
                        'asset_id' => $newAsset->id,
                        'user_id' => $request->user()?->id,
                        'action' => 'created',
                        'description' => "Aset {$newAsset->asset_code} ditambahkan melalui fitur Import Excel.",
                        'changes' => ['source' => 'Import Excel'],
                    ]);
                    $createdCount++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal mengimpor data: ' . $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => "Import Excel berhasil: {$createdCount} aset baru ditambahkan, {$updatedCount} aset diperbarui.",
            'created_count' => $createdCount,
            'updated_count' => $updatedCount,
        ]);
    }
}
