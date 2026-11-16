<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\AssetTransfer;
use App\Models\AssetTransferDocument;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssetTransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AssetTransfer::with([
            'asset:id,asset_code,name,category,brand,model,serial_number,condition,room_id,pic',
            'asset.room:id,code,name,building',
            'fromRoom:id,code,name,building',
            'toRoom:id,code,name,building',
            'creator:id,name,role',
            'canceller:id,name,role',
            'documents',
        ]);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('previous_pic', 'like', "%{$search}%")
                  ->orWhere('new_pic', 'like', "%{$search}%")
                  ->orWhere('transfer_reason', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('asset', function ($aq) use ($search) {
                      $aq->where('asset_code', 'like', "%{$search}%")
                         ->orWhere('name', 'like', "%{$search}%")
                         ->orWhere('serial_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('fromRoom', fn ($rq) => $rq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('toRoom', fn ($rq) => $rq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('transfer_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('transfer_date', '<=', $endDate);
        }

        if ($fromRoomId = $request->input('from_room_id')) {
            $query->where('from_room_id', $fromRoomId);
        }

        if ($toRoomId = $request->input('to_room_id')) {
            $query->where('to_room_id', $toRoomId);
        }

        if ($category = $request->input('category')) {
            $query->whereHas('asset', fn ($aq) => $aq->where('category', $category));
        }

        if ($createdBy = $request->input('created_by')) {
            $query->where('created_by', $createdBy);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($assetId = $request->input('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        $query->orderByDesc('transfer_date')->orderByDesc('id');

        $items = $query->get();

        // Calculate Dashboard & Report Statistics (Section 1 & Section 9)
        $now = now();
        $totalTransfers = AssetTransfer::where('status', 'Selesai')->count();
        $thisMonthTransfers = AssetTransfer::where('status', 'Selesai')
            ->whereYear('transfer_date', $now->year)
            ->whereMonth('transfer_date', $now->month)
            ->count();
        $todayTransfers = AssetTransfer::where('status', 'Selesai')
            ->whereDate('transfer_date', $now->toDateString())
            ->count();
        $pendingTransfers = AssetTransfer::where('status', 'Menunggu')->count();
        $cancelledTransfers = AssetTransfer::where('status', 'Dibatalkan')->count();
        $distinctAssetsTransferred = AssetTransfer::where('status', 'Selesai')
            ->distinct('asset_id')
            ->count('asset_id');

        // Top Incoming Rooms (Ruangan dengan jumlah aset masuk terbanyak)
        $topIncomingRooms = AssetTransfer::where('status', 'Selesai')
            ->select('to_room_id', DB::raw('COUNT(*) as total_in'))
            ->groupBy('to_room_id')
            ->orderByDesc('total_in')
            ->with('toRoom:id,code,name,building')
            ->take(5)
            ->get()
            ->map(fn ($r) => [
                'room_id' => $r->to_room_id,
                'name' => $r->toRoom?->name ?? '-',
                'building' => $r->toRoom?->building ?? '-',
                'total' => (int) $r->total_in,
            ]);

        // Top Outgoing Rooms (Ruangan dengan jumlah aset keluar terbanyak)
        $topOutgoingRooms = AssetTransfer::where('status', 'Selesai')
            ->select('from_room_id', DB::raw('COUNT(*) as total_out'))
            ->groupBy('from_room_id')
            ->orderByDesc('total_out')
            ->with('fromRoom:id,code,name,building')
            ->take(5)
            ->get()
            ->map(fn ($r) => [
                'room_id' => $r->from_room_id,
                'name' => $r->fromRoom?->name ?? '-',
                'building' => $r->fromRoom?->building ?? '-',
                'total' => (int) $r->total_out,
            ]);

        // Monthly Recap (Jumlah pemindahan per bulan)
        $monthlyRecap = AssetTransfer::where('status', 'Selesai')
            ->select(
                DB::raw("DATE_FORMAT(transfer_date, '%Y-%m') as month_key"),
                DB::raw("DATE_FORMAT(transfer_date, '%b %Y') as month_label"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key', 'desc')
            ->take(12)
            ->get();

        return response()->json([
            'data' => $items,
            'stats' => [
                'total_transfers' => $totalTransfers,
                'this_month_transfers' => $thisMonthTransfers,
                'today_transfers' => $todayTransfers,
                'pending_transfers' => $pendingTransfers,
                'cancelled_transfers' => $cancelledTransfers,
                'distinct_assets_transferred' => $distinctAssetsTransferred,
                'top_incoming_rooms' => $topIncomingRooms,
                'top_outgoing_rooms' => $topOutgoingRooms,
                'monthly_recap' => $monthlyRecap,
            ],
            'next_transfer_number' => AssetTransfer::generateNextTransferNumber(),
            'staff_list' => User::select('id', 'name', 'role')->orderBy('name')->get(),
        ]);
    }

    public function show(string $idOrNumber): JsonResponse
    {
        $transfer = AssetTransfer::with([
            'asset.room',
            'fromRoom',
            'toRoom',
            'creator:id,name,role,position',
            'canceller:id,name,role,position',
            'documents.uploader:id,name',
        ])
        ->where('id', $idOrNumber)
        ->orWhere('transfer_number', $idOrNumber)
        ->first();

        if (!$transfer) {
            return response()->json([
                'message' => 'Data pemindahan aset tidak ditemukan.',
            ], 404);
        }

        $latestCompleted = AssetTransfer::where('asset_id', $transfer->asset_id)
            ->where('status', 'Selesai')
            ->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->first();

        $canRollbackLocation = $transfer->status === 'Selesai'
            && $latestCompleted
            && $latestCompleted->id === $transfer->id
            && $transfer->asset
            && !$transfer->asset->trashed()
            && (int) $transfer->asset->room_id === (int) $transfer->to_room_id;

        return response()->json([
            'transfer' => $transfer,
            'can_rollback_location' => $canRollbackLocation,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'integer'],
            'to_room_id' => ['required', 'exists:rooms,id'],
            'new_pic' => ['nullable', 'string', 'max:255'],
            'transfer_date' => ['required', 'date'],
            'transfer_time' => ['nullable', 'string', 'max:10'],
            'transfer_reason' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['Menunggu', 'Selesai'])],
            'photo_before' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'photo_after' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'supporting_doc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx', 'max:10240'],
        ]);

        // Rule: Hanya aset aktif yang dapat dipindahkan
        $asset = Asset::find($validated['asset_id']);
        if (!$asset) {
            return response()->json([
                'message' => 'Hanya aset aktif yang dapat dipindahkan. Aset tidak ditemukan atau berada di Recycle Bin.',
            ], 422);
        }

        if (!$asset->room_id) {
            return response()->json([
                'message' => 'Aset tidak memiliki lokasi asal yang valid.',
            ], 422);
        }

        // Rule: Tidak boleh ada pemindahan aset ke ruangan yang sama dengan lokasi saat ini
        if ((int) $asset->room_id === (int) $validated['to_room_id']) {
            return response()->json([
                'message' => 'Lokasi tujuan tidak boleh sama dengan lokasi ruangan aset saat ini.',
                'errors' => ['to_room_id' => ['Lokasi tujuan tidak boleh sama dengan lokasi aset saat ini.']],
            ], 422);
        }

        $timePart = !empty($validated['transfer_time'])
            ? trim($validated['transfer_time'])
            : now()->format('H:i:s');
        if (strlen($timePart) === 5) {
            $timePart .= ':00';
        }
        $fullTransferDateTime = $validated['transfer_date'] . ' ' . $timePart;
        $status = $validated['status'] ?? 'Selesai';

        return DB::transaction(function () use ($request, $validated, $asset, $fullTransferDateTime, $status) {
            // Lock asset row for transaction consistency
            $lockedAsset = Asset::lockForUpdate()->findOrFail($asset->id);

            if ((int) $lockedAsset->room_id === (int) $validated['to_room_id']) {
                abort(422, 'Lokasi tujuan tidak boleh sama dengan lokasi aset saat ini.');
            }

            $fromRoom = Room::findOrFail($lockedAsset->room_id);
            $toRoom = Room::findOrFail($validated['to_room_id']);
            $previousPic = $lockedAsset->pic;
            $newPic = filled($validated['new_pic'] ?? null) ? trim($validated['new_pic']) : $previousPic;

            $transfer = AssetTransfer::create([
                'transfer_number' => AssetTransfer::generateNextTransferNumber(),
                'asset_id' => $lockedAsset->id,
                'from_room_id' => $fromRoom->id,
                'to_room_id' => $toRoom->id,
                'previous_pic' => $previousPic,
                'new_pic' => $newPic,
                'transfer_date' => $fullTransferDateTime,
                'transfer_reason' => trim($validated['transfer_reason']),
                'description' => $validated['description'] ?? null,
                'status' => $status,
                'created_by' => $request->user()?->id,
            ]);

            // Handle Optional Documentation Uploads (Section 6)
            $docTypes = ['photo_before', 'photo_after', 'supporting_doc'];
            foreach ($docTypes as $docType) {
                if ($request->hasFile($docType)) {
                    $file = $request->file($docType);
                    $storedPath = $file->store('transfers/docs', 'public');
                    AssetTransferDocument::create([
                        'asset_transfer_id' => $transfer->id,
                        'document_type' => $docType,
                        'file_path' => $storedPath,
                        'original_name' => $file->getClientOriginalName(),
                        'uploaded_by' => $request->user()?->id,
                    ]);
                }
            }

            // If transfer is immediately completed, update asset room & PIC (never changing category/brand/model/SN)
            if ($status === 'Selesai') {
                $lockedAsset->update([
                    'room_id' => $toRoom->id,
                    'pic' => $newPic,
                    'updated_by' => $request->user()?->id,
                ]);

                AssetHistory::create([
                    'asset_id' => $lockedAsset->id,
                    'user_id' => $request->user()?->id,
                    'action' => 'room_moved',
                    'description' => "Pemindahan aset [{$transfer->transfer_number}] dari {$fromRoom->name} ke {$toRoom->name}. Alasan: {$transfer->transfer_reason}.",
                    'changes' => [
                        'transfer_number' => $transfer->transfer_number,
                        'lokasi' => ['from' => $fromRoom->name, 'to' => $toRoom->name],
                        'pic' => ['from' => $previousPic ?: '-', 'to' => $newPic ?: '-'],
                        'alasan' => $transfer->transfer_reason,
                    ],
                ]);
            } else {
                AssetHistory::create([
                    'asset_id' => $lockedAsset->id,
                    'user_id' => $request->user()?->id,
                    'action' => 'transfer_scheduled',
                    'description' => "Jadwal pemindahan aset [{$transfer->transfer_number}] dibuat dari {$fromRoom->name} menuju {$toRoom->name} (Status: Menunggu).",
                    'changes' => [
                        'transfer_number' => $transfer->transfer_number,
                        'lokasi_tujuan' => $toRoom->name,
                        'status' => 'Menunggu',
                    ],
                ]);
            }

            $transfer->load(['asset.room', 'fromRoom', 'toRoom', 'creator', 'documents']);

            return response()->json([
                'message' => $status === 'Selesai'
                    ? "Pemindahan aset {$lockedAsset->asset_code} ke {$toRoom->name} berhasil disimpan ({$transfer->transfer_number})."
                    : "Permintaan pemindahan aset {$transfer->transfer_number} disimpan dengan status Menunggu.",
                'transfer' => $transfer,
            ], 201);
        });
    }

    public function complete(Request $request, AssetTransfer $transfer): JsonResponse
    {
        if ($transfer->status !== 'Menunggu') {
            return response()->json([
                'message' => 'Hanya transaksi berstatus Menunggu yang dapat dikonfirmasi selesai.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $transfer) {
            $asset = Asset::lockForUpdate()->find($transfer->asset_id);
            if (!$asset) {
                return response()->json([
                    'message' => 'Aset tidak aktif atau berada di Recycle Bin.',
                ], 422);
            }

            if ((int) $asset->room_id === (int) $transfer->to_room_id) {
                return response()->json([
                    'message' => 'Aset sudah berada di lokasi tujuan.',
                ], 422);
            }

            $fromRoom = Room::find($asset->room_id);
            $toRoom = Room::findOrFail($transfer->to_room_id);

            $transfer->update([
                'from_room_id' => $asset->room_id,
                'previous_pic' => $asset->pic,
                'status' => 'Selesai',
                'transfer_date' => now(),
            ]);

            $asset->update([
                'room_id' => $toRoom->id,
                'pic' => $transfer->new_pic ?: $asset->pic,
                'updated_by' => $request->user()?->id,
            ]);

            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user()?->id,
                'action' => 'room_moved',
                'description' => "Konfirmasi pemindahan aset [{$transfer->transfer_number}] selesai: {$fromRoom?->name} -> {$toRoom->name}.",
                'changes' => [
                    'transfer_number' => $transfer->transfer_number,
                    'lokasi' => ['from' => $fromRoom?->name, 'to' => $toRoom->name],
                    'status' => 'Selesai',
                ],
            ]);

            $transfer->load(['asset.room', 'fromRoom', 'toRoom', 'creator', 'documents']);

            return response()->json([
                'message' => "Pemindahan {$transfer->transfer_number} berhasil dikonfirmasi selesai. Lokasi aset kini di {$toRoom->name}.",
                'transfer' => $transfer,
            ]);
        });
    }

    public function cancel(Request $request, AssetTransfer $transfer): JsonResponse
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Hanya Administrator yang memiliki wewenang melakukan pembatalan/koreksi pemindahan aset.',
            ], 403);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        if ($transfer->status === 'Dibatalkan') {
            return response()->json([
                'message' => 'Transaksi pemindahan ini sudah berstatus Dibatalkan.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $transfer, $validated) {
            $asset = Asset::withTrashed()->lockForUpdate()->findOrFail($transfer->asset_id);
            $wasCompleted = $transfer->status === 'Selesai';

            if ($wasCompleted) {
                // Rule Section 7: Jika lokasi aset sudah berubah karena pemindahan berikutnya,
                // sistem tidak boleh langsung mengembalikan lokasi aset berdasarkan transaksi lama.
                $latestCompleted = AssetTransfer::where('asset_id', $asset->id)
                    ->where('status', 'Selesai')
                    ->orderByDesc('transfer_date')
                    ->orderByDesc('id')
                    ->first();

                if (($latestCompleted && $latestCompleted->id !== $transfer->id) || (int) $asset->room_id !== (int) $transfer->to_room_id) {
                    return response()->json([
                        'message' => 'Tidak dapat membatalkan lokasi secara langsung karena aset ini sudah mengalami perpindahan berikutnya atau lokasi saat ini telah berubah. Silakan buat transaksi pemindahan baru untuk koreksi lokasi.',
                    ], 422);
                }

                // Rollback asset location and PIC safely to previous state
                $asset->update([
                    'room_id' => $transfer->from_room_id,
                    'pic' => $transfer->previous_pic,
                    'updated_by' => $request->user()->id,
                ]);
            }

            $transfer->update([
                'status' => 'Dibatalkan',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancellation_reason' => trim($validated['cancellation_reason']),
            ]);

            $fromRoomName = $transfer->fromRoom?->name ?? '-';
            $toRoomName = $transfer->toRoom?->name ?? '-';

            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user()->id,
                'action' => 'transfer_cancelled',
                'description' => "Transaksi pemindahan [{$transfer->transfer_number}] ({$fromRoomName} -> {$toRoomName}) DIBATALKAN oleh Administrator ({$request->user()->name}). Alasan: {$transfer->cancellation_reason}." . ($wasCompleted ? " Lokasi aset dikembalikan ke {$fromRoomName}." : ''),
                'changes' => [
                    'transfer_number' => $transfer->transfer_number,
                    'status' => 'Dibatalkan',
                    'cancellation_reason' => $transfer->cancellation_reason,
                    'location_rolled_back_to' => $wasCompleted ? $fromRoomName : null,
                ],
            ]);

            $transfer->load(['asset.room', 'fromRoom', 'toRoom', 'creator', 'canceller', 'documents']);

            return response()->json([
                'message' => "Transaksi pemindahan {$transfer->transfer_number} telah dibatalkan" . ($wasCompleted ? " dan lokasi aset dikembalikan ke {$fromRoomName}." : '.'),
                'transfer' => $transfer,
            ]);
        });
    }
}

