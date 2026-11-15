<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();

            $table->string('kode_member')->unique();
            $table->string('token')->unique();

            $table->string('nama_member');
            $table->string('nama_perusahaan');

            $table->decimal('total_harga', 12, 2)->default(0);
            $table->decimal('jumlah_bayar', 12, 2)->default(0);
            $table->decimal('kembalian', 12, 2)->default(0);

            $table->string('status')->default('belum lunas');

            $table->string('bulan_tagihan')->nullable();

            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_expired')->nullable();
            $table->dateTime('tanggal_bayar')->nullable();

            // Untuk fitur parkir
            $table->boolean('sedang_parkir')->default(false);
            $table->dateTime('waktu_masuk_parkir')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};

