<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\AssetTransfer;
use App\Models\AssetTransferDocument;
use App\Models\Maintenance;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemSettingController extends Controller
{
    public function info(): JsonResponse
    {
        $mysqlVersion = DB::selectOne('SELECT VERSION() as version')?->version ?? 'MySQL 8.x';
        $dbName = DB::connection()->getDatabaseName();

        return response()->json([
            'system' => [
                'app_name' => config('app.name', 'IT Asset Management RSUD'),
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'database_driver' => 'MySQL',
                'mysql_version' => $mysqlVersion,
                'database_name' => $dbName,
                'timezone' => config('app.timezone'),
                'server_time' => now()->format('Y-m-d H:i:s'),
            ],
            'counts' => [
                'users' => User::count(),
                'rooms' => Room::count(),
                'assets_active' => Asset::count(),
                'assets_trashed' => Asset::onlyTrashed()->count(),
                'maintenances' => Maintenance::count(),
                'transfers' => AssetTransfer::count(),
                'histories' => AssetHistory::count(),
            ],
        ]);
    }

    public function backupDatabase(Request $request): StreamedResponse
    {
        $dbName = DB::connection()->getDatabaseName();
        $filename = 'backup_' . $dbName . '_' . date('Y-m-d_His') . '.sql';

        $tables = [
            'users',
            'rooms',
            'assets',
            'asset_histories',
            'maintenances',
            'asset_transfers',
            'asset_transfer_documents',
            'kso_partners',
            'reconciliation_sessions',
            'reconciliation_imports',
            'reconciliation_results',
            'reconciliation_audit_logs',
        ];
        $pdo = DB::connection()->getPdo();

        return response()->streamDownload(function () use ($tables, $dbName, $pdo) {
            echo "-- ==========================================================\n";
            echo "-- IT ASSET MANAGEMENT SYSTEM RSUD - MYSQL DATABASE BACKUP\n";
            echo "-- Database: `{$dbName}`\n";
            echo "-- Generated At: " . now()->toDateTimeString() . "\n";
            echo "-- ==========================================================\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n";
            echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n\n";

            foreach ($tables as $table) {
                echo "-- --------------------------------------------------------\n";
                echo "-- Struktur & Data Tabel `{$table}`\n";
                echo "-- --------------------------------------------------------\n\n";

                $createResult = DB::select("SHOW CREATE TABLE `{$table}`");
                if (!empty($createResult)) {
                    $row = (array) $createResult[0];
                    $createSql = $row['Create Table'] ?? array_values($row)[1] ?? '';
                    echo "DROP TABLE IF EXISTS `{$table}`;\n";
                    echo $createSql . ";\n\n";
                }

                $rows = DB::table($table)->get();
                if ($rows->count() > 0) {
                    foreach ($rows as $record) {
                        $recordArr = (array) $record;
                        $columns = array_map(fn ($col) => "`{$col}`", array_keys($recordArr));
                        $values = array_map(function ($val) use ($pdo) {
                            if ($val === null) {
                                return 'NULL';
                            }
                            return $pdo->quote((string) $val);
                        }, array_values($recordArr));

                        echo "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    echo "\n";
                }
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, [
            'Content-Type' => 'application/sql; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function clearInventoryData(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Hanya Administrator yang dapat mereset data inventaris untuk mode produksi bersih.',
            ], 403);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        AssetTransferDocument::truncate();
        AssetTransfer::truncate();
        Maintenance::truncate();
        AssetHistory::truncate();
        Asset::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return response()->json([
            'message' => 'Seluruh data sampel inventaris dan riwayat pemeliharaan telah dibersihkan. Sistem siap diisi data produksi aktual.',
        ]);
    }

    public function reseedSampleData(Request $request): JsonResponse
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Hanya Administrator yang dapat menjalankan fungsi ini.',
            ], 403);
        }

        $seeder = new \Database\Seeders\DatabaseSeeder();
        $seeder->run();

        return response()->json([
            'message' => 'Data awal rumah sakit berhasil dimuat ulang ke MySQL.',
        ]);
    }
}
