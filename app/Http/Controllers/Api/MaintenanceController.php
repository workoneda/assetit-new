<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Maintenance::with([
            'asset:id,asset_code,name,category,brand,model,condition,room_id,ip_address',
            'asset.room:id,code,name,building',
            'creator:id,name,role',
        ]);

        if ($search = trim((string) $request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('job_type', 'like', "%{$search}%")
                  ->orWhere('complaint', 'like', "%{$search}%")
                  ->orWhere('action_taken', 'like', "%{$search}%")
                  ->orWhere('technician', 'like', "%{$search}%")
                  ->orWhereHas('asset', function ($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%")
                         ->orWhere('asset_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($assetId = $request->input('asset_id')) {
            $query->where('asset_id', $assetId);
        }

        if ($jobType = $request->input('job_type')) {
            $query->where('job_type', $jobType);
        }

        $items = $query->orderByDesc('maintenance_date')->orderByDesc('id')->get();

        $summary = [
            'total' => Maintenance::count(),
            'menunggu' => Maintenance::where('status', 'Menunggu')->count(),
            'sedang_dikerjakan' => Maintenance::where('status', 'Sedang Dikerjakan')->count(),
            'selesai' => Maintenance::where('status', 'Selesai')->count(),
            'total_cost' => (float) Maintenance::sum('cost'),
        ];

        return response()->json([
            'data' => $items,
            'summary' => $summary,
            'next_ticket' => Maintenance::generateNextTicketNumber(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'maintenance_date' => ['required', 'date'],
            'completed_date' => ['nullable', 'date'],
            'job_type' => ['required', 'string', 'max:120'],
            'complaint' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'technician' => ['required', 'string', 'max:120'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Maintenance::STATUSES)],
            'condition_after' => ['nullable', Rule::in(Asset::CONDITIONS)],
            'notes' => ['nullable', 'string'],
            'sync_asset_condition' => ['nullable', 'boolean'],
        ]);

        $validated['ticket_number'] = Maintenance::generateNextTicketNumber();
        $validated['cost'] = (float) ($validated['cost'] ?? 0);
        $validated['created_by'] = $request->user()?->id;

        if ($validated['status'] === 'Selesai' && empty($validated['completed_date'])) {
            $validated['completed_date'] = now()->format('Y-m-d');
        }

        $maintenance = Maintenance::create($validated);

        // Sync asset condition
        $asset = Asset::withTrashed()->find($validated['asset_id']);
        if ($asset && ($request->boolean('sync_asset_condition', true))) {
            $oldCondition = $asset->condition;
            if (!empty($validated['condition_after'])) {
                $newCondition = $validated['condition_after'];
            } elseif ($validated['status'] === 'Selesai') {
                $newCondition = 'Baik';
            } else {
                $newCondition = 'Maintenance';
            }

            $asset->update([
                'condition' => $newCondition,
                'needs_followup' => $newCondition !== 'Baik',
                'updated_by' => $request->user()?->id,
            ]);

            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user()?->id,
                'action' => 'maintenance_logged',
                'description' => "Tiket pemeliharaan {$maintenance->ticket_number} ({$maintenance->job_type}) dicatat dengan status '{$maintenance->status}'. Kondisi aset: {$oldCondition} -> {$newCondition}.",
                'changes' => [
                    'ticket_number' => $maintenance->ticket_number,
                    'status' => $maintenance->status,
                    'condition' => ['from' => $oldCondition, 'to' => $newCondition],
                ],
            ]);
        }

        $maintenance->load(['asset.room', 'creator']);

        return response()->json([
            'message' => "Catatan pemeliharaan {$maintenance->ticket_number} berhasil disimpan.",
            'maintenance' => $maintenance,
        ], 201);
    }

    public function update(Request $request, Maintenance $maintenance): JsonResponse
    {
        $validated = $request->validate([
            'asset_id' => ['required', 'exists:assets,id'],
            'maintenance_date' => ['required', 'date'],
            'completed_date' => ['nullable', 'date'],
            'job_type' => ['required', 'string', 'max:120'],
            'complaint' => ['required', 'string'],
            'action_taken' => ['nullable', 'string'],
            'technician' => ['required', 'string', 'max:120'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(Maintenance::STATUSES)],
            'condition_after' => ['nullable', Rule::in(Asset::CONDITIONS)],
            'notes' => ['nullable', 'string'],
            'sync_asset_condition' => ['nullable', 'boolean'],
        ]);

        if ($validated['status'] === 'Selesai' && empty($validated['completed_date'])) {
            $validated['completed_date'] = now()->format('Y-m-d');
        }

        $validated['cost'] = (float) ($validated['cost'] ?? 0);
        $maintenance->update($validated);

        $asset = Asset::withTrashed()->find($validated['asset_id']);
        if ($asset && ($request->boolean('sync_asset_condition', true))) {
            $oldCondition = $asset->condition;
            if (!empty($validated['condition_after'])) {
                $newCondition = $validated['condition_after'];
            } elseif ($validated['status'] === 'Selesai') {
                $newCondition = 'Baik';
            } else {
                $newCondition = 'Maintenance';
            }

            if ($oldCondition !== $newCondition) {
                $asset->update([
                    'condition' => $newCondition,
                    'needs_followup' => $newCondition !== 'Baik',
                    'updated_by' => $request->user()?->id,
                ]);
            }

            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user()?->id,
                'action' => 'maintenance_logged',
                'description' => "Tiket pemeliharaan {$maintenance->ticket_number} diperbarui menjadi status '{$maintenance->status}'.",
                'changes' => [
                    'ticket_number' => $maintenance->ticket_number,
                    'status' => $maintenance->status,
                    'condition' => ['from' => $oldCondition, 'to' => $newCondition],
                ],
            ]);
        }

        $maintenance->load(['asset.room', 'creator']);

        return response()->json([
            'message' => "Data pemeliharaan {$maintenance->ticket_number} berhasil diperbarui.",
            'maintenance' => $maintenance,
        ]);
    }

    public function destroy(Maintenance $maintenance): JsonResponse
    {
        $ticket = $maintenance->ticket_number;
        $maintenance->delete();

        return response()->json([
            'message' => "Riwayat pemeliharaan {$ticket} berhasil dihapus.",
        ]);
    }
}
