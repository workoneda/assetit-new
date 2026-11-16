<?php

namespace Database\Seeders;

use App\Http\Controllers\Api\KsoReconciliationController;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\AssetTransfer;
use App\Models\AssetTransferDocument;
use App\Models\KsoPartner;
use App\Models\Maintenance;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationImport;
use App\Models\ReconciliationResult;
use App\Models\ReconciliationSession;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with actual RSUD Majenang IT Asset Data.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@rsud.go.id'],
            [
                'name' => 'Administrator IT RSUD Majenang',
                'username' => 'admin',
                'role' => 'admin',
                'position' => 'Kepala Unit SIMRS & TI RSUD Majenang',
                'phone' => '0812-3456-7890',
                'is_active' => true,
                'password' => Hash::make('password123'),
            ]
        );

        $petugas1 = User::updateOrCreate(
            ['email' => 'petugas@rsud.go.id'],
            [
                'name' => 'Tim Teknis IT RSUD Majenang',
                'username' => 'petugas',
                'role' => 'petugas_it',
                'position' => 'Petugas IT Support & Jaringan RSUD Majenang',
                'phone' => '0813-8899-1122',
                'is_active' => true,
                'password' => Hash::make('password123'),
            ]
        );

        $jsonPath = database_path('seeders/rsud_majenang_data.json');
        if (!file_exists($jsonPath)) {
            return;
        }

        $rows = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($rows) || empty($rows)) {
            return;
        }

        // Clear all existing rooms, assets, histories, transfers, maintenances, and KSO reconciliations
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ReconciliationAuditLog::truncate();
        ReconciliationResult::truncate();
        ReconciliationImport::truncate();
        ReconciliationSession::truncate();
        KsoPartner::truncate();
        AssetTransferDocument::truncate();
        AssetTransfer::truncate();
        Maintenance::truncate();
        AssetHistory::truncate();
        Asset::truncate();
        Room::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Create Master Data Ruangan from distinct RUANGAN values in Excel
        $roomMap = [];
        $roomIndex = 1;

        foreach ($rows as $row) {
            $roomName = trim($row['ruangan'] ?: 'IT');
            if (!isset($roomMap[$roomName])) {
                $code = 'RM-' . str_pad((string) $roomIndex++, 3, '0', STR_PAD_LEFT);
                $building = $this->inferBuilding($roomName);
                $room = Room::create([
                    'code' => $code,
                    'name' => $roomName,
                    'building' => $building,
                    'pic_name' => $row['pengguna'] ?: 'Koordinator ' . $roomName,
                    'description' => "Lokasi penempatan perangkat IT pada ruang {$roomName} ({$building}) - RSUD Majenang.",
                ]);
                $roomMap[$roomName] = $room;
            }
        }

        // 2. Insert all 415 Assets from RSUD Majenang Excel
        $now = now()->toDateTimeString();
        $createdAssets = [];
        $ticketSeq = 1;
        $transferSeq = 1;
        $itRoom = $roomMap['IT'] ?? reset($roomMap);

        foreach ($rows as $idx => $row) {
            $assetCode = 'AST-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT);
            $roomName = trim($row['ruangan'] ?: 'IT');
            $room = $roomMap[$roomName];

            $rawName = trim($row['perangkat_it']);
            $rawSpec = trim($row['spesifikasi']);
            $rawJenis = trim($row['jenis_perangkat']);
            $rawOwner = trim($row['status_kepemilikan']);
            $rawKondisi = trim($row['kondisi']);
            $rawKet = trim($row['keterangan']);
            $rawIp = trim($row['ip']);
            $rawSn = trim($row['no_seri']);
            $rawFoto = trim($row['foto']);
            $rawPic = trim($row['pengguna']);

            // Category Mapping
            $category = match ($rawJenis) {
                'KOMPUTER' => 'Komputer / PC',
                'LAPTOP' => 'Laptop',
                'PRINTER' => 'Printer',
                'SWITCH' => 'Switch',
                'ROUTER' => 'Router',
                'SERVER' => 'Server',
                'ACCESS POINT' => 'Access Point',
                'UPS' => 'UPS',
                'SCANNER', 'LAINNYA' => 'Perangkat IT lainnya',
                default => str_contains(strtoupper($rawSpec), 'CASING') || str_starts_with(strtoupper($rawName), 'PC')
                    ? 'Komputer / PC'
                    : 'Perangkat IT lainnya',
            };

            // Device Name Fallback & Normalization
            if ($rawName === '') {
                $name = $rawSpec !== '' ? "Unit PC ({$rawSpec}) - {$roomName}" : "Perangkat IT {$roomName} #{$row['row_num']}";
            } else {
                $name = $rawName;
            }

            // Brand & Model Inference
            [$brand, $model] = $this->extractBrandAndModel($name, $rawSpec, $category);

            // Ownership & Partner Mapping
            $ownership = 'Milik RSUD';
            $partnerName = null;
            $ownershipNotes = null;

            if (str_contains($rawOwner, 'KSO')) {
                $ownership = 'KSO';
                if (preg_match('/\(([^)]+)\)/', $rawOwner, $m)) {
                    $partnerName = trim($m[1]);
                } else {
                    $partnerName = 'Mitra KSO RSUD Majenang';
                }
                $ownershipNotes = "Status Kepemilikan Excel: {$rawOwner}";
            } elseif ($rawOwner === 'LAINNYA') {
                $ownership = 'Milik Pihak Ketiga';
                $partnerName = 'Mitra Pihak Ketiga / Bawaan Alat';
                $ownershipNotes = $rawKet ?: 'Aset Kepemilikan Pihak Ketiga / Kerjasama Alat';
            }

            // Condition & Follow-up Mapping
            $upperKet = strtoupper($rawKet);
            $needsUpgrade = str_contains($upperKet, 'NEED UPGRADE') && !str_contains($upperKet, 'SUDAH DIUPGRADE');
            $inRepair = str_contains($upperKet, 'SEDANG DIPERBAIKI') || str_contains($upperKet, 'DIBAWA KE IT');

            if ($rawKondisi === 'RUSAK') {
                $condition = 'Rusak';
            } elseif ($needsUpgrade || $inRepair) {
                $condition = 'Maintenance';
            } else {
                $condition = 'Baik';
            }

            $needsFollowup = ($condition !== 'Baik') || $needsUpgrade || $inRepair;

            // VLAN / Subnet Label Inference from IP
            $vlan = null;
            if ($rawIp !== '') {
                if (str_starts_with($rawIp, '192.168.9.')) {
                    $vlan = 'Subnet 192.168.9.0/24 (LAN Utama RSUD)';
                } elseif (str_starts_with($rawIp, '192.168.17.')) {
                    $vlan = 'Subnet 192.168.17.0/24 (Segmen SIMRS)';
                } elseif (str_starts_with($rawIp, '192.168.15.')) {
                    $vlan = 'Subnet 192.168.15.0/24 (Segmen Pelayanan)';
                } else {
                    $vlan = 'LAN Internal RSUD Majenang';
                }
            }

            $networkStatus = $condition === 'Rusak' ? 'offline' : ($rawIp !== '' || in_array($category, ['Router', 'Switch', 'Server'], true) ? 'online' : 'unknown');

            // Notes (include JUMLAH > 1 if present)
            $notesParts = [];
            if ($rawKet !== '') {
                $notesParts[] = $rawKet;
            }
            if (($row['jumlah'] ?? 1) > 1) {
                $notesParts[] = "Jumlah tercatat di Excel: {$row['jumlah']} unit";
            }
            $finalNotes = !empty($notesParts) ? implode(' | ', $notesParts) : null;

            $asset = Asset::create([
                'asset_code' => $assetCode,
                'name' => $name,
                'category' => $category,
                'brand' => $brand,
                'model' => $model,
                'serial_number' => ($rawSn !== '' && strtoupper($rawSn) !== 'HAHAHAH') ? $rawSn : null,
                'ip_address' => $rawIp !== '' ? $rawIp : null,
                'mac_address' => null,
                'subnet_mask' => '255.255.255.0',
                'vlan' => $vlan,
                'uplink_asset_id' => null,
                'network_status' => $networkStatus,
                'latency_ms' => $networkStatus === 'online' ? rand(1, 5) : null,
                'last_checked_at' => $now,
                'specifications' => $rawSpec !== '' ? $rawSpec : null,
                'room_id' => $room->id,
                'pic' => $rawPic !== '' ? $rawPic : null,
                'ownership' => $ownership,
                'partner_name' => $partnerName,
                'ownership_notes' => $ownershipNotes,
                'condition' => $condition,
                'acquisition_year' => 2024,
                'photo_path' => $rawFoto !== '' ? $rawFoto : null,
                'notes' => $finalNotes,
                'needs_followup' => $needsFollowup,
                'followup_note' => $needsFollowup ? ($rawKet ?: "Perangkat berstatus {$condition}") : null,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);

            $createdAssets[] = $asset;

            // Create initial History record
            AssetHistory::create([
                'asset_id' => $asset->id,
                'user_id' => $admin->id,
                'action' => 'created',
                'description' => "Data perangkat {$asset->asset_code} ({$asset->name}) dimigrasikan dari Master Excel Perangkat IT RSUD Majenang ke ruangan {$room->name}.",
                'changes' => [
                    'ruangan' => $room->name,
                    'kategori' => $category,
                    'kepemilikan' => $ownership,
                    'kondisi' => $condition,
                ],
            ]);

            // Create Maintenance Radar tickets for items with NEED UPGRADE, SEDANG DIPERBAIKI, RUSAK, or SUDAH DIPERBAIKI
            if ($needsUpgrade || $inRepair || $rawKondisi === 'RUSAK' || str_contains($upperKet, 'SUDAH DIPERBAIKI') || str_contains($upperKet, 'SUDAH DIUPGRADE')) {
                $ticketNo = 'MNT-2026-' . str_pad((string) $ticketSeq++, 4, '0', STR_PAD_LEFT);
                $isDone = str_contains($upperKet, 'SUDAH DIPERBAIKI') || str_contains($upperKet, 'SUDAH DIUPGRADE');

                Maintenance::create([
                    'ticket_number' => $ticketNo,
                    'asset_id' => $asset->id,
                    'maintenance_date' => now()->subDays(rand(1, 15))->format('Y-m-d'),
                    'completed_date' => $isDone ? now()->format('Y-m-d') : null,
                    'job_type' => $needsUpgrade || str_contains($upperKet, 'UPGRADE') ? 'Upgrade Komponen' : 'Perbaikan Hardware',
                    'complaint' => $rawKet !== '' ? $rawKet : "Perangkat {$asset->name} di ruang {$room->name} mengalami kerusakan ({$rawSpec}).",
                    'action_taken' => $isDone
                        ? $rawKet
                        : ($needsUpgrade ? 'Rekomendasi upgrade RAM / SSD & update OS Windows untuk kelancaran SIMRS.' : 'Pemeriksaan unit oleh tim teknisi IT RSUD Majenang.'),
                    'technician' => $petugas1->name,
                    'cost' => 0,
                    'status' => $isDone ? 'Selesai' : ($inRepair ? 'Sedang Dikerjakan' : 'Menunggu'),
                    'condition_after' => $condition,
                    'notes' => "Data tindak lanjut dari laporan inventaris RSUD Majenang ({$room->name}).",
                    'created_by' => $admin->id,
                ]);
            }

            // Extract Historical Room Transfers from RSUD Majenang Excel Notes (KETERANGAN)
            if (str_contains($upperKet, 'PINDAH') || str_contains($upperKet, 'DARI ')) {
                $originRoom = $this->resolveOriginRoom($upperKet, $roomMap, $room, $itRoom);
                if ($originRoom && $originRoom->id !== $room->id) {
                    $transferDate = now()->subDays(rand(5, 45));
                    if (preg_match('#(\d{2})/(\d{2})/(\d{4})#', $rawKet, $dm)) {
                        $transferDate = "{$dm[3]}-{$dm[2]}-{$dm[1]} 09:00:00";
                    }

                    $trfNo = 'TRF-2026-' . str_pad((string) $transferSeq++, 4, '0', STR_PAD_LEFT);
                    AssetTransfer::create([
                        'transfer_number' => $trfNo,
                        'asset_id' => $asset->id,
                        'from_room_id' => $originRoom->id,
                        'to_room_id' => $room->id,
                        'previous_pic' => 'Koordinator ' . $originRoom->name,
                        'new_pic' => $asset->pic ?: ('Koordinator ' . $room->name),
                        'transfer_date' => $transferDate,
                        'transfer_reason' => str_contains($upperKet, 'GANTI') ? 'Penggantian perangkat' : 'Kebutuhan operasional',
                        'description' => $rawKet,
                        'status' => 'Selesai',
                        'created_by' => $admin->id,
                    ]);

                    AssetHistory::create([
                        'asset_id' => $asset->id,
                        'user_id' => $admin->id,
                        'action' => 'room_moved',
                        'description' => "Riwayat Pemindahan [{$trfNo}]: Dipindahkan dari {$originRoom->name} ke {$room->name}. Catatan: {$rawKet}.",
                        'changes' => [
                            'transfer_number' => $trfNo,
                            'lokasi' => ['from' => $originRoom->name, 'to' => $room->name],
                            'keterangan' => $rawKet,
                        ],
                    ]);
                }
            }
        }

        // 3. Link Uplink Network Topology (Core Router RB4011 -> Switches -> Room Endpoints)
        $coreRouter = Asset::where('name', 'like', '%RB4011%')->first()
            ?? Asset::where('category', 'Router')->first();

        if ($coreRouter) {
            if (!$coreRouter->ip_address) {
                $coreRouter->update(['ip_address' => '192.168.9.1', 'vlan' => 'Core Gateway RSUD Majenang']);
            }

            $switches = Asset::where('category', 'Switch')->get();
            $roomSwitchMap = [];
            foreach ($switches as $sw) {
                $sw->update(['uplink_asset_id' => $coreRouter->id]);
                $roomSwitchMap[$sw->room_id] = $sw->id;
            }

            $firstSwitchId = $switches->first()?->id ?? $coreRouter->id;

            // Link Servers and other Routers to Core Router / Core Switch
            Asset::whereIn('category', ['Server', 'Router'])
                ->where('id', '!=', $coreRouter->id)
                ->update(['uplink_asset_id' => $coreRouter->id]);

            // Link IP-connected PCs/Printers to their room's switch or core switch
            $endpoints = Asset::whereNotNull('ip_address')
                ->whereNotIn('category', ['Router', 'Switch', 'Server'])
                ->get();

            foreach ($endpoints as $ep) {
                $uplinkId = $roomSwitchMap[$ep->room_id] ?? $firstSwitchId;
                $ep->update(['uplink_asset_id' => $uplinkId]);
            }
        }

        // 4. Seed Initial KSO Partners & Baseline KSO Reconciliation Snapshot (MITRA ABADI)
        $this->seedInitialKsoReconciliation($admin);
    }

    public function seedInitialKsoReconciliation(User $admin): void
    {
        Auth::login($admin);

        $mitraAbadi = KsoPartner::updateOrCreate(
            ['name' => 'MITRA ABADI'],
            [
                'code' => 'KSO-001',
                'contract_number' => 'KSO/RSUD-MJN/2026/001',
                'contact_person' => 'Divisi Inventaris KSO Mitra Abadi',
                'phone' => '0280-621112',
                'email' => 'kso.mitraabadi@partner.co.id',
                'notes' => 'Mitra KSO penyedia unit Printer & Perangkat Operasional RSUD Majenang',
                'is_active' => true,
            ]
        );

        KsoPartner::updateOrCreate(
            ['name' => 'KALIMAS'],
            [
                'code' => 'KSO-002',
                'contract_number' => 'KSO/RSUD-MJN/2026/002',
                'contact_person' => 'Koordinator Teknis KSO Kalimas',
                'phone' => '0280-621223',
                'email' => 'support@kalimas-kso.co.id',
                'notes' => 'Mitra KSO penyedia perangkat pendukung pelayanan RSUD Majenang',
                'is_active' => true,
            ]
        );

        KsoPartner::updateOrCreate(
            ['name' => 'Mitra Pihak Ketiga / Bawaan Alat'],
            [
                'code' => 'KSO-003',
                'contract_number' => 'KSO/RSUD-MJN/2026/003',
                'contact_person' => 'Vendor Alat Penunjang Medis & Laboratorium',
                'notes' => 'Perangkat IT bawaan alat medis / pihak ketiga',
                'is_active' => true,
            ]
        );

        $mitraAssets = Asset::with('room')
            ->where('partner_name', 'MITRA ABADI')
            ->orderBy('id')
            ->get();

        if ($mitraAssets->isEmpty()) {
            return;
        }

        $items = [];
        $rowNo = 1;

        // Omit last 2 assets that have valid SNs so they become MISSING IN KSO
        $validSnAssets = $mitraAssets->filter(fn ($a) => trim((string) $a->serial_number) !== '' && trim((string) $a->serial_number) !== '-')->values();
        $omittedIds = $validSnAssets->slice(-2)->pluck('id')->all();

        $mismatchCount = 0;
        $duplicatedAdded = false;

        foreach ($mitraAssets as $asset) {
            if (in_array($asset->id, $omittedIds, true)) {
                continue; // Simulate asset recorded in RSUD system but missing in KSO partner's Excel
            }

            $sn = trim((string) ($asset->serial_number ?? ''));
            $model = $asset->model ?: $asset->name;

            // Make 2 valid SN items have slightly different model names in KSO partner sheet -> MODEL MISMATCH
            if ($sn !== '' && $sn !== '-' && $mismatchCount < 2) {
                $model = $model . ' (KSO REV-B)';
                $mismatchCount++;
            }

            $items[] = [
                'row_number' => $rowNo,
                'item_number' => (string) $rowNo,
                'sn' => $sn,
                'model' => $model,
                'brand' => $asset->brand ?: 'Epson',
                'category' => $asset->category ?: 'Printer',
                'location' => $asset->room?->name ?? 'IT',
                'notes' => 'Inventaris rutin Mitra Abadi Q1 2026',
            ];
            $rowNo++;

            // Duplicate 1 valid SN row in KSO partner sheet -> DUPLICATE SN
            if ($sn !== '' && $sn !== '-' && !$duplicatedAdded && $mismatchCount === 2) {
                $items[] = [
                    'row_number' => $rowNo,
                    'item_number' => (string) $rowNo,
                    'sn' => $sn,
                    'model' => $asset->model ?: $asset->name,
                    'brand' => $asset->brand ?: 'Epson',
                    'category' => $asset->category ?: 'Printer',
                    'location' => $asset->room?->name ?? 'IT',
                    'notes' => 'Entri ganda pada lembar kerja mitra KSO',
                ];
                $rowNo++;
                $duplicatedAdded = true;
            }
        }

        // Add 2 new KSO partner devices not yet registered in RSUD system -> MISSING IN SYSTEM
        $items[] = [
            'row_number' => $rowNo,
            'item_number' => (string) $rowNo,
            'sn' => 'X9LU998871',
            'model' => 'EPSON L3210',
            'brand' => 'Epson',
            'category' => 'Printer',
            'location' => 'POLI DALAM',
            'notes' => 'Unit drop baru Mitra Abadi belum masuk buku inventaris IT',
        ];
        $rowNo++;

        $items[] = [
            'row_number' => $rowNo,
            'item_number' => (string) $rowNo,
            'sn' => 'X9LU998872',
            'model' => 'EPSON L121',
            'brand' => 'Epson',
            'category' => 'Printer',
            'location' => 'FARMASI RAJAL',
            'notes' => 'Unit backup Mitra Abadi penempatan Farmasi',
        ];

        $req = Request::create('/api/kso-reconciliation/sessions', 'POST', [
            'kso_partner_id' => $mitraAbadi->id,
            'partner_name' => $mitraAbadi->name,
            'file_name' => 'Data_Inventaris_KSO_MITRA_ABADI_2026.xlsx',
            'period' => 'Semester I - 2026',
            'notes' => 'Rekonsiliasi berkala inventaris perangkat KSO MITRA ABADI dengan database IT Asset Management RSUD Majenang.',
            'items' => $items,
        ]);
        $req->setUserResolver(fn () => $admin);

        app(KsoReconciliationController::class)->storeSession($req);
    }

    private function inferBuilding(string $roomName): string
    {
        if (str_contains($roomName, 'POLI ') || str_contains($roomName, 'TPPRJ') || str_contains($roomName, 'APM')) {
            return 'Gedung Pelayanan Rawat Jalan / Poliklinik';
        }
        if (in_array($roomName, ['ANGGREK', 'ASTER', 'BOUGENVILLE', 'DAHLIA', 'EDELWEIS', 'FLAMBOYAN', 'MELATI', 'MAWAR', 'TERATAI', 'TULIP', 'WIJAYAKUSUMA', 'NURSE STATION', 'SEBELAH MELATI'], true)) {
            return 'Gedung Instalasi Rawat Inap';
        }
        if (str_contains($roomName, 'FARMASI')) {
            return 'Instalasi Farmasi RSUD Majenang';
        }
        if (str_contains($roomName, 'IGD') || in_array($roomName, ['ICU', 'HCU', 'IBS', 'BELAKANG IBS', 'SOKA/NICU/PICU', 'HD BARU', 'HD KRISAN', 'HD LAMA / KRISAN'], true)) {
            return 'Gedung IGD, Bedah Sentral & Perawatan Intensif';
        }
        if (str_contains($roomName, 'LABORAT') || in_array($roomName, ['RADIOLOGI', 'GIZI', 'KONSULTASI GIZI', 'CSSD', 'IPSRS', 'IPJ'], true)) {
            return 'Instalasi Penunjang Medis & Non-Medis';
        }
        if (in_array($roomName, ['SERVER', 'IT'], true)) {
            return 'Unit SIMRS, TI & Ruang Server RSUD Majenang';
        }
        if (str_contains($roomName, 'REKAM MEDIS') || str_contains($roomName, 'TPPRI') || str_contains($roomName, 'KASIR') || in_array($roomName, ['CASEMIX', 'FILLING ATAS', 'CUSTOMER SERVICE'], true)) {
            return 'Instalasi Rekam Medis, Admisi & Billing';
        }
        return 'Gedung Manajemen & Administrasi RSUD Majenang';
    }

    private function extractBrandAndModel(string $name, string $spec, string $category): array
    {
        $upper = strtoupper($name . ' ' . $spec);

        $brands = [
            'EPSON' => 'Epson',
            'CANON' => 'Canon',
            'BROTHER' => 'Brother',
            'HP ' => 'HP',
            'HEWLETT' => 'HP',
            'ZEBRA' => 'Zebra',
            'TSC ' => 'TSC',
            'TOSHIBA' => 'Toshiba',
            'DELL' => 'Dell',
            'ASUS' => 'ASUS',
            'LENOVO' => 'Lenovo',
            'ACER' => 'Acer',
            'MIKROTIK' => 'MikroTik',
            'CISCO' => 'Cisco',
            'TPLINK' => 'TP-Link',
            'TP-LINK' => 'TP-Link',
            'TP LINK' => 'TP-Link',
            'DLINK' => 'D-Link',
            'D-LINK' => 'D-Link',
            'RUIJIE' => 'Ruijie',
            'UBIQUITI' => 'Ubiquiti',
            'UNIFI' => 'Ubiquiti UniFi',
            'INTEL' => 'Intel Custom / Rakitan',
            'AMD' => 'AMD Custom / Rakitan',
            'RYZEN' => 'AMD Ryzen / Rakitan',
        ];

        $detectedBrand = 'RSUD Standard';
        foreach ($brands as $keyword => $brandLabel) {
            if (str_contains($upper, $keyword)) {
                $detectedBrand = $brandLabel;
                break;
            }
        }

        if ($category === 'Komputer / PC' && str_starts_with(strtoupper($name), 'PC-')) {
            if (str_contains($upper, 'AIO')) {
                $model = 'All-in-One PC (' . $name . ')';
            } else {
                $model = 'Desktop Workstation (' . $name . ')';
            }
        } elseif ($category === 'Laptop' && str_starts_with(strtoupper($name), 'LP-')) {
            $model = 'Notebook Operasional (' . $name . ')';
        } else {
            $model = $name;
        }

        return [$detectedBrand, $model];
    }

    private function resolveOriginRoom(string $upperKet, array $roomMap, Room $currentRoom, Room $fallbackItRoom): ?Room
    {
        $mappings = [
            'KEPERAWATAN' => 'KEPERAWATAN',
            'FARMASI RAJAL' => 'FARMASI RAJAL',
            'ADM FARMASI' => 'ADM FARMASI',
            'FARMASI RANAP' => 'FARMASI RANAP',
            'POLI THT' => 'POLI THT',
            'POLI BEDAH' => 'POLI BEDAH',
            'POLBED' => 'POLI BEDAH',
            'ORTOPEDI' => 'POLI ORTHOPEDI',
            'TPPRJ' => 'TPPRJ LT 1',
            'DRIVER' => 'DRIVER',
            'LAB DEPAN' => 'LABORAT DEPAN',
            'IGD' => 'IGD',
            'TULIP' => 'TULIP',
        ];

        foreach ($mappings as $keyword => $targetRoomName) {
            if (str_contains($upperKet, $keyword) && isset($roomMap[$targetRoomName])) {
                $candidate = $roomMap[$targetRoomName];
                if ($candidate->id !== $currentRoom->id) {
                    return $candidate;
                }
            }
        }

        if ($fallbackItRoom->id !== $currentRoom->id) {
            return $fallbackItRoom;
        }

        return null;
    }
}
