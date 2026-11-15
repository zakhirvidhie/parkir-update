<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class MemberController extends Controller
{
    // =========================================================
    // SEMUA MEMBER
    // =========================================================
    public function index()
    {
        $members = Member::orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'status' => true,
            'data' => $members
        ]);
    }


    // =========================================================
    // TAMBAH MEMBER
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'nama_member' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
            'jumlah_bayar' => 'nullable|numeric|min:0',
        ]);

        $hargaMember = 150000;
        $jumlahBayar = $request->filled('jumlah_bayar') ? (float) $request->jumlah_bayar : $hargaMember;

        if ($jumlahBayar < $hargaMember) {
            $jumlahBayar = $hargaMember;
        }

        $kembalian = $jumlahBayar > $hargaMember ? $jumlahBayar - $hargaMember : 0;
        $tanggalMulai = Carbon::now();
        $tanggalExpired = Carbon::now()->addMonth();
        $tanggalBayar = Carbon::now();

        $member = Member::create([
            'kode_member' => $this->buatKodeMember(),
            'token' => Str::uuid()->toString(),

            'nama_member' => $request->nama_member,
            'nama_perusahaan' => $request->nama_perusahaan,

            'total_harga' => $hargaMember,
            'jumlah_bayar' => $jumlahBayar,
            'kembalian' => $kembalian,

            'status' => 'lunas',

            'bulan_tagihan' => $tanggalMulai->format('Y-m'),

            'tanggal_mulai' => $tanggalMulai,
            'tanggal_expired' => $tanggalExpired,
            'tanggal_bayar' => $tanggalBayar,
        ]);

        return response()->json([
            'success' => true,
            'status' => true,
            'message' => 'Member berhasil ditambahkan dan pembayaran lunas.',
            'data' => $member
        ], 201);
    }


    // =========================================================
    // DETAIL MEMBER
    // =========================================================
    public function show($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $member
        ]);
    }


    // =========================================================
    // UPDATE DATA MEMBER
    // =========================================================
    public function update(Request $request, $id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'nama_member' => 'required|string|max:255',
            'nama_perusahaan' => 'required|string|max:255',
        ]);

        $member->update([
            'nama_member' => $request->nama_member,
            'nama_perusahaan' => $request->nama_perusahaan,
        ]);

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Data member berhasil diperbarui',
            'data' => $member
        ]);
    }


    // =========================================================
    // PEMBAYARAN MEMBER
    // =========================================================
    public function pembayaran(Request $request, $id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $request->validate([
            'jumlah_bayar' => 'required|numeric|min:0',
        ]);

        $jumlahBayar = (float) $request->jumlah_bayar;

        $tagihan = (float) ($member->total_harga ?? 0);

        $kembalian = 0;

        if ($jumlahBayar > $tagihan) {
            $kembalian = $jumlahBayar - $tagihan;
        }

        if ($jumlahBayar >= $tagihan) {
            $status = 'lunas';
            $tanggalBayar = Carbon::now();
        } else {
            $status = 'belum lunas';
            $tanggalBayar = null;
        }

        $member->update([
            'jumlah_bayar' => $jumlahBayar,
            'kembalian' => $kembalian,
            'status' => $status,
            'tanggal_bayar' => $tanggalBayar,
        ]);

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Pembayaran berhasil disimpan',
            'data' => $member
        ]);
    }


    // =========================================================
    // HAPUS MEMBER
    // =========================================================
    public function destroy($id)
    {
        $member = Member::find($id);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak ditemukan'
            ], 404);
        }

        $member->delete();

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil dihapus'
        ]);
    }


    // =========================================================
    // CEK KARTU MEMBER
    // =========================================================
    public function check(Request $request)
    {
        $token = trim($request->input('token', ''));

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token atau kode member wajib diisi'
            ], 422);
        }

        $member = Member::where('token', $token)
            ->orWhere('kode_member', $token)
            ->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu member tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Member ditemukan',
            'data' => $member
        ]);
    }


    // =========================================================
    // MEMBER MASUK PARKIR
    // =========================================================
    public function masuk(Request $request)
    {
        $request->validate([
            'kode_member' => 'required|string'
        ]);

        $kodeMember = trim($request->kode_member);

        // Cari berdasarkan kode member ATAU token
        $member = Member::where('kode_member', $kodeMember)
            ->orWhere('token', $kodeMember)
            ->first();

        // Member tidak ditemukan
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu member tidak ditemukan'
            ], 404);
        }

        // CEK APAKAH MEMBER MASIH PARKIR
        if ((bool) $member->sedang_parkir === true) {
            return response()->json([
                'success' => false,
                'message' => 'Member sedang parkir. Tidak bisa masuk lagi.',
                'data' => $member
            ], 409);
        }

        // Tandai member sedang parkir
        $member->sedang_parkir = true;
        $member->waktu_masuk_parkir = Carbon::now();
        $member->save();

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil masuk parkir',
            'data' => $member
        ]);
    }


    // =========================================================
    // MEMBER KELUAR PARKIR
    // =========================================================
    public function keluar(Request $request)
    {
        $request->validate([
            'kode_member' => 'required|string',
            'no_plat'     => 'nullable|string'
        ]);

        $kodeMember = trim($request->kode_member);

        // Cari berdasarkan kode member ATAU token
        $member = Member::where('kode_member', $kodeMember)
            ->orWhere('token', $kodeMember)
            ->first();

        // Member tidak ditemukan
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Kartu member tidak ditemukan'
            ], 404);
        }

        // CEK APAKAH MEMBER MEMANG SEDANG PARKIR
        if ((bool) $member->sedang_parkir === false) {
            return response()->json([
                'success' => false,
                'message' => 'Member tidak sedang parkir.'
            ], 409);
        }

        $waktuMasuk = $member->waktu_masuk_parkir;
        $waktuKeluar = now();
        $noPlat = strtoupper(trim($request->input('no_plat', '')));

        // Tandai member sudah keluar
        $member->sedang_parkir = false;
        $member->waktu_masuk_parkir = null;
        $member->save();

        $member->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Member berhasil keluar parkir',
            'data' => array_merge($member->toArray(), [
                'waktu_masuk'  => $waktuMasuk,
                'waktu_keluar' => $waktuKeluar,
                'no_plat'      => $noPlat,
                'total_bayar'  => 0,
            ])
        ]);
    }


    // =========================================================
    // RESET BULANAN MEMBER
    // =========================================================
    public function resetBulanan()
    {
        $members = Member::all();

        $jumlahReset = 0;

        foreach ($members as $member) {

            $tanggalMulai = Carbon::now();
            $tanggalExpired = Carbon::now()->addMonth();

            $member->update([
                'total_harga' => 150000,
                'jumlah_bayar' => 0,
                'kembalian' => 0,
                'status' => 'belum lunas',
                'bulan_tagihan' => $tanggalMulai->format('Y-m'),
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_expired' => $tanggalExpired,
                'tanggal_bayar' => null,
            ]);

            $jumlahReset++;
        }

        return response()->json([
            'success' => true,
            'message' => 'Tagihan member berhasil direset',
            'jumlah_member' => $jumlahReset
        ]);
    }


    // =========================================================
    // BUAT KODE MEMBER OTOMATIS
    // =========================================================
    private function buatKodeMember()
    {
        $last = Member::orderByDesc('id')->first();

        if (!$last) {
            return 'M001';
        }

        $kodeLama = $last->kode_member;

        $angka = intval(
            preg_replace('/[^0-9]/', '', $kodeLama)
        );

        $angka++;

        do {
            $kode = 'M' . str_pad(
                $angka,
                3,
                '0',
                STR_PAD_LEFT
            );

            $angka++;
        } while (
            Member::where('kode_member', $kode)->exists()
        );

        return $kode;
    }
}