<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            if (!Schema::hasColumn('tiket_parkir', 'jenis_kendaraan')) {
                $table->string('jenis_kendaraan')->default('motor')->after('qr_code');
            }
            if (!Schema::hasColumn('tiket_parkir', 'tarif_per_jam')) {
                $table->integer('tarif_per_jam')->default(3000)->after('jenis_kendaraan');
            }
            if (!Schema::hasColumn('tiket_parkir', 'total_bayar')) {
                $table->integer('total_bayar')->default(0)->after('tarif_per_jam');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            $table->dropColumn(['jenis_kendaraan', 'tarif_per_jam', 'total_bayar']);
        });
    }
};

