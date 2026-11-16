<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Mitra KSO (kso_partners)
        Schema::create('kso_partners', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('contract_number', 120)->nullable();
            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Tabel Sesi Rekonsiliasi (reconciliation_sessions)
        Schema::create('reconciliation_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number', 50)->unique();
            $table->foreignId('kso_partner_id')->nullable()->constrained('kso_partners')->nullOnDelete();
            $table->string('partner_name');
            $table->string('file_name');
            $table->string('period', 100)->nullable();
            $table->unsignedInteger('total_system_assets')->default(0);
            $table->unsignedInteger('total_kso_items')->default(0);
            $table->unsignedInteger('valid_kso_items')->default(0);
            $table->unsignedInteger('invalid_kso_items')->default(0);
            $table->unsignedInteger('match_count')->default(0);
            $table->unsignedInteger('model_mismatch_count')->default(0);
            $table->unsignedInteger('missing_in_system_count')->default(0);
            $table->unsignedInteger('missing_in_kso_count')->default(0);
            $table->unsignedInteger('duplicate_sn_count')->default(0);
            $table->unsignedInteger('invalid_data_count')->default(0);
            $table->unsignedInteger('manual_review_count')->default(0);
            $table->enum('status', ['Draft', 'Dalam Proses', 'Selesai'])->default('Dalam Proses');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['kso_partner_id', 'status']);
            $table->index('created_at');
        });

        // 3. Tabel Data Mentah Import KSO (reconciliation_imports)
        Schema::create('reconciliation_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_session_id')->constrained('reconciliation_sessions')->cascadeOnDelete();
            $table->unsignedInteger('row_number')->default(1);
            $table->string('item_number', 50)->nullable();
            $table->string('raw_sn', 150)->nullable();
            $table->string('normalized_sn', 150)->nullable()->index();
            $table->string('raw_model', 255)->nullable();
            $table->string('normalized_model', 255)->nullable()->index();
            $table->string('brand', 150)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('location', 255)->nullable();
            $table->text('notes')->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('validation_note', 255)->nullable();
            $table->timestamps();
        });

        // 4. Tabel Hasil Pencocokan Rekonsiliasi (reconciliation_results)
        Schema::create('reconciliation_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_session_id')->constrained('reconciliation_sessions')->cascadeOnDelete();
            $table->foreignId('reconciliation_import_id')->nullable()->constrained('reconciliation_imports')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->unsignedInteger('row_number')->default(1);

            // Snapshot Data Mitra KSO
            $table->string('kso_sn', 150)->nullable();
            $table->string('kso_model', 255)->nullable();
            $table->string('kso_brand', 150)->nullable();
            $table->string('kso_category', 100)->nullable();
            $table->string('kso_location', 255)->nullable();
            $table->text('kso_notes')->nullable();

            // Snapshot Data Sistem Aplikasi
            $table->string('system_asset_code', 50)->nullable();
            $table->string('system_name', 255)->nullable();
            $table->string('system_sn', 150)->nullable();
            $table->string('system_model', 255)->nullable();
            $table->string('system_brand', 150)->nullable();
            $table->string('system_category', 100)->nullable();
            $table->string('system_location', 255)->nullable();
            $table->string('system_ownership', 80)->nullable();
            $table->string('system_partner_name', 150)->nullable();
            $table->text('system_notes')->nullable();

            // Status Rekonsiliasi (Exact 7 Statuses from Section 3 & 4)
            $table->enum('status', [
                'MATCH',
                'MODEL MISMATCH',
                'MISSING IN SYSTEM',
                'MISSING IN KSO',
                'DUPLICATE SN',
                'INVALID DATA',
                'NEEDS MANUAL REVIEW',
            ])->index();

            // Rincian Perbedaan & Kandidat Pencocokan
            $table->json('differences')->nullable();
            $table->json('candidates')->nullable();

            // Status Tindakan Rekonsiliasi (Section 6)
            $table->string('action_status', 100)->default('Belum Ditindaklanjuti');
            $table->text('action_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['reconciliation_session_id', 'status']);
        });

        // 5. Tabel Audit Log Tindakan & Koreksi Rekonsiliasi (reconciliation_audit_logs)
        Schema::create('reconciliation_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_session_id')->constrained('reconciliation_sessions')->cascadeOnDelete();
            $table->foreignId('reconciliation_result_id')->nullable()->constrained('reconciliation_results')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->string('action_type', 80);
            $table->text('description');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_audit_logs');
        Schema::dropIfExists('reconciliation_results');
        Schema::dropIfExists('reconciliation_imports');
        Schema::dropIfExists('reconciliation_sessions');
        Schema::dropIfExists('kso_partners');
    }
};

