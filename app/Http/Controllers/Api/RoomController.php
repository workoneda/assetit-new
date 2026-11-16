<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssetTransfer;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Room::withCount([
            'assets',
            'assets as baik_count' => fn ($q) => $q->where('condition', 'Baik'),
            'assets as rusak_count' => fn ($q) => $q->where('condition', 'Rusak'),
            'assets as maintenance_count' => fn ($q) => $q->where('condition', 'Maintenance'),
        ]);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('building', 'like', "%{$search}%")
                  ->orWhere('pic_name', 'like', "%{$search}%");
            });
        }

        $rooms = $query->orderBy('code')->get();

        return response()->json([
            'data' => $rooms,
            'next_code' => Room::generateNextCode(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:30', Rule::unique('rooms', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'building' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = Room::generateNextCode();
        } else {
            $validated['code'] = strtoupper(trim($validated['code']));
        }

        $room = Room::create($validated);
        $room->loadCount(['assets', 'assets as baik_count', 'assets as rusak_count', 'assets as maintenance_count']);

        return response()->json([
            'message' => "Ruangan {$room->name} berhasil ditambahkan.",
            'room' => $room,
        ], 201);
    }

    public function update(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('rooms', 'code')->ignore($room->id)],
            'name' => ['required', 'string', 'max:255'],
            'building' => ['required', 'string', 'max:255'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $room->update($validated);
        $room->loadCount(['assets', 'assets as baik_count', 'assets as rusak_count', 'assets as maintenance_count']);

        return response()->json([
            'message' => "Data ruangan {$room->name} berhasil diperbarui.",
            'room' => $room,
        ]);
    }

    public function destroy(Room $room): JsonResponse
    {
        $activeCount = $room->assets()->count();
        $trashedCount = $room->assets()->onlyTrashed()->count();
        $transferCount = AssetTransfer::where('from_room_id', $room->id)
            ->orWhere('to_room_id', $room->id)
            ->count();

        if ($activeCount > 0 || $trashedCount > 0 || $transferCount > 0) {
            return response()->json([
                'message' => "Ruangan {$room->name} tidak dapat dihapus karena masih memiliki {$activeCount} aset aktif"
                    . ($trashedCount > 0 ? ", {$trashedCount} aset di Recycle Bin" : '')
                    . ($transferCount > 0 ? ", dan {$transferCount} riwayat pemindahan aset" : '')
                    . '.',
            ], 422);
        }

        $name = $room->name;
        $room->delete();

        return response()->json([
            'message' => "Ruangan {$name} berhasil dihapus.",
        ]);
    }
}
