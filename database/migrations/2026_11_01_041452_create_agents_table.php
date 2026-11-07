<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('kode_agen', 20)->nullable()->unique();
            $table->string('jenis_agen', 20)->default('perorangan');
            // draft | pending | active | inactive_closing | inactive | rejected
            $table->string('status', 30)->default('draft');
            $table->string('kba_nama')->nullable();
            $table->string('kba_phone', 20)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};