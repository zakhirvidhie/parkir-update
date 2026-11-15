<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Member;
use App\Models\TiketParkir;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // =========================================================
    // SEMUA USER
    // =========================================================
    public function index()
    {
        $users = User::orderByDesc('id')->get()->map(fn($u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'username'   => $u->username ?? '',
            'no_telp'    => $u->no_telp ?? $u->nomor_wa ?? '-',
            'role'       => $u->role ?? 'petugas',
            'created_at' => $u->created_at,
        ]);

        return response()->json(['success' => true, 'data' => $users]);
    }

    // =========================================================
    // TAMBAH USER BARU
    // =========================================================
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'username' => 'nullable|string|max:100|unique:users,username',
            'password' => 'required|string|min:6',
            'no_telp'  => 'nullable|string|max:20',
            'role'     => 'nullable|in:superadmin,petugas',
        ]);

        $user = new User();
        $user->name     = $request->name;
        $user->email    = $request->email;
        $user->username = $request->username ?? strtolower(str_replace(' ', '', $request->name));
        $user->password = Hash::make($request->password);
        $user->no_telp  = $request->no_telp;
        $user->role     = $request->role ?? 'petugas';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => ucfirst($user->role) . ' berhasil ditambahkan',
            'data'    => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'username'   => $user->username,
                'no_telp'    => $user->no_telp,
                'role'       => $user->role,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    // =========================================================
    // UPDATE USER
    // =========================================================
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $id,
            'username' => 'nullable|string|max:100|unique:users,username,' . $id,
            'no_telp'  => 'nullable|string|max:20',
            'role'     => 'nullable|in:superadmin,petugas',
        ]);

        $updateData = [
            'name'    => $request->name,
            'email'   => $request->email,
            'no_telp' => $request->no_telp,
            'role'    => $request->role ?? $user->role,
        ];

        if ($request->filled('username')) {
            $updateData['username'] = $request->username;
        }

        $user->update($updateData);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'data'    => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'username' => $user->username,
                'no_telp'  => $user->no_telp,
                'role'     => $user->role,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    // =========================================================
    // HAPUS USER
    // =========================================================
    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['success' => false, 'message' => 'User tidak ditemukan'], 404);

        if (($user->role ?? '') === 'superadmin') {
            // Pastikan masih ada superadmin lain
            $countSuperadmin = User::where('role', 'superadmin')->count();
            if ($countSuperadmin <= 1) {
                return response()->json(['success' => false, 'message' => 'Tidak bisa menghapus satu-satunya superadmin'], 403);
            }
        }

        $user->delete();
        return response()->json(['success' => true, 'message' => 'User berhasil dihapus']);
    }

    // =========================================================
    // STATISTIK DASHBOARD SUPERADMIN
    // =========================================================
    public function stats()
    {
        $totalPetugas    = User::where('role', 'petugas')->count();
        $totalSuperadmin = User::where('role', 'superadmin')->count();
        $totalMember     = Member::count();
        $totalTiketAktif = TiketParkir::where('status', 'masuk')->count();
        $pendapatanTotal = TiketParkir::where('status', 'keluar')->sum('total_bayar');
        $pendapatanHariIni = TiketParkir::where('status', 'keluar')
            ->whereDate('waktu_keluar', today())
            ->sum('total_bayar');

        return response()->json([
            'success' => true,
            'data'    => [
                'total_petugas'      => $totalPetugas,
                'total_superadmin'   => $totalSuperadmin,
                'total_member'       => $totalMember,
                'tiket_aktif'        => $totalTiketAktif,
                'pendapatan_total'   => $pendapatanTotal,
                'pendapatan_hari_ini'=> $pendapatanHariIni,
            ],
        ]);
    }
}
