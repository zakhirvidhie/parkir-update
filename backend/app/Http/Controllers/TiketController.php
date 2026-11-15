<?php

namespace App\Http\Controllers;

use App\Models\TiketParkir;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TiketController extends Controller
{
    // =========================================================
    // BUAT TIKET BARU / KENDARAAN MASUK
    // =========================================================
    public function create(Request $request)
    {
        $jenis = strtolower(
            $request->input('jenis_kendaraan', 'motor')
        );

        if (!in_array($jenis, ['mobil', 'motor'])) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis kendaraan harus mobil atau motor.'
            ], 422);
        }

        // Tarif parkir
        $tarif = $jenis === 'mobil'
            ? 5000
            : 3000;

        $waktuMasuk = now();

        // Membuat kode tiket A001, A002, A003, dst.
        $last = TiketParkir::orderByDesc('id')->first();

        if ($last) {
            $angka = intval(
                substr($last->kode_tiket, 1)
            );

            $nomor = $angka + 1;
        } else {
            $nomor = 1;
        }

        $kode = 'A' . str_pad(
            $nomor,
            3,
            '0',
            STR_PAD_LEFT
        );

        // Pastikan kode tidak sama
        while (
            TiketParkir::where(
                'kode_tiket',
                $kode
            )->exists()
        ) {
            $nomor++;

            $kode = 'A' . str_pad(
                $nomor,
                3,
                '0',
                STR_PAD_LEFT
            );
        }

        // Simpan tiket
        $tiket = TiketParkir::create([
            'kode_tiket' => $kode,

            'qr_code' => Str::random(60),

            'jenis_kendaraan' => $jenis,

            'no_plat' => strtoupper(trim($request->input('no_plat', ''))),

            'tarif_per_jam' => $tarif,

            'total_bayar' => 0,

            'status' => 'masuk',

            'waktu_masuk' => $waktuMasuk,

            'waktu_keluar' => null,
        ]);

        return response()->json([
            'success' => true,

            'message' => 'Tiket berhasil dibuat',

            'data' => $tiket
        ], 201);
    }


    // =========================================================
    // SEMUA TIKET
    // =========================================================
    public function index()
    {
        $tiket = TiketParkir::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $tiket
        ]);
    }


    // =========================================================
    // DETAIL TIKET
    // =========================================================
    public function show($id)
    {
        $tiket = TiketParkir::find($id);

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tiket
        ]);
    }


    // =========================================================
    // CARI TIKET BERDASARKAN KODE
    // =========================================================
    public function cariKode($kode)
    {
        $tiket = TiketParkir::where(
            'kode_tiket',
            $kode
        )->first();

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tiket
        ]);
    }


    // =========================================================
    // PROSES KELUAR
    // =========================================================
    public function keluar(Request $request, $id)
    {
        $tiket = TiketParkir::find($id);

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan'
            ], 404);
        }

        // Tidak boleh keluar dua kali
        if (
            $tiket->status === 'keluar' ||
            $tiket->waktu_keluar !== null
        ) {
            return response()->json([
                'success' => false,
                'message' =>
                    'Tiket ini sudah digunakan untuk keluar.',
                'data' => $tiket
            ], 422);
        }

        $waktuKeluar = now();

        // Hitung durasi
        $durasiDetik =
            $waktuKeluar->timestamp -
            $tiket->waktu_masuk->timestamp;

        // Minimal 1 jam
        $jumlahJam = max(
            1,
            ceil($durasiDetik / 3600)
        );

        // Tarif berdasarkan kendaraan
        $tarif = (int) $tiket->tarif_per_jam;

        if ($tarif <= 0) {
            $tarif =
                strtolower(
                    $tiket->jenis_kendaraan
                ) === 'mobil'
                ? 5000
                : 3000;
        }

        $totalBayar =
            $jumlahJam * $tarif;

        // Boleh sesuaikan biaya parkir jika dikirim dari frontend
        if ($request->filled('total_bayar') && (int) $request->input('total_bayar') >= 0) {
            $totalBayar = (int) $request->input('total_bayar');
        }

        // Uang yang dibayar dan kembalian
        $jumlahBayar = $request->filled('jumlah_bayar') ? (int) $request->input('jumlah_bayar') : $totalBayar;
        $kembalian = max(0, $jumlahBayar - $totalBayar);

        // Update data tiket
        $updateData = [
            'status'       => 'keluar',
            'waktu_keluar' => $waktuKeluar,
            'tarif_per_jam'=> $tarif,
            'total_bayar'  => $totalBayar,
            'jumlah_bayar' => $jumlahBayar,
            'kembalian'    => $kembalian,
        ];

        // Simpan no_plat jika dikirim dan belum ada
        $noPlat = strtoupper(trim($request->input('no_plat', '')));
        if ($noPlat) {
            $updateData['no_plat'] = $noPlat;
        }

        $tiket->update($updateData);

        $tiket->refresh();

        return response()->json([
            'success' => true,

            'message' =>
                'Kendaraan berhasil keluar',

            'data' => $tiket,

            'durasi_jam' =>
                $jumlahJam,

            'total_bayar' =>
                $totalBayar,

            'jumlah_bayar' =>
                $jumlahBayar,

            'kembalian' =>
                $kembalian,
        ]);
    }


    // =========================================================
    // HAPUS TIKET
    // =========================================================
    public function destroy($id)
    {
        $tiket = TiketParkir::find($id);

        if (!$tiket) {
            return response()->json([
                'success' => false,
                'message' => 'Tiket tidak ditemukan'
            ], 404);
        }

        $tiket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tiket berhasil dihapus'
        ]);
    }
}