<?php

use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AssetTransferController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\KsoReconciliationController;
use App\Http\Controllers\Api\MaintenanceController;
use App\Http\Controllers\Api\NetworkMapController;
use App\Http\Controllers\Api\RecycleBinController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\SystemSettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    // Public Auth Endpoints
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Protected Endpoints
    Route::middleware('auth')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

        // Dashboard Analytics
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Asset Directory & QR Detail
        Route::get('/assets/next-code', [AssetController::class, 'nextCode']);
        Route::post('/assets/import', [AssetController::class, 'import']);
        Route::get('/assets', [AssetController::class, 'index']);
        Route::post('/assets', [AssetController::class, 'store']);
        Route::get('/assets/{idOrCode}', [AssetController::class, 'show']);
        Route::post('/assets/{asset}', [AssetController::class, 'update']);
        Route::put('/assets/{asset}', [AssetController::class, 'update']);
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy']);

        // Master Data Ruangan
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::post('/rooms', [RoomController::class, 'store']);
        Route::put('/rooms/{room}', [RoomController::class, 'update']);
        Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

        // Asset Transfer Management (Pemindahan Aset)
        Route::get('/asset-transfers', [AssetTransferController::class, 'index']);
        Route::post('/asset-transfers', [AssetTransferController::class, 'store']);
        Route::get('/asset-transfers/{idOrNumber}', [AssetTransferController::class, 'show']);
        Route::post('/asset-transfers/{transfer}/complete', [AssetTransferController::class, 'complete']);
        Route::post('/asset-transfers/{transfer}/cancel', [AssetTransferController::class, 'cancel']);

        // KSO Asset Reconciliation (Rekonsiliasi Aset KSO)
        Route::get('/kso-reconciliation', [KsoReconciliationController::class, 'index']);
        Route::post('/kso-reconciliation/partners', [KsoReconciliationController::class, 'storePartner']);
        Route::post('/kso-reconciliation/sessions', [KsoReconciliationController::class, 'storeSession']);
        Route::post('/kso-reconciliation/sessions/{session}/verify-all-matches', [KsoReconciliationController::class, 'verifyAllMatches']);
        Route::post('/kso-reconciliation/sessions/{session}/complete', [KsoReconciliationController::class, 'completeSession']);
        Route::post('/kso-reconciliation/results/{result}/resolve', [KsoReconciliationController::class, 'resolveResult']);

        // Maintenance Radar (Riwayat Pemeliharaan)
        Route::get('/maintenances', [MaintenanceController::class, 'index']);
        Route::post('/maintenances', [MaintenanceController::class, 'store']);
        Route::put('/maintenances/{maintenance}', [MaintenanceController::class, 'update']);
        Route::delete('/maintenances/{maintenance}', [MaintenanceController::class, 'destroy']);

        // IP Network Map
        Route::get('/network-map', [NetworkMapController::class, 'index']);
        Route::post('/network-map/ping-all', [NetworkMapController::class, 'pingAll']);
        Route::post('/network-map/{asset}/ping', [NetworkMapController::class, 'pingDevice']);

        // Laporan & Rekapitulasi
        Route::get('/reports', [ReportController::class, 'index']);

        // Recycle Bin
        Route::get('/recycle-bin', [RecycleBinController::class, 'index']);
        Route::post('/recycle-bin/restore-all', [RecycleBinController::class, 'restoreAll']);
        Route::post('/recycle-bin/{id}/restore', [RecycleBinController::class, 'restore']);
        Route::delete('/recycle-bin/empty/all', [RecycleBinController::class, 'emptyBin']);
        Route::delete('/recycle-bin/{id}', [RecycleBinController::class, 'forceDestroy']);

        // Manajemen Pengguna (Administrator)
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);

        // Pengaturan & Database Backup
        Route::get('/system/info', [SystemSettingController::class, 'info']);
        Route::get('/system/backup', [SystemSettingController::class, 'backupDatabase']);
        Route::post('/system/clear-inventory', [SystemSettingController::class, 'clearInventoryData']);
        Route::post('/system/reseed', [SystemSettingController::class, 'reseedSampleData']);
    });
});

// SPA Catch-All Route (includes QR Scan direct URLs like /assets/AST-0001 or /scan/AST-0001)
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '^(?!api|storage).*$')->name('login');
