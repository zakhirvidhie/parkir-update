<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TiketController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| LUPA PASSWORD / OTP WHATSAPP (Fonnte)
|--------------------------------------------------------------------------
*/
Route::post('/forgot-password/send-otp',   [OtpController::class, 'sendOtp']);
Route::post('/forgot-password/verify-otp', [OtpController::class, 'verifyOtp']);
Route::post('/forgot-password/reset',      [OtpController::class, 'resetPassword']);

/*
|--------------------------------------------------------------------------
| USER / PETUGAS MANAGEMENT
|--------------------------------------------------------------------------
*/

// Statistik dashboard superadmin
Route::get('/users/stats', [UserController::class, 'stats'])->name('users.stats');

// CRUD petugas
Route::get('/users',              [UserController::class, 'index'])->name('users.index');
Route::post('/users',             [UserController::class, 'store'])->name('users.store');
Route::put('/users/{id}',         [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{id}',      [UserController::class, 'destroy'])->name('users.destroy');
Route::patch('/users/{id}/toggle',[UserController::class, 'toggleActive'])->name('users.toggle');

/*
|--------------------------------------------------------------------------
| MEMBER
|--------------------------------------------------------------------------
*/

// =========================================================
// SEMUA MEMBER
// =========================================================
Route::get('/member', [
    MemberController::class,
    'index'
])->name('member.index');


// =========================================================
// TAMBAH MEMBER
// =========================================================
Route::post('/member', [
    MemberController::class,
    'store'
])->name('member.store');


// =========================================================
// DETAIL MEMBER + QR
// =========================================================
Route::get('/member/{id}', [
    MemberController::class,
    'show'
])->name('member.show');


// =========================================================
// UPDATE MEMBER
// =========================================================
Route::put('/member/{id}', [
    MemberController::class,
    'update'
])->name('member.update');


// =========================================================
// HAPUS MEMBER
// =========================================================
Route::delete('/member/{id}', [
    MemberController::class,
    'destroy'
])->name('member.destroy');


// =========================================================
// PEMBAYARAN MEMBER
// =========================================================
Route::put('/member/{id}/pembayaran', [
    MemberController::class,
    'pembayaran'
])->name('member.pembayaran');


// =========================================================
// CHECK QR MEMBER
// =========================================================
Route::post('/member/check', [
    MemberController::class,
    'check'
])->name('member.check');


// =========================================================
// MEMBER MASUK PARKIR
// =========================================================
Route::post('/member/masuk', [
    MemberController::class,
    'masuk'
])->name('member.masuk');


// =========================================================
// MEMBER KELUAR PARKIR
// =========================================================
Route::post('/member/keluar', [
    MemberController::class,
    'keluar'
])->name('member.keluar');


// =========================================================
// RESET / BUAT TAGIHAN BULAN BARU
// =========================================================
Route::post('/member/reset-bulanan', [
    MemberController::class,
    'resetBulanan'
])->name('member.reset.bulanan');


/*
|--------------------------------------------------------------------------
| TIKET PARKIR
|--------------------------------------------------------------------------
*/

// =========================================================
// BUAT TIKET BARU
// =========================================================
Route::post('/tiket', [
    TiketController::class,
    'create'
])->name('tiket.create');


// =========================================================
// SEMUA TIKET
// =========================================================
Route::get('/tiket', [
    TiketController::class,
    'index'
])->name('tiket.index');


// =========================================================
// CARI TIKET BERDASARKAN KODE
// =========================================================
// PENTING:
// Route ini harus berada SEBELUM /tiket/{id}
Route::get('/tiket/kode/{kode}', [
    TiketController::class,
    'cariKode'
])->name('tiket.cari.kode');


// =========================================================
// DETAIL TIKET BERDASARKAN ID
// =========================================================
Route::get('/tiket/{id}', [
    TiketController::class,
    'show'
])->name('tiket.show');


// =========================================================
// PROSES KENDARAAN KELUAR
// =========================================================
Route::put('/tiket/{id}/keluar', [
    TiketController::class,
    'keluar'
])->name('tiket.keluar');


// =========================================================
// HAPUS TIKET
// =========================================================
Route::delete('/tiket/{id}', [
    TiketController::class,
    'destroy'
])->name('tiket.destroy');


/*
|--------------------------------------------------------------------------
| LUPA PASSWORD / OTP WHATSAPP
|--------------------------------------------------------------------------
*/

// =========================================================
// KIRIM OTP KE WHATSAPP
// =========================================================
Route::post('/forgot-password/send-otp', [
    OtpController::class,
    'sendOtp'
]);


// =========================================================
// VERIFIKASI OTP
// =========================================================
Route::post('/forgot-password/verify-otp', [
    OtpController::class,
    'verifyOtp'
]);


// =========================================================
// RESET PASSWORD
// =========================================================
Route::post('/forgot-password/reset', [
    OtpController::class,
    'resetPassword'
]);