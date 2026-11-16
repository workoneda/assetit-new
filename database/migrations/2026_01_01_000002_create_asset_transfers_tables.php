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
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 40)->unique();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('from_room_id')->constrained('rooms')->restrictOnDelete();
            $table->foreignId('to_room_id')->constrained('rooms')->restrictOnDelete();
            $table->string('previous_pic')->nullable();
            $table->string('new_pic')->nullable();
            $table->dateTime('transfer_date')->index();
            $table->string('transfer_reason', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['Menunggu', 'Selesai', 'Dibatalkan'])->default('Selesai')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_transfer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_transfer_id')->constrained('asset_transfers')->cascadeOnDelete();
            $table->enum('document_type', ['photo_before', 'photo_after', 'supporting_doc']);
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transfer_documents');
        Schema::dropIfExists('asset_transfers');
    }
};

