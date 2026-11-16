<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Room;
use App\Models\User;
use Tests\TestCase;

class ItAssetSystemTest extends TestCase
{
    public function test_full_it_asset_management_workflow(): void
    {
        // 1. Login as Administrator
        $loginRes = $this->postJson('/api/auth/login', [
            'identity' => 'admin@rsud.go.id',
            'password' => 'password123',
        ]);
        $loginRes->assertStatus(200)
                 ->assertJsonPath('user.role', 'admin');

        $admin = User::where('email', 'admin@rsud.go.id')->first();
        $this->actingAs($admin);

        // 2. Dashboard Analytics returns all 8 stat cards & charts
        $dashRes = $this->getJson('/api/dashboard');
        $dashRes->assertStatus(200)
                ->assertJsonStructure([
                    'stats' => [
                        'total_assets',
                        'total_pc_laptop',
                        'total_printer',
                        'total_network',
                        'total_rsud',
                        'total_kso_partner',
                        'total_damaged',
                        'total_maintenance',
                    ],
                    'charts' => [
                        'by_category',
                        'by_condition',
                        'by_ownership',
                        'by_room',
                    ],
                    'lists' => [
                        'latest_assets',
                        'active_maintenances',
                        'followup_assets',
                    ],
                ]);

        // 3. Rooms CRUD & protection against deleting room with assets
        $roomsRes = $this->getJson('/api/rooms');
        $roomsRes->assertStatus(200);
        $roomWithAssets = Room::has('assets')->first();
        $delRoomRes = $this->deleteJson("/api/rooms/{$roomWithAssets->id}");
        $delRoomRes->assertStatus(422); // Protected!

        // 4. Asset Directory CRUD + Soft Delete + Recycle Bin Restore
        $createAssetRes = $this->postJson('/api/assets', [
            'name' => 'PC Test Verifikasi Sistem',
            'category' => 'Komputer / PC',
            'brand' => 'Dell',
            'model' => 'OptiPlex 7010',
            'serial_number' => 'SN-TEST-999',
            'ip_address' => '10.10.99.99',
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'specifications' => 'Core i7, 16GB RAM, 512GB SSD',
            'room_id' => $roomWithAssets->id,
            'pic' => 'Petugas Uji',
            'ownership' => 'Milik RSUD',
            'condition' => 'Baik',
            'acquisition_year' => 2026,
        ]);
        $createAssetRes->assertStatus(201);
        $newAssetId = $createAssetRes->json('asset.id');
        $newAssetCode = $createAssetRes->json('asset.asset_code');

        // 5. Detail Aset by QR Code (asset_code)
        $detailByCode = $this->getJson("/api/assets/{$newAssetCode}");
        $detailByCode->assertStatus(200)
                     ->assertJsonPath('asset.id', $newAssetId);

        // 6. Soft Delete to Recycle Bin
        $softDelRes = $this->deleteJson("/api/assets/{$newAssetId}");
        $softDelRes->assertStatus(200);

        $binRes = $this->getJson('/api/recycle-bin');
        $binRes->assertStatus(200);

        // 7. Restore from Recycle Bin & Force Delete
        $restoreRes = $this->postJson("/api/recycle-bin/{$newAssetId}/restore");
        $restoreRes->assertStatus(200);

        $this->deleteJson("/api/assets/{$newAssetId}")->assertStatus(200);
        $forceDelRes = $this->deleteJson("/api/recycle-bin/{$newAssetId}");
        $forceDelRes->assertStatus(200);

        // 8. Network Map, Maintenance Radar, Reports, System Info
        $this->getJson('/api/network-map')->assertStatus(200);
        $this->getJson('/api/maintenances')->assertStatus(200);
        $this->getJson('/api/reports')->assertStatus(200);
        $this->getJson('/api/system/info')->assertStatus(200);

        // 9. Asset Transfer Management (Mutasi Lokasi, Validasi Ruangan Sama, Timeline & Pembatalan Admin)
        $targetAsset = Asset::first();
        $originalRoomId = $targetAsset->room_id;
        $originalPic = $targetAsset->pic;
        $destinationRoom = Room::where('id', '!=', $originalRoomId)->first();

        // 9a. Reject transfer to the exact same room
        $sameRoomRes = $this->postJson('/api/asset-transfers', [
            'asset_id' => $targetAsset->id,
            'to_room_id' => $originalRoomId,
            'transfer_date' => now()->toDateString(),
            'transfer_reason' => 'Mutasi ruangan',
            'status' => 'Selesai',
        ]);
        $sameRoomRes->assertStatus(422);

        // 9b. Execute valid transfer with DB transaction
        $transferRes = $this->postJson('/api/asset-transfers', [
            'asset_id' => $targetAsset->id,
            'to_room_id' => $destinationRoom->id,
            'new_pic' => 'Koordinator Ruang Tujuan Uji',
            'transfer_date' => now()->toDateString(),
            'transfer_time' => '10:30',
            'transfer_reason' => 'Kebutuhan operasional',
            'description' => 'Pengujian otomatis pemindahan aset IT',
            'status' => 'Selesai',
        ]);
        $transferRes->assertStatus(201);
        $transferId = $transferRes->json('transfer.id');

        // Verify asset table updated to new room & new PIC
        $targetAsset->refresh();
        $this->assertEquals($destinationRoom->id, $targetAsset->room_id);
        $this->assertEquals('Koordinator Ruang Tujuan Uji', $targetAsset->pic);

        // 9c. Cancel transfer as Administrator and verify rollback to previous room & PIC
        $cancelRes = $this->postJson("/api/asset-transfers/{$transferId}/cancel", [
            'cancellation_reason' => 'Koreksi pengujian otomatis oleh Administrator',
        ]);
        $cancelRes->assertStatus(200)
                  ->assertJsonPath('transfer.status', 'Dibatalkan');

        $targetAsset->refresh();
        $this->assertEquals($originalRoomId, $targetAsset->room_id);
        $this->assertEquals($originalPic, $targetAsset->pic);

        // Delete the test cancelled transfer record to keep seeded DB clean
        \App\Models\AssetTransfer::where('id', $transferId)->delete();
    }

