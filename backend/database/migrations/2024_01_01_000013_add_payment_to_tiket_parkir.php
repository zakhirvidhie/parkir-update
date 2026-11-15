<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            if (!Schema::hasColumn('tiket_parkir', 'jumlah_bayar')) {
                $table->integer('jumlah_bayar')->default(0)->after('total_bayar');
            }
            if (!Schema::hasColumn('tiket_parkir', 'kembalian')) {
                $table->integer('kembalian')->default(0)->after('jumlah_bayar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            $table->dropColumn(['jumlah_bayar', 'kembalian']);
        });
    }
};

