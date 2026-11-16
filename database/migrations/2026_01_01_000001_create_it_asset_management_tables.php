<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('building');
            $table->string('pic_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_code', 30)->unique();
            $table->string('name');
            $table->string('category', 80)->index();
            $table->string('brand', 100)->nullable();
            $table->string('model', 150)->nullable();
            $table->string('serial_number', 120)->nullable()->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('mac_address', 30)->nullable();
            $table->string('subnet_mask', 30)->nullable()->default('255.255.255.0');
            $table->string('vlan', 60)->nullable();
            $table->foreignId('uplink_asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->enum('network_status', ['online', 'offline', 'unknown'])->default('online');
            $table->integer('latency_ms')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('specifications')->nullable();
            $table->foreignId('room_id')->constrained('rooms')->restrictOnDelete();
            $table->string('pic')->nullable();
            $table->enum('ownership', ['Milik RSUD', 'KSO', 'Milik Pihak Ketiga'])->default('Milik RSUD')->index();
            $table->string('partner_name')->nullable();
            $table->text('ownership_notes')->nullable();
            $table->enum('condition', ['Baik', 'Rusak', 'Maintenance'])->default('Baik')->index();
            $table->year('acquisition_year')->nullable()->index();
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('needs_followup')->default(false)->index();
            $table->string('followup_note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('asset_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->text('description');
            $table->json('changes')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 40)->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->date('maintenance_date')->index();
            $table->date('completed_date')->nullable();
            $table->string('job_type', 120);
            $table->text('complaint');
            $table->text('action_taken')->nullable();
            $table->string('technician', 120);
            $table->decimal('cost', 15, 2)->nullable()->default(0);
            $table->enum('status', ['Menunggu', 'Sedang Dikerjakan', 'Selesai'])->default('Menunggu')->index();
            $table->enum('condition_after', ['Baik', 'Rusak', 'Maintenance'])->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenances');
        Schema::dropIfExists('asset_histories');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('rooms');
    }
};