    public function test_kso_reconciliation_matching_and_resolutions_work_with_mysql(): void
    {
        $admin = User::where('email', 'admin@rsud.go.id')->firstOrFail();

        // 1. Verify initial seeded KSO Reconciliation dashboard & stats
        $indexRes = $this->actingAs($admin)->getJson('/api/kso-reconciliation');
        $indexRes->assertStatus(200)
            ->assertJsonStructure([
                'partners',
                'sessions',
                'active_session',
                'results',
                'audit_logs',
                'stats' => [
                    'total_system_assets',
                    'total_kso_items',
                    'match_count',
                    'missing_in_system_count',
                    'missing_in_kso_count',
                    'data_mismatch_count',
                    'duplicate_sn_count',
                ],
            ]);

        $this->assertEquals('REC-2026-0001', $indexRes->json('active_session.reconciliation_number'));
        $this->assertGreaterThan(50, $indexRes->json('stats.match_count'));

        // 2. Test uploading a KSO sheet for MITRA ABADI without automatically changing main `assets` table
        $initialAssetCount = Asset::count();
        $mitraAssets = Asset::with('room')
            ->where('partner_name', 'MITRA ABADI')
            ->whereNotNull('serial_number')
            ->where('serial_number', '!=', '')
            ->take(3)
            ->get();

        $this->assertGreaterThanOrEqual(3, $mitraAssets->count());

        $a1 = $mitraAssets[0]; // Will be MATCH (with lowercase & extra spaces to test normalization)
        $a2 = $mitraAssets[1]; // Will be MODEL MISMATCH
        $a3 = $mitraAssets[2]; // Will be DUPLICATE SN
        // Remaining MITRA ABADI assets not included in items will become MISSING IN KSO

        $mitraPartner = \App\Models\KsoPartner::where('name', 'MITRA ABADI')->firstOrFail();

        $sessionRes = $this->actingAs($admin)->postJson('/api/kso-reconciliation/sessions', [
            'kso_partner_id' => $mitraPartner->id,
            'partner_name' => 'MITRA ABADI',
            'file_name' => 'Test_Mitra_Abadi_Reconciliation.xlsx',
            'period' => 'Uji Rekonsiliasi 2026',
            'notes' => 'Pengujian algoritma rekonsiliasi 6 tahap',
            'items' => [
                [
                    'row_number' => 1,
                    'item_number' => '1',
                    'sn' => '  ' . strtolower($a1->serial_number) . '  ',
                    'model' => '  ' . strtolower($a1->model ?: $a1->name) . ' ',
                    'brand' => $a1->brand,
                    'category' => $a1->category,
                    'location' => $a1->room?->name,
                    'notes' => 'Normalisasi huruf & spasi',
                ],
                [
                    'row_number' => 2,
                    'item_number' => '2',
                    'sn' => $a2->serial_number,
                    'model' => ($a2->model ?: $a2->name) . ' PRO SERIES X',
                    'brand' => $a2->brand,
                    'category' => $a2->category,
                    'location' => $a2->room?->name,
                    'notes' => 'Perbedaan model',
                ],
                [
                    'row_number' => 3,
                    'item_number' => '3',
                    'sn' => $a3->serial_number,
                    'model' => $a3->model ?: $a3->name,
                    'brand' => $a3->brand,
                    'category' => $a3->category,
                    'location' => $a3->room?->name,
                    'notes' => 'Duplikat baris 1',
                ],
                [
                    'row_number' => 4,
                    'item_number' => '4',
                    'sn' => $a3->serial_number,
                    'model' => $a3->model ?: $a3->name,
                    'brand' => $a3->brand,
                    'category' => $a3->category,
                    'location' => $a3->room?->name,
                    'notes' => 'Duplikat baris 2',
                ],
                [
                    'row_number' => 5,
                    'item_number' => '5',
                    'sn' => 'SN-KSO-BARU-9991',
                    'model' => 'EPSON L3250 WIFI',
                    'brand' => 'Epson',
                    'category' => 'Printer',
                    'location' => 'IGD',
                    'notes' => 'Belum terdaftar di sistem',
                ],
                [
                    'row_number' => 6,
                    'item_number' => '6',
                    'sn' => '',
                    'model' => 'PERANGKAT TANPA SN',
                    'brand' => 'Generic',
                    'category' => 'Perangkat IT lainnya',
                    'location' => 'IT',
                    'notes' => 'SN kosong',
                ],
            ],
        ]);

        $sessionRes->assertStatus(201);
        $newSessionId = $sessionRes->json('session.id');

        // Confirm main `assets` table was NOT modified by upload
        $this->assertEquals($initialAssetCount, Asset::count());

        $this->assertEquals(1, $sessionRes->json('session.match_count'));
        $this->assertEquals(1, $sessionRes->json('session.model_mismatch_count'));
        $this->assertEquals(2, $sessionRes->json('session.duplicate_sn_count'));
        $this->assertEquals(1, $sessionRes->json('session.missing_in_system_count'));
        $this->assertGreaterThanOrEqual(1, $sessionRes->json('session.missing_in_kso_count'));

        // 3. Test Resolution Action on MODEL MISMATCH (keep_system)
        $mismatchResult = \App\Models\ReconciliationResult::where('reconciliation_session_id', $newSessionId)
            ->where('status', 'MODEL MISMATCH')
            ->firstOrFail();

        $resolveMismatch = $this->actingAs($admin)->postJson("/api/kso-reconciliation/results/{$mismatchResult->id}/resolve", [
            'action' => 'keep_system',
            'action_note' => 'Gunakan penamaan model standar RSUD Majenang.',
        ]);
        $resolveMismatch->assertStatus(200);
        $this->assertEquals('Data Sistem Dipertahankan', $resolveMismatch->json('result.action_status'));

        // 4. Test Resolution Action on MISSING IN SYSTEM (add_to_directory) and clean up created asset
        $missingSysResult = \App\Models\ReconciliationResult::where('reconciliation_session_id', $newSessionId)
            ->where('status', 'MISSING IN SYSTEM')
            ->firstOrFail();

        $firstRoom = Room::firstOrFail();

        $resolveAdd = $this->actingAs($admin)->postJson("/api/kso-reconciliation/results/{$missingSysResult->id}/resolve", [
            'action' => 'add_to_directory',
            'action_note' => 'Tambahkan aset KSO baru ke Asset Directory setelah verifikasi fisik.',
            'room_id' => $firstRoom->id,
            'name' => 'EPSON L3250 WIFI',
            'category' => 'Printer',
            'brand' => 'Epson',
            'model' => 'EPSON L3250 WIFI',
            'serial_number' => 'SN-KSO-BARU-9991',
        ]);
        $resolveAdd->assertStatus(200);
        $this->assertEquals('Ditambahkan ke Asset Directory', $resolveAdd->json('result.action_status'));
        $createdAssetId = $resolveAdd->json('result.asset_id');
        $this->assertNotNull($createdAssetId);
        $this->assertEquals($initialAssetCount + 1, Asset::count());

        // Clean up test session & test created asset so seeded state stays pristine
        \App\Models\AssetHistory::where('asset_id', $createdAssetId)->delete();
        Asset::where('id', $createdAssetId)->forceDelete();
        \App\Models\ReconciliationAuditLog::where('reconciliation_session_id', $newSessionId)->delete();
        \App\Models\ReconciliationResult::where('reconciliation_session_id', $newSessionId)->delete();
        \App\Models\ReconciliationImport::where('reconciliation_session_id', $newSessionId)->delete();
        \App\Models\ReconciliationSession::where('id', $newSessionId)->delete();
    }
}

