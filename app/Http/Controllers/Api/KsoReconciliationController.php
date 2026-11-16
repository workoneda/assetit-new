<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\KsoPartner;
use App\Models\ReconciliationAuditLog;
use App\Models\ReconciliationImport;
use App\Models\ReconciliationResult;
use App\Models\ReconciliationSession;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class KsoReconciliationController extends Controller
{
    /**
     * Ensure existing partner names in assets are registered in kso_partners.
     */
    private function syncPartnersFromAssets(): void
    {
        $distinctPartners = Asset::whereNotNull('partner_name')
            ->where('partner_name', '!=', '')
            ->select('partner_name', 'ownership')
            ->distinct()
            ->get();

        foreach ($distinctPartners as $row) {
            $name = trim($row->partner_name);
            if (!$name) {
                continue;
            }
            $exists = KsoPartner::whereRaw('UPPER(name) = ?', [strtoupper($name)])->exists();
            if (!$exists) {
                $count = KsoPartner::count() + 1;
                KsoPartner::create([
                    'code' => 'KSO-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'contract_number' => 'KSO/RSUD-MJN/' . date('Y') . '/0' . $count,
                    'contact_person' => 'Tim Teknis ' . $name,
                    'notes' => "Mitra {$row->ownership} terdaftar pada inventaris RSUD Majenang",
                    'is_active' => true,
                ]);
            }
        }
    }

    public static function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $cleaned = preg_replace('/\s+/', ' ', trim($value));
        $upper = strtoupper((string) $cleaned);
        if (in_array($upper, ['-', '--', 'N/A', 'NA', 'NULL', 'NONE', 'KOSONG', 'TIDAK ADA', '0'], true)) {
            return '';
        }
        return $upper;
    }

    public static function buildFieldComparison(
        ?string $ksoSn,
        ?string $sysSn,
        ?string $ksoModel,
        ?string $sysModel,
        ?string $ksoBrand,
        ?string $sysBrand,
        ?string $ksoCat,
        ?string $sysCat,
        ?string $ksoLoc,
        ?string $sysLoc
    ): array {
        $compare = function (?string $left, ?string $right): string {
            $l = self::normalizeText($left);
            $r = self::normalizeText($right);
            if ($l === '' || $r === '') {
                return 'empty'; // Kuning: data kosong atau perlu pemeriksaan
            }
            return $l === $r ? 'match' : 'mismatch'; // Hijau jika sesuai, Merah jika berbeda
        };

        return [
            'serial_number' => $compare($ksoSn, $sysSn),
            'model' => $compare($ksoModel, $sysModel),
            'brand' => $compare($ksoBrand, $sysBrand),
            'category' => $compare($ksoCat, $sysCat),
            'location' => $compare($ksoLoc, $sysLoc),
        ];
    }

    /**
     * GET /api/kso-reconciliation
     * Returns partners, sessions history, active/latest session details & dynamic dashboard stats.
     */
    public function index(Request $request): JsonResponse
    {
        $this->syncPartnersFromAssets();

        $partners = KsoPartner::orderBy('name')->get();

        $sessionsQuery = ReconciliationSession::with(['creator:id,name,role', 'partner:id,code,name']);

        if ($partnerId = $request->input('kso_partner_id')) {
            $sessionsQuery->where('kso_partner_id', $partnerId);
        }
        if ($period = trim((string) $request->input('period', ''))) {
            $sessionsQuery->where('period', 'like', "%{$period}%");
        }

        $sessions = $sessionsQuery->orderByDesc('id')->get();

        $selectedSessionId = $request->input('session_id') ?: ($sessions->first()?->id);
        $activeSession = null;
        $results = [];
        $auditLogs = [];

        if ($selectedSessionId) {
            $activeSession = ReconciliationSession::with(['creator:id,name,role', 'partner'])
                ->find($selectedSessionId);

            if ($activeSession) {
                $resultsQuery = ReconciliationResult::with(['asset.room', 'resolver:id,name'])
                    ->where('reconciliation_session_id', $activeSession->id);

                if ($statusFilter = $request->input('status')) {
                    $resultsQuery->where('status', $statusFilter);
                }

                if ($actionFilter = $request->input('action_status')) {
                    $resultsQuery->where('action_status', $actionFilter);
                }

                if ($search = trim((string) $request->input('search', ''))) {
                    $resultsQuery->where(function ($q) use ($search) {
                        $q->where('kso_sn', 'like', "%{$search}%")
                          ->orWhere('kso_model', 'like', "%{$search}%")
                          ->orWhere('system_sn', 'like', "%{$search}%")
                          ->orWhere('system_model', 'like', "%{$search}%")
                          ->orWhere('system_asset_code', 'like', "%{$search}%")
                          ->orWhere('system_name', 'like', "%{$search}%")
                          ->orWhere('kso_location', 'like', "%{$search}%")
                          ->orWhere('system_location', 'like', "%{$search}%");
                    });
                }

                $results = $resultsQuery->orderBy('row_number')->orderBy('id')->get();
                $auditLogs = ReconciliationAuditLog::with('user:id,name,role')
                    ->where('reconciliation_session_id', $activeSession->id)
                    ->latest()
                    ->take(50)
                    ->get();
            }
        }

        $totalKsoAssetsInDb = Asset::where('ownership', '!=', 'Milik RSUD')->count();

        // 7 Dynamic Stat Cards (Section 1)
        $stats = [
            'total_system_assets' => $activeSession ? $activeSession->total_system_assets : $totalKsoAssetsInDb,
            'total_kso_items' => $activeSession ? $activeSession->total_kso_items : 0,
            'match_count' => $activeSession ? $activeSession->match_count : 0,
            'missing_in_system_count' => $activeSession ? $activeSession->missing_in_system_count : 0,
            'missing_in_kso_count' => $activeSession ? $activeSession->missing_in_kso_count : 0,
            'data_mismatch_count' => $activeSession ? $activeSession->model_mismatch_count : 0,
            'duplicate_sn_count' => $activeSession ? $activeSession->duplicate_sn_count : 0,
            'invalid_data_count' => $activeSession ? $activeSession->invalid_data_count : 0,
            'manual_review_count' => $activeSession ? $activeSession->manual_review_count : 0,
        ];

        return response()->json([
            'partners' => $partners,
            'sessions' => $sessions,
            'active_session' => $activeSession,
            'results' => $results,
            'audit_logs' => $auditLogs,
            'stats' => $stats,
            'rooms' => Room::select('id', 'code', 'name', 'building')->orderBy('name')->get(),
            'next_reconciliation_number' => ReconciliationSession::generateNextNumber(),
        ]);
    }

    /**
     * POST /api/kso-reconciliation/partners
     */
    public function storePartner(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contract_number' => ['nullable', 'string', 'max:120'],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $count = KsoPartner::count() + 1;
        $partner = KsoPartner::create([
            'code' => 'KSO-' . str_pad((string) $count, 3, '0', STR_PAD_LEFT),
            'name' => trim($validated['name']),
            'contract_number' => $validated['contract_number'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'email' => $validated['email'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => "Mitra KSO {$partner->name} berhasil ditambahkan.",
            'partner' => $partner,
        ], 201);
    }

    /**
     * POST /api/kso-reconciliation/sessions
     * Imports mapped KSO rows into temporary/snapshot tables and executes the exact 6-step matching algorithm
     * WITHOUT modifying any data in the main `assets` table.
     */
    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kso_partner_id' => ['nullable', 'exists:kso_partners,id'],
            'partner_name' => ['required', 'string', 'max:255'],
            'file_name' => ['required', 'string', 'max:255'],
            'period' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.row_number' => ['nullable', 'integer'],
            'items.*.item_number' => ['nullable'],
            'items.*.sn' => ['nullable'],
            'items.*.model' => ['nullable'],
            'items.*.brand' => ['nullable'],
            'items.*.category' => ['nullable'],
            'items.*.location' => ['nullable'],
            'items.*.notes' => ['nullable'],
            'items.*.raw_payload' => ['nullable', 'array'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $partnerName = trim($validated['partner_name']);
            $partnerId = $validated['kso_partner_id'] ?? null;

            if (!$partnerId && $partnerName !== 'Semua Mitra KSO') {
                $partner = KsoPartner::firstOrCreate(
                    ['name' => $partnerName],
                    ['code' => 'KSO-' . str_pad((string) (KsoPartner::count() + 1), 3, '0', STR_PAD_LEFT)]
                );
                $partnerId = $partner->id;
            }

            // Query system KSO assets in scope
            $systemQuery = Asset::with('room:id,code,name,building')
                ->where('ownership', '!=', 'Milik RSUD');

            if ($partnerName !== '' && $partnerName !== 'Semua Mitra KSO') {
                $systemQuery->whereRaw('UPPER(partner_name) = ?', [strtoupper($partnerName)]);
            }

            $systemAssets = $systemQuery->get();

            // Create Reconciliation Session Snapshot
            $session = ReconciliationSession::create([
                'reconciliation_number' => ReconciliationSession::generateNextNumber(),
                'kso_partner_id' => $partnerId,
                'partner_name' => $partnerName,
                'file_name' => $validated['file_name'],
                'period' => $validated['period'] ?: date('F Y'),
                'total_system_assets' => $systemAssets->count(),
                'total_kso_items' => count($validated['items']),
                'status' => 'Dalam Proses',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()?->id,
            ]);

            // Step 1: Save all raw imported rows to `reconciliation_imports` & count SN frequencies
            $importModels = [];
            $ksoSnFrequency = [];
            $validCount = 0;
            $invalidCount = 0;

            foreach ($validated['items'] as $idx => $item) {
                $rawSn = isset($item['sn']) ? trim((string) $item['sn']) : '';
                $rawModel = isset($item['model']) ? trim((string) $item['model']) : '';
                $normSn = self::normalizeText($rawSn);
                $normModel = self::normalizeText($rawModel);

                $isValid = $normSn !== '';
                if ($isValid) {
                    $validCount++;
                    $ksoSnFrequency[$normSn] = ($ksoSnFrequency[$normSn] ?? 0) + 1;
                } else {
                    $invalidCount++;
                }

                $importModels[] = ReconciliationImport::create([
                    'reconciliation_session_id' => $session->id,
                    'row_number' => $item['row_number'] ?? ($idx + 1),
                    'item_number' => isset($item['item_number']) ? (string) $item['item_number'] : (string) ($idx + 1),
                    'raw_sn' => $rawSn !== '' ? $rawSn : null,
                    'normalized_sn' => $normSn !== '' ? $normSn : null,
                    'raw_model' => $rawModel !== '' ? $rawModel : null,
                    'normalized_model' => $normModel !== '' ? $normModel : null,
                    'brand' => isset($item['brand']) && trim((string) $item['brand']) !== '' ? trim((string) $item['brand']) : null,
                    'category' => isset($item['category']) && trim((string) $item['category']) !== '' ? trim((string) $item['category']) : null,
                    'location' => isset($item['location']) && trim((string) $item['location']) !== '' ? trim((string) $item['location']) : null,
                    'notes' => isset($item['notes']) && trim((string) $item['notes']) !== '' ? trim((string) $item['notes']) : null,
                    'raw_payload' => $item['raw_payload'] ?? null,
                    'is_valid' => $isValid,
                    'validation_note' => $isValid ? 'Valid' : 'Serial Number (SN) kosong',
                ]);
            }

            // Index System Assets by Normalized SN & count system SN frequency
            $systemBySn = [];
            $systemSnFrequency = [];
            foreach ($systemAssets as $sysAsset) {
                $sysNormSn = self::normalizeText($sysAsset->serial_number);
                if ($sysNormSn !== '') {
                    $systemBySn[$sysNormSn][] = $sysAsset;
                    $systemSnFrequency[$sysNormSn] = ($systemSnFrequency[$sysNormSn] ?? 0) + 1;
                }
            }

            $matchedSystemAssetIds = [];
            $counters = [
                'MATCH' => 0,
                'MODEL MISMATCH' => 0,
                'MISSING IN SYSTEM' => 0,
                'MISSING IN KSO' => 0,
                'DUPLICATE SN' => 0,
                'INVALID DATA' => 0,
                'NEEDS MANUAL REVIEW' => 0,
            ];

            // Step 2: Evaluate each KSO imported row according to Section 3 matching rules
            foreach ($importModels as $imp) {
                $normSn = $imp->normalized_sn ?? '';
                $normModel = $imp->normalized_model ?? '';

                // Case A: SN is empty (SN kosong)
                if ($normSn === '') {
                    // Check if we can offer candidates based on Model and Brand (Section 3: NEEDS MANUAL REVIEW)
                    $candidates = [];
                    if ($normModel !== '') {
                        foreach ($systemAssets as $cand) {
                            $candModel = self::normalizeText($cand->model ?: $cand->name);
                            $candBrand = self::normalizeText($cand->brand);
                            $impBrand = self::normalizeText($imp->brand);

                            if (
                                ($candModel !== '' && ($candModel === $normModel || str_contains($candModel, $normModel) || str_contains($normModel, $candModel))) ||
                                ($impBrand !== '' && $candBrand === $impBrand && $candModel === $normModel)
                            ) {
                                $candidates[] = [
                                    'asset_id' => $cand->id,
                                    'asset_code' => $cand->asset_code,
                                    'name' => $cand->name,
                                    'serial_number' => $cand->serial_number,
                                    'model' => $cand->model,
                                    'brand' => $cand->brand,
                                    'category' => $cand->category,
                                    'location' => $cand->room?->name,
                                ];
                            }
                            if (count($candidates) >= 5) {
                                break;
                            }
                        }
                    }

                    $status = count($candidates) > 0 ? 'NEEDS MANUAL REVIEW' : 'INVALID DATA';
                    $counters[$status]++;

                    ReconciliationResult::create([
                        'reconciliation_session_id' => $session->id,
                        'reconciliation_import_id' => $imp->id,
                        'asset_id' => null,
                        'row_number' => $imp->row_number,
                        'kso_sn' => $imp->raw_sn,
                        'kso_model' => $imp->raw_model,
                        'kso_brand' => $imp->brand,
                        'kso_category' => $imp->category,
                        'kso_location' => $imp->location,
                        'kso_notes' => $imp->notes,
                        'status' => $status,
                        'differences' => self::buildFieldComparison(
                            $imp->raw_sn,
                            null,
                            $imp->raw_model,
                            null,
                            $imp->brand,
                            null,
                            $imp->category,
                            null,
                            $imp->location,
                            null
                        ),
                        'candidates' => $candidates,
                        'action_status' => 'Belum Ditindaklanjuti',
                    ]);
                    continue;
                }

                // Case B: Duplicate SN (SN yang sama ditemukan lebih dari satu kali di file KSO atau di DB sistem)
                $isDuplicateInKso = ($ksoSnFrequency[$normSn] ?? 0) > 1;
                $isDuplicateInSystem = ($systemSnFrequency[$normSn] ?? 0) > 1;

                if ($isDuplicateInKso || $isDuplicateInSystem) {
                    $matchedList = $systemBySn[$normSn] ?? [];
                    $primaryAsset = $matchedList[0] ?? null;
                    if ($primaryAsset) {
                        $matchedSystemAssetIds[$primaryAsset->id] = true;
                    }

                    $duplicateCandidates = [];
                    foreach ($matchedList as $dupSys) {
                        $duplicateCandidates[] = [
                            'source' => 'SYSTEM',
                            'asset_id' => $dupSys->id,
                            'asset_code' => $dupSys->asset_code,
                            'name' => $dupSys->name,
                            'serial_number' => $dupSys->serial_number,
                            'model' => $dupSys->model,
                            'brand' => $dupSys->brand,
                            'location' => $dupSys->room?->name,
                        ];
                    }

                    $counters['DUPLICATE SN']++;

                    ReconciliationResult::create([
                        'reconciliation_session_id' => $session->id,
                        'reconciliation_import_id' => $imp->id,
                        'asset_id' => $primaryAsset?->id,
                        'row_number' => $imp->row_number,
                        'kso_sn' => $imp->raw_sn,
                        'kso_model' => $imp->raw_model,
                        'kso_brand' => $imp->brand,
                        'kso_category' => $imp->category,
                        'kso_location' => $imp->location,
                        'kso_notes' => $imp->notes,
                        'system_asset_code' => $primaryAsset?->asset_code,
                        'system_name' => $primaryAsset?->name,
                        'system_sn' => $primaryAsset?->serial_number,
                        'system_model' => $primaryAsset?->model,
                        'system_brand' => $primaryAsset?->brand,
                        'system_category' => $primaryAsset?->category,
                        'system_location' => $primaryAsset?->room?->name,
                        'system_ownership' => $primaryAsset?->ownership,
                        'system_partner_name' => $primaryAsset?->partner_name,
                        'system_notes' => $primaryAsset?->notes,
                        'status' => 'DUPLICATE SN',
                        'differences' => self::buildFieldComparison(
                            $imp->raw_sn,
                            $primaryAsset?->serial_number,
                            $imp->raw_model,
                            $primaryAsset?->model,
                            $imp->brand,
                            $primaryAsset?->brand,
                            $imp->category,
                            $primaryAsset?->category,
                            $imp->location,
                            $primaryAsset?->room?->name
                        ),
                        'candidates' => $duplicateCandidates,
                        'action_status' => 'Belum Ditindaklanjuti',
                    ]);
                    continue;
                }

                // Case C: Unique non-empty SN
                $sysAsset = $systemBySn[$normSn][0] ?? null;

                if (!$sysAsset) {
                    // 3. SN hanya ditemukan pada data KSO = MISSING IN SYSTEM
                    $counters['MISSING IN SYSTEM']++;

                    ReconciliationResult::create([
                        'reconciliation_session_id' => $session->id,
                        'reconciliation_import_id' => $imp->id,
                        'asset_id' => null,
                        'row_number' => $imp->row_number,
                        'kso_sn' => $imp->raw_sn,
                        'kso_model' => $imp->raw_model,
                        'kso_brand' => $imp->brand,
                        'kso_category' => $imp->category,
                        'kso_location' => $imp->location,
                        'kso_notes' => $imp->notes,
                        'status' => 'MISSING IN SYSTEM',
                        'differences' => self::buildFieldComparison(
                            $imp->raw_sn,
                            null,
                            $imp->raw_model,
                            null,
                            $imp->brand,
                            null,
                            $imp->category,
                            null,
                            $imp->location,
                            null
                        ),
                        'action_status' => 'Belum Ditindaklanjuti',
                    ]);
                    continue;
                }

                // Mark system asset as matched
                $matchedSystemAssetIds[$sysAsset->id] = true;

                $sysNormModel = self::normalizeText($sysAsset->model ?: $sysAsset->name);

                // 1. SN sama dan Model sama = MATCH
                // 2. SN sama tetapi Model berbeda = MODEL MISMATCH
                $isModelMatch = ($normModel !== '' && $sysNormModel !== '' && $normModel === $sysNormModel);
                $status = $isModelMatch ? 'MATCH' : 'MODEL MISMATCH';
                $counters[$status]++;

                ReconciliationResult::create([
                    'reconciliation_session_id' => $session->id,
                    'reconciliation_import_id' => $imp->id,
                    'asset_id' => $sysAsset->id,
                    'row_number' => $imp->row_number,
                    'kso_sn' => $imp->raw_sn,
                    'kso_model' => $imp->raw_model,
                    'kso_brand' => $imp->brand,
                    'kso_category' => $imp->category,
                    'kso_location' => $imp->location,
                    'kso_notes' => $imp->notes,
                    'system_asset_code' => $sysAsset->asset_code,
                    'system_name' => $sysAsset->name,
                    'system_sn' => $sysAsset->serial_number,
                    'system_model' => $sysAsset->model,
                    'system_brand' => $sysAsset->brand,
                    'system_category' => $sysAsset->category,
                    'system_location' => $sysAsset->room?->name,
                    'system_ownership' => $sysAsset->ownership,
                    'system_partner_name' => $sysAsset->partner_name,
                    'system_notes' => $sysAsset->notes,
                    'status' => $status,
                    'differences' => self::buildFieldComparison(
                        $imp->raw_sn,
                        $sysAsset->serial_number,
                        $imp->raw_model,
                        $sysAsset->model,
                        $imp->brand,
                        $sysAsset->brand,
                        $imp->category,
                        $sysAsset->category,
                        $imp->location,
                        $sysAsset->room?->name
                    ),
                    'action_status' => 'Belum Ditindaklanjuti',
                ]);
            }

            // Step 3: 4. SN hanya ditemukan pada database aplikasi = MISSING IN KSO
            $extraRowNum = count($importModels) + 1;
            foreach ($systemAssets as $sysAsset) {
                if (isset($matchedSystemAssetIds[$sysAsset->id])) {
                    continue;
                }

                $counters['MISSING IN KSO']++;

                ReconciliationResult::create([
                    'reconciliation_session_id' => $session->id,
                    'reconciliation_import_id' => null,
                    'asset_id' => $sysAsset->id,
                    'row_number' => $extraRowNum++,
                    'kso_sn' => null,
                    'kso_model' => null,
                    'kso_brand' => null,
                    'kso_category' => null,
                    'kso_location' => null,
                    'kso_notes' => null,
                    'system_asset_code' => $sysAsset->asset_code,
                    'system_name' => $sysAsset->name,
                    'system_sn' => $sysAsset->serial_number,
                    'system_model' => $sysAsset->model,
                    'system_brand' => $sysAsset->brand,
                    'system_category' => $sysAsset->category,
                    'system_location' => $sysAsset->room?->name,
                    'system_ownership' => $sysAsset->ownership,
                    'system_partner_name' => $sysAsset->partner_name,
                    'system_notes' => $sysAsset->notes,
                    'status' => 'MISSING IN KSO',
                    'differences' => self::buildFieldComparison(
                        null,
                        $sysAsset->serial_number,
                        null,
                        $sysAsset->model,
                        null,
                        $sysAsset->brand,
                        null,
                        $sysAsset->category,
                        null,
                        $sysAsset->room?->name
                    ),
                    'action_status' => 'Belum Ditindaklanjuti',
                ]);
            }

            // Update Session Summary Counters
            $session->update([
                'valid_kso_items' => $validCount,
                'invalid_kso_items' => $invalidCount,
                'match_count' => $counters['MATCH'],
                'model_mismatch_count' => $counters['MODEL MISMATCH'],
                'missing_in_system_count' => $counters['MISSING IN SYSTEM'],
                'missing_in_kso_count' => $counters['MISSING IN KSO'],
                'duplicate_sn_count' => $counters['DUPLICATE SN'],
                'invalid_data_count' => $counters['INVALID DATA'],
                'manual_review_count' => $counters['NEEDS MANUAL REVIEW'],
            ]);

            ReconciliationAuditLog::create([
                'reconciliation_session_id' => $session->id,
                'action_type' => 'session_created',
                'description' => "Sesi rekonsiliasi {$session->reconciliation_number} dibuat dari file '{$session->file_name}' untuk mitra {$session->partner_name}. Match: {$counters['MATCH']}, Model Mismatch: {$counters['MODEL MISMATCH']}, Missing in System: {$counters['MISSING IN SYSTEM']}, Missing in KSO: {$counters['MISSING IN KSO']}, Duplicate SN: {$counters['DUPLICATE SN']}.",
                'after_values' => $counters,
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => "Proses rekonsiliasi {$session->reconciliation_number} selesai dianalisis. Data aset utama tidak diubah secara otomatis.",
                'session' => $session,
            ], 201);
        });
    }

    /**
     * POST /api/kso-reconciliation/results/{result}/resolve
     * Executes explicit user-confirmed actions on a reconciliation result (Section 6 & Section 10).
     */
    public function resolveResult(Request $request, ReconciliationResult $result): JsonResponse
    {
        $validated = $request->validate([
            'action' => [
                'required',
                Rule::in([
                    'verify_match',
                    'keep_system',
                    'update_system',
                    'add_to_directory',
                    'flag_confirm_kso',
                    'manual_review',
                ]),
            ],
            'action_note' => ['nullable', 'string', 'max:500'],
            // Fields for add_to_directory (MISSING IN SYSTEM)
            'name' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:150'],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'pic' => ['nullable', 'string', 'max:255'],
            'ownership' => ['nullable', 'string', 'max:80'],
            'partner_name' => ['nullable', 'string', 'max:150'],
            'condition' => ['nullable', Rule::in(['Baik', 'Maintenance', 'Rusak'])],
            'acquisition_year' => ['nullable', 'integer'],
            'specifications' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $result, $validated) {
            $user = $request->user();
            $action = $validated['action'];
            $note = trim((string) ($validated['action_note'] ?? ''));

            // 1. MATCH -> Tandai sebagai sudah diverifikasi
            if ($action === 'verify_match') {
                $result->update([
                    'action_status' => 'Terverifikasi',
                    'action_note' => $note ?: 'Data SN dan Model telah sesuai dan diverifikasi petugas IT.',
                    'resolved_by' => $user?->id,
                    'resolved_at' => now(),
                ]);

                ReconciliationAuditLog::create([
                    'reconciliation_session_id' => $result->reconciliation_session_id,
                    'reconciliation_result_id' => $result->id,
                    'asset_id' => $result->asset_id,
                    'action_type' => 'verify_match',
                    'description' => "Verifikasi aset cocok (MATCH) SN {$result->kso_sn} [{$result->system_asset_code}].",
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'message' => "Aset {$result->system_asset_code} (SN: {$result->kso_sn}) berhasil ditandai Terverifikasi.",
                    'result' => $result->fresh(['asset.room', 'resolver']),
                ]);
            }

            // 2. MODEL MISMATCH -> Pilihan 1: Pertahankan data sistem
            if ($action === 'keep_system') {
                $result->update([
                    'action_status' => 'Data Sistem Dipertahankan',
                    'action_note' => $note ?: 'Data model pada sistem aplikasi dipertahankan setelah verifikasi fisik.',
                    'resolved_by' => $user?->id,
                    'resolved_at' => now(),
                ]);

                ReconciliationAuditLog::create([
                    'reconciliation_session_id' => $result->reconciliation_session_id,
                    'reconciliation_result_id' => $result->id,
                    'asset_id' => $result->asset_id,
                    'action_type' => 'keep_system',
                    'description' => "Mempertahankan model sistem ({$result->system_model}) untuk SN {$result->system_sn}. Alasan: " . ($note ?: 'Verifikasi fisik sesuai sistem'),
                    'before_values' => ['system_model' => $result->system_model, 'kso_model' => $result->kso_model],
                    'after_values' => ['system_model' => $result->system_model],
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'message' => "Data sistem untuk aset {$result->system_asset_code} dipertahankan.",
                    'result' => $result->fresh(['asset.room', 'resolver']),
                ]);
            }

            // 3. MODEL MISMATCH -> Pilihan 2: Perbarui data sistem setelah diverifikasi (Wajib alasan koreksi)
            if ($action === 'update_system') {
                if ($note === '') {
                    return response()->json([
                        'message' => 'Alasan koreksi wajib diisi sebelum memperbarui data aset utama.',
                    ], 422);
                }

                $asset = Asset::lockForUpdate()->findOrFail($result->asset_id);
                $before = [
                    'model' => $asset->model,
                    'brand' => $asset->brand,
                    'notes' => $asset->notes,
                ];

                $newModel = trim((string) ($validated['model'] ?? $result->kso_model ?: $asset->model));
                $newBrand = trim((string) ($validated['brand'] ?? $result->kso_brand ?: $asset->brand));

                $asset->update([
                    'model' => $newModel,
                    'brand' => $newBrand,
                    'updated_by' => $user?->id,
                ]);

                $after = [
                    'model' => $asset->model,
                    'brand' => $asset->brand,
                    'notes' => $asset->notes,
                ];

                $result->update([
                    'action_status' => 'Data Sistem Diperbarui',
                    'action_note' => $note,
                    'resolved_by' => $user?->id,
                    'resolved_at' => now(),
                ]);

                AssetHistory::create([
                    'asset_id' => $asset->id,
                    'user_id' => $user?->id,
                    'action' => 'kso_reconciliation_update',
                    'description' => "Koreksi model/merk aset melalui Rekonsiliasi KSO ({$result->session?->reconciliation_number}). Model: '{$before['model']}' -> '{$after['model']}'. Alasan: {$note}",
                    'changes' => ['before' => $before, 'after' => $after, 'reason' => $note],
                ]);

                ReconciliationAuditLog::create([
                    'reconciliation_session_id' => $result->reconciliation_session_id,
                    'reconciliation_result_id' => $result->id,
                    'asset_id' => $asset->id,
                    'action_type' => 'update_system',
                    'description' => "Memperbarui data aset {$asset->asset_code} (SN: {$asset->serial_number}) mengikuti verifikasi KSO. Alasan: {$note}",
                    'before_values' => $before,
                    'after_values' => $after,
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'message' => "Data aset {$asset->asset_code} berhasil diperbarui dan dicatat dalam audit log.",
                    'result' => $result->fresh(['asset.room', 'resolver']),
                ]);
            }

            // 4. MISSING IN SYSTEM -> Tambahkan ke Asset Directory setelah dilengkapi & dikonfirmasi pengguna
            if ($action === 'add_to_directory') {
                if (empty($validated['room_id']) || empty($validated['name']) || empty($validated['category'])) {
                    return response()->json([
                        'message' => 'Nama perangkat, Kategori, dan Ruangan wajib dilengkapi sebelum menambahkan aset ke Asset Directory.',
                    ], 422);
                }

                $session = ReconciliationSession::find($result->reconciliation_session_id);
                $room = Room::findOrFail($validated['room_id']);

                $newAsset = Asset::create([
                    'asset_code' => Asset::generateNextCode(),
                    'name' => trim($validated['name']),
                    'category' => $validated['category'],
                    'brand' => ($validated['brand'] ?? null) ?: ($result->kso_brand ?: 'Mitra KSO'),
                    'model' => ($validated['model'] ?? null) ?: ($result->kso_model ?: $validated['name']),
                    'serial_number' => ($validated['serial_number'] ?? null) ?: $result->kso_sn,
                    'specifications' => $validated['specifications'] ?? null,
                    'room_id' => $room->id,
                    'pic' => ($validated['pic'] ?? null) ?: ($room->pic_name ?: 'Koordinator Ruangan'),
                    'ownership' => ($validated['ownership'] ?? null) ?: 'KSO',
                    'partner_name' => ($validated['partner_name'] ?? null) ?: ($session?->partner_name !== 'Semua Mitra KSO' ? $session?->partner_name : 'MITRA ABADI'),
                    'condition' => ($validated['condition'] ?? null) ?: 'Baik',
                    'acquisition_year' => ($validated['acquisition_year'] ?? null) ?: (int) date('Y'),
                    'notes' => ($validated['notes'] ?? null) ?: ($result->kso_notes ?: "Ditambahkan dari hasil Rekonsiliasi KSO {$session?->reconciliation_number}"),
                    'created_by' => $user?->id,
                    'updated_by' => $user?->id,
                ]);

                $result->update([
                    'asset_id' => $newAsset->id,
                    'system_asset_code' => $newAsset->asset_code,
                    'system_name' => $newAsset->name,
                    'system_sn' => $newAsset->serial_number,
                    'system_model' => $newAsset->model,
                    'system_brand' => $newAsset->brand,
                    'system_category' => $newAsset->category,
                    'system_location' => $room->name,
                    'system_ownership' => $newAsset->ownership,
                    'system_partner_name' => $newAsset->partner_name,
                    'action_status' => 'Ditambahkan ke Asset Directory',
                    'action_note' => $note ?: "Ditambahkan sebagai aset baru [{$newAsset->asset_code}] di {$room->name}.",
                    'resolved_by' => $user?->id,
                    'resolved_at' => now(),
                ]);

                AssetHistory::create([
                    'asset_id' => $newAsset->id,
                    'user_id' => $user?->id,
                    'action' => 'created_from_kso_reconciliation',
                    'description' => "Aset KSO baru [{$newAsset->asset_code}] ditambahkan melalui Rekonsiliasi KSO ({$session?->reconciliation_number}).",
                    'changes' => $newAsset->only(['asset_code', 'name', 'serial_number', 'model', 'partner_name']),
                ]);

                ReconciliationAuditLog::create([
                    'reconciliation_session_id' => $result->reconciliation_session_id,
                    'reconciliation_result_id' => $result->id,
                    'asset_id' => $newAsset->id,
                    'action_type' => 'add_to_directory',
                    'description' => "Menambahkan aset KSO yang belum terdaftar (SN: {$newAsset->serial_number}) ke Asset Directory dengan kode {$newAsset->asset_code}.",
                    'before_values' => null,
                    'after_values' => $newAsset->only(['asset_code', 'name', 'category', 'brand', 'model', 'serial_number', 'room_id', 'partner_name']),
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'message' => "Aset berhasil ditambahkan ke Asset Directory dengan kode {$newAsset->asset_code}.",
                    'result' => $result->fresh(['asset.room', 'resolver']),
                ]);
            }

            // 5. MISSING IN KSO -> Tandai perlu konfirmasi ke mitra KSO (Tanpa menghapus/mengubah kepemilikan)
            if ($action === 'flag_confirm_kso') {
                $result->update([
                    'action_status' => 'Perlu Konfirmasi Mitra KSO',
                    'action_note' => $note ?: 'Aset tercatat di RSUD namun belum tercantum pada daftar mitra KSO. Perlu konfirmasi berita acara mitra.',
                    'resolved_by' => $user?->id,
                    'resolved_at' => now(),
                ]);

                if ($result->asset_id) {
                    $asset = Asset::find($result->asset_id);
                    if ($asset) {
                        $asset->update([
                            'needs_followup' => true,
                            'followup_note' => 'Rekonsiliasi KSO: Perlu konfirmasi ke mitra KSO (' . ($note ?: 'Tidak ditemukan di daftar KSO terbaru') . ')',
                        ]);
                    }
                }

                ReconciliationAuditLog::create([
                    'reconciliation_session_id' => $result->reconciliation_session_id,
                    'reconciliation_result_id' => $result->id,
                    'asset_id' => $result->asset_id,
                    'action_type' => 'flag_confirm_kso',
                    'description' => "Menandai aset {$result->system_asset_code} (SN: {$result->system_sn}) sebagai Perlu Konfirmasi ke Mitra KSO. Catatan: " . ($note ?: '-'),
                    'user_id' => $user?->id,
                ]);

                return response()->json([
                    'message' => "Aset {$result->system_asset_code} berhasil ditandai untuk konfirmasi ke pihak mitra KSO.",
                    'result' => $result->fresh(['asset.room', 'resolver']),
                ]);
            }

            // 6. DUPLICATE SN / NEEDS MANUAL REVIEW / INVALID DATA -> Pemeriksaan Manual
            $result->update([
                'action_status' => 'Ditinjau Manual',
                'action_note' => $note ?: 'Telah dilakukan pemeriksaan manual oleh petugas IT.',
                'resolved_by' => $user?->id,
                'resolved_at' => now(),
            ]);

            ReconciliationAuditLog::create([
                'reconciliation_session_id' => $result->reconciliation_session_id,
                'reconciliation_result_id' => $result->id,
                'asset_id' => $result->asset_id,
                'action_type' => 'manual_review',
                'description' => "Pemeriksaan manual pada baris #{$result->row_number} ({$result->status}). Catatan: " . ($note ?: '-'),
                'user_id' => $user?->id,
            ]);

            return response()->json([
                'message' => 'Hasil pemeriksaan manual berhasil disimpan.',
                'result' => $result->fresh(['asset.room', 'resolver']),
            ]);
        });
    }

    /**
     * POST /api/kso-reconciliation/sessions/{session}/verify-all-matches
     */
    public function verifyAllMatches(Request $request, ReconciliationSession $session): JsonResponse
    {
        return DB::transaction(function () use ($request, $session) {
            $count = ReconciliationResult::where('reconciliation_session_id', $session->id)
                ->where('status', 'MATCH')
                ->where('action_status', 'Belum Ditindaklanjuti')
                ->update([
                    'action_status' => 'Terverifikasi',
                    'action_note' => 'Terverifikasi otomatis karena SN dan Model cocok 100%.',
                    'resolved_by' => $request->user()?->id,
                    'resolved_at' => now(),
                ]);

            ReconciliationAuditLog::create([
                'reconciliation_session_id' => $session->id,
                'action_type' => 'verify_all_matches',
                'description' => "Verifikasi massal {$count} aset berstatus MATCH pada sesi {$session->reconciliation_number}.",
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => "{$count} aset berstatus MATCH berhasil ditandai Terverifikasi.",
            ]);
        });
    }

    /**
     * POST /api/kso-reconciliation/sessions/{session}/complete
     */
    public function completeSession(Request $request, ReconciliationSession $session): JsonResponse
    {
        $session->update([
            'status' => 'Selesai',
            'completed_at' => now(),
        ]);

        ReconciliationAuditLog::create([
            'reconciliation_session_id' => $session->id,
            'action_type' => 'session_completed',
            'description' => "Sesi rekonsiliasi {$session->reconciliation_number} diselesaikan dan dikunci sebagai snapshot resmi.",
            'user_id' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => "Sesi rekonsiliasi {$session->reconciliation_number} telah ditandai Selesai.",
            'session' => $session,
        ]);
    }
}
