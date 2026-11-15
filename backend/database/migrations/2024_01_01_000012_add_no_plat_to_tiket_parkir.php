<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            if (!Schema::hasColumn('tiket_parkir', 'no_plat')) {
                $table->string('no_plat', 20)->nullable()->after('jenis_kendaraan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tiket_parkir', function (Blueprint $table) {
            $table->dropColumn('no_plat');
        });
    }
};

