<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class OtpController extends Controller
{
    // =========================================================
    // KIRIM OTP KE WHATSAPP via FONNTE
    // =========================================================
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));
        $user  = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan di sistem.',
            ], 404);
        }

        $noWa = $user->nomor_wa ?? $user->no_telp ?? null;

        if (!$noWa) {
            return response()->json([
                'success' => false,
                'message' => 'Akun ini tidak memiliki nomor WhatsApp. Hubungi administrator.',
            ], 422);
        }

        // Generate OTP 6 digit
        $otp     = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = now()->addMinutes(15); // Beri waktu 15 menit agar tidak cepat expired

        // Simpan OTP ke database
        $user->otp_code       = $otp;
        $user->otp_expires_at = $expires;
        $user->save();

        // Format nomor WA (pastikan diawali 62)
        $noWaFormatted = preg_replace('/^0/', '62', preg_replace('/\D/', '', $noWa));

        // Kirim via Fonnte
        $pesan = "🔐 *Kode OTP E-Parkir*\n\nKode OTP Anda: *{$otp}*\n\nKode berlaku selama *15 menit*.\n\nJangan berikan kode ini kepada siapapun!";

        // Kirim via Fonnte (withoutVerifying = bypass SSL cert di localhost Windows)
        $response = Http::withoutVerifying()
            ->withHeaders(['Authorization' => env('FONNTE_TOKEN')])
            ->post(env('FONNTE_URL') . '/send', [
                'target'  => $noWaFormatted,
                'message' => $pesan,
            ]);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim OTP ke WhatsApp. Coba lagi.',
                'detail'  => $response->body(),
            ], 500);
        }

        // Sensor nomor WA untuk respons
        $noWaSensor = substr($noWaFormatted, 0, 4) . str_repeat('*', strlen($noWaFormatted) - 7) . substr($noWaFormatted, -3);

        return response()->json([
            'success' => true,
            'message' => "OTP berhasil dikirim ke WhatsApp {$noWaSensor}.",
            'wa'      => $noWaSensor,
        ]);
    }

    // =========================================================
    // VERIFIKASI OTP
    // =========================================================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->email));
        $user  = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email tidak ditemukan.'], 404);
        }

        if (!$user->otp_code || trim($user->otp_code) !== trim($request->otp)) {
            return response()->json(['success' => false, 'message' => 'Kode OTP salah.'], 422);
        }

        if (!$user->otp_expires_at || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Kode OTP sudah kedaluwarsa. Minta kode baru.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP valid. Silakan buat password baru.',
        ]);
    }

    // =========================================================
    // RESET PASSWORD
    // =========================================================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:6',
        ]);

        $email = strtolower(trim($request->email));
        $user  = User::whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Email tidak ditemukan.'], 404);
        }

        if (!$user->otp_code || trim($user->otp_code) !== trim($request->otp)) {
            return response()->json(['success' => false, 'message' => 'Kode OTP tidak valid.'], 422);
        }

        if (!$user->otp_expires_at || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Kode OTP sudah kedaluwarsa.'], 422);
        }

        // Update password & hapus OTP
        $user->password       = Hash::make($request->password);
        $user->otp_code       = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login.',
        ]);
    }
}

