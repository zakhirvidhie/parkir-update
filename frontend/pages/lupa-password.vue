<script setup lang="ts">
import { ref } from 'vue'

definePageMeta({ layout: false })

const API = 'http://127.0.0.1:8000/api'

// ── Step: 1=email, 2=otp, 3=password baru, 4=sukses ─────
const step = ref<1 | 2 | 3 | 4>(1)

// ── Form fields ───────────────────────────────────────────
const email       = ref('')
const otp         = ref('')
const newPassword = ref('')
const konfirmasi  = ref('')
const showPass    = ref(false)
const showKonfirm = ref(false)

// ── State ─────────────────────────────────────────────────
const loading      = ref(false)
const errorMsg     = ref('')
const successMsg   = ref('')
const waNumber     = ref('')   // nomor WA yang tersensor dari server

// ── Countdown resend ──────────────────────────────────────
const countdown   = ref(0)
let countdownTimer: ReturnType<typeof setInterval> | null = null

function startCountdown() {
  countdown.value = 60
  if (countdownTimer) clearInterval(countdownTimer)
  countdownTimer = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--
    } else {
      clearInterval(countdownTimer!)
    }
  }, 1000)
}

// ── Step 1: Kirim OTP ─────────────────────────────────────
async function kirimOtp() {
  errorMsg.value   = ''
  successMsg.value = ''
  if (!email.value.trim()) { errorMsg.value = 'Email wajib diisi.'; return }

  loading.value = true
  try {
    const res: any = await $fetch(`${API}/forgot-password/send-otp`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: { email: email.value.trim() },
    })
    waNumber.value   = res.wa || ''
    successMsg.value = res.message || 'OTP dikirim!'
    step.value       = 2
    startCountdown()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal mengirim OTP. Coba lagi.'
  } finally {
    loading.value = false
  }
}

// ── Kirim ulang OTP ───────────────────────────────────────
async function kirimUlang() {
  if (countdown.value > 0) return
  errorMsg.value   = ''
  successMsg.value = ''
  loading.value    = true
  try {
    const res: any = await $fetch(`${API}/forgot-password/send-otp`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: { email: email.value.trim() },
    })
    successMsg.value = 'OTP baru telah dikirim!'
    startCountdown()
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal kirim ulang OTP.'
  } finally {
    loading.value = false
  }
}

// ── Step 2: Verifikasi OTP ────────────────────────────────
async function verifikasiOtp() {
  errorMsg.value   = ''
  successMsg.value = ''
  if (otp.value.length !== 6) { errorMsg.value = 'Masukkan 6 digit kode OTP.'; return }

  loading.value = true
  try {
    await $fetch(`${API}/forgot-password/verify-otp`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: { email: email.value.trim(), otp: otp.value },
    })
    step.value = 3
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'OTP salah atau kedaluwarsa.'
  } finally {
    loading.value = false
  }
}

// ── Step 3: Reset Password ────────────────────────────────
async function resetPassword() {
  errorMsg.value   = ''
  successMsg.value = ''
  if (!newPassword.value || newPassword.value.length < 6) {
    errorMsg.value = 'Password minimal 6 karakter.'; return
  }
  if (newPassword.value !== konfirmasi.value) {
    errorMsg.value = 'Password dan konfirmasi tidak cocok.'; return
  }

  loading.value = true
  try {
    await $fetch(`${API}/forgot-password/reset`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: { email: email.value.trim(), otp: otp.value, password: newPassword.value },
    })
    step.value = 4
  } catch (e: any) {
    errorMsg.value = e?.data?.message || 'Gagal reset password.'
  } finally {
    loading.value = false
  }
}

// ── Masukkan OTP digit-by-digit ───────────────────────────
const otpInputs = ref<string[]>(['', '', '', '', '', ''])

function onOtpInput(index: number, e: Event) {
  const val = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(-1)
  otpInputs.value[index] = val
  otp.value = otpInputs.value.join('')
  if (val && index < 5) {
    const next = document.getElementById(`otp-${index + 1}`)
    next?.focus()
  }
}

function onOtpKeydown(index: number, e: KeyboardEvent) {
  if (e.key === 'Backspace' && !otpInputs.value[index] && index > 0) {
    const prev = document.getElementById(`otp-${index - 1}`)
    prev?.focus()
  }
}

// ── Ke halaman login (hapus session dulu agar middleware tidak redirect ke beranda) ──
function keLogin() {
  if (process.client) {
    localStorage.removeItem('role')
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }
  navigateTo(`/login?email=${encodeURIComponent(email.value.trim())}&reset=1`)
}

// ── Password strength ─────────────────────────────────────
function getStrength(p: string): number {
  let s = 0
  if (p.length >= 6)  s++
  if (p.length >= 10) s++
  if (/[A-Z]/.test(p) || /[0-9]/.test(p)) s++
  if (/[^A-Za-z0-9]/.test(p)) s++
  return Math.max(1, s)
}
function strengthColor(p: string): string {
  const s = getStrength(p)
  if (s <= 1) return 'bg-red-500'
  if (s === 2) return 'bg-orange-400'
  if (s === 3) return 'bg-yellow-400'
  return 'bg-green-500'
}
function strengthLabel(p: string): string {
  const s = getStrength(p)
  if (s <= 1) return 'Lemah'
  if (s === 2) return 'Cukup'
  if (s === 3) return 'Kuat'
  return 'Sangat Kuat'
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center px-4 py-8 relative overflow-hidden">

    <!-- Dekorasi -->
    <div class="absolute inset-0 pointer-events-none">
      <div class="absolute top-[-80px] left-[-80px] w-72 h-72 bg-blue-600/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-[-80px] right-[-80px] w-72 h-72 bg-violet-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">

      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-xl shadow-blue-500/30 text-3xl mb-4">
          🔐
        </div>
        <h1 class="text-2xl font-black text-white">Lupa Password</h1>
        <p class="text-slate-400 text-sm mt-1">E-Parkir Plaza Andalas</p>
      </div>

      <!-- Card -->
      <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">

        <!-- ── STEP INDICATOR ── -->
        <div class="flex items-center gap-2 mb-8">
          <template v-for="s in [1,2,3,4]" :key="s">
            <div :class="[
              'flex items-center justify-center w-8 h-8 rounded-full text-xs font-bold transition-all',
              step >= s
                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30'
                : 'bg-white/10 text-slate-500'
            ]">
              <span v-if="step > s">✓</span>
              <span v-else>{{ s }}</span>
            </div>
            <div v-if="s < 4" :class="['flex-1 h-0.5 rounded transition-all', step > s ? 'bg-blue-600' : 'bg-white/10']"></div>
          </template>
        </div>

        <!-- ── STEP 1: EMAIL ── -->
        <div v-if="step === 1">
          <h2 class="text-lg font-bold text-white mb-1">Masukkan Email</h2>
          <p class="text-slate-400 text-sm mb-6">Kode OTP akan dikirim ke WhatsApp yang terdaftar.</p>

          <div class="mb-5">
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Email Akun</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">✉️</span>
              <input
                v-model="email"
                type="email"
                placeholder="email@contoh.com"
                @keyup.enter="kirimOtp"
                class="w-full h-12 bg-white/5 border border-white/10 rounded-xl pl-11 pr-4 text-white text-sm placeholder:text-slate-600 focus:outline-none focus:border-blue-500 focus:bg-white/10 transition-all"
              />
            </div>
          </div>

          <div v-if="errorMsg" class="flex gap-2 bg-red-500/10 border border-red-500/30 text-red-300 rounded-xl px-4 py-3 text-sm mb-4">
            <span>⚠️</span> {{ errorMsg }}
          </div>

          <button @click="kirimOtp" :disabled="loading"
            class="w-full h-12 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 rounded-xl text-white font-bold text-sm transition-all cursor-pointer shadow-lg shadow-blue-600/30">
            <span v-if="loading">⏳ Mengirim...</span>
            <span v-else>📱 Kirim Kode OTP</span>
          </button>
        </div>

        <!-- ── STEP 2: OTP ── -->
        <div v-if="step === 2">
          <h2 class="text-lg font-bold text-white mb-1">Verifikasi OTP</h2>
          <p class="text-slate-400 text-sm mb-1">
            Kode OTP dikirim ke WhatsApp
            <span class="text-blue-400 font-semibold">{{ waNumber || '***' }}</span>
          </p>
          <p class="text-slate-500 text-xs mb-6">Berlaku selama 5 menit.</p>

          <!-- OTP Boxes -->
          <div class="flex gap-3 justify-center mb-6">
            <input
              v-for="(_, i) in otpInputs"
              :key="i"
              :id="`otp-${i}`"
              type="text"
              inputmode="numeric"
              maxlength="1"
              :value="otpInputs[i]"
              @input="onOtpInput(i, $event)"
              @keydown="onOtpKeydown(i, $event)"
              class="w-12 h-14 text-center text-xl font-black bg-white/5 border border-white/15 rounded-xl text-white focus:outline-none focus:border-blue-500 focus:bg-white/10 transition-all"
            />
          </div>

          <div v-if="errorMsg" class="flex gap-2 bg-red-500/10 border border-red-500/30 text-red-300 rounded-xl px-4 py-3 text-sm mb-4">
            <span>⚠️</span> {{ errorMsg }}
          </div>
          <div v-if="successMsg" class="flex gap-2 bg-green-500/10 border border-green-500/30 text-green-300 rounded-xl px-4 py-3 text-sm mb-4">
            <span>✅</span> {{ successMsg }}
          </div>

          <button @click="verifikasiOtp" :disabled="loading || otp.length < 6"
            class="w-full h-12 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 rounded-xl text-white font-bold text-sm transition-all cursor-pointer shadow-lg shadow-blue-600/30 mb-4">
            <span v-if="loading">⏳ Memverifikasi...</span>
            <span v-else>✅ Verifikasi OTP</span>
          </button>

          <!-- Kirim ulang -->
          <div class="text-center">
            <span class="text-slate-500 text-sm">Belum terima kode? </span>
            <button @click="kirimUlang" :disabled="countdown > 0 || loading"
              class="text-sm font-semibold transition-colors cursor-pointer"
              :class="countdown > 0 ? 'text-slate-600 cursor-not-allowed' : 'text-blue-400 hover:text-blue-300'">
              {{ countdown > 0 ? `Kirim ulang (${countdown}s)` : 'Kirim Ulang' }}
            </button>
          </div>
        </div>

        <!-- ── STEP 3: PASSWORD BARU ── -->
        <div v-if="step === 3">
          <h2 class="text-lg font-bold text-white mb-1">Buat Password Baru</h2>
          <p class="text-slate-400 text-sm mb-6">Pastikan password minimal 6 karakter.</p>

          <div class="space-y-4 mb-5">
            <!-- Password baru -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">Password Baru</label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">🔒</span>
                <input
                  v-model="newPassword"
                  :type="showPass ? 'text' : 'password'"
                  placeholder="Minimal 6 karakter"
                  class="w-full h-12 bg-white/5 border border-white/10 rounded-xl pl-11 pr-12 text-white text-sm placeholder:text-slate-600 focus:outline-none focus:border-blue-500 focus:bg-white/10 transition-all"
                />
                <button type="button" @click="showPass = !showPass"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 cursor-pointer">
                  {{ showPass ? '🙈' : '👁️' }}
                </button>
              </div>
            </div>
            <!-- Konfirmasi -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">Konfirmasi Password</label>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">🔒</span>
                <input
                  v-model="konfirmasi"
                  :type="showKonfirm ? 'text' : 'password'"
                  placeholder="Ulangi password baru"
                  @keyup.enter="resetPassword"
                  class="w-full h-12 bg-white/5 border border-white/10 rounded-xl pl-11 pr-12 text-white text-sm placeholder:text-slate-600 focus:outline-none focus:border-blue-500 focus:bg-white/10 transition-all"
                />
                <button type="button" @click="showKonfirm = !showKonfirm"
                  class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 cursor-pointer">
                  {{ showKonfirm ? '🙈' : '👁️' }}
                </button>
              </div>
            </div>
          </div>

          <!-- Strength indicator -->
          <div v-if="newPassword" class="mb-4">
            <div class="flex gap-1 mb-1">
              <div v-for="n in 4" :key="n"
                :class="['h-1 flex-1 rounded', getStrength(newPassword) >= n ? strengthColor(newPassword) : 'bg-white/10']">
              </div>
            </div>
            <p :class="['text-xs', strengthColor(newPassword).replace('bg-','text-')]">
              {{ strengthLabel(newPassword) }}
            </p>
          </div>

          <div v-if="errorMsg" class="flex gap-2 bg-red-500/10 border border-red-500/30 text-red-300 rounded-xl px-4 py-3 text-sm mb-4">
            <span>⚠️</span> {{ errorMsg }}
          </div>

          <button @click="resetPassword" :disabled="loading"
            class="w-full h-12 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 rounded-xl text-white font-bold text-sm transition-all cursor-pointer shadow-lg shadow-blue-600/30">
            <span v-if="loading">⏳ Menyimpan...</span>
            <span v-else>🔐 Simpan Password Baru</span>
          </button>
        </div>

        <!-- ── STEP 4: SUKSES ── -->
        <div v-if="step === 4" class="text-center py-4">
          <div class="text-6xl mb-4">🎉</div>
          <h2 class="text-xl font-black text-white mb-2">Password Berhasil Diubah!</h2>
          <p class="text-slate-400 text-sm mb-8">Silakan login dengan password baru Anda.</p>
          <button @click="keLogin"
            class="w-full h-12 bg-blue-600 hover:bg-blue-500 rounded-xl text-white font-bold text-sm transition-all cursor-pointer shadow-lg shadow-blue-600/30">
            🔑 Kembali ke Login
          </button>
        </div>

      </div>

      <!-- Back to login (step 1 & 2 only) -->
      <div v-if="step < 4" class="text-center mt-5">
        <button @click="navigateTo('/login')" class="text-sm text-slate-500 hover:text-slate-300 transition-colors cursor-pointer">
          ← Kembali ke Login
        </button>
      </div>

    </div>
  </div>
</template>
