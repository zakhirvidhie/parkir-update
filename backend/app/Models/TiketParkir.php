<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiketParkir extends Model
{
    protected $table = 'tiket_parkir';

    protected $fillable = [
        'kode_tiket',
        'qr_code',
        'jenis_kendaraan',
        'no_plat',
        'tarif_per_jam',
        'total_bayar',
        'jumlah_bayar',
        'kembalian',
        'status',
        'waktu_masuk',
        'waktu_keluar',
    ];

    protected $casts = [
        'waktu_masuk' => 'datetime',
        'waktu_keluar' => 'datetime',
        'tarif_per_jam' => 'integer',
        'total_bayar' => 'integer',
    ];
}