<script setup lang="ts">
import { ref, onMounted } from 'vue'

const route = useRoute()

// ── Role ──────────────────────────────────────────────────
const role = ref<'superadmin' | 'petugas'>('superadmin')

// ── Form ──────────────────────────────────────────────────
const email        = ref('')
const password     = ref('')
const showPassword = ref(false)
const loading      = ref(false)
const errorMessage = ref('')
const resetSuccess = ref('')

// ── Credentials per role (Demo) ───────────────────────────
const CREDENTIALS = {
  superadmin: { email: 'superadmin@parkir.local', password: 'password' },
  petugas:    { email: 'petugas1@parkir.local',   password: 'password' },
}

function selectRole(r: 'superadmin' | 'petugas') {
  role.value = r
  // Hanya isi otomatis jika input email masih kosong atau masih akun demo bawaan
  if (!email.value || email.value.endsWith('@parkir.local')) {
    email.value    = CREDENTIALS[r].email
    password.value = CREDENTIALS[r].password
  }
  errorMessage.value = ''
}

function fillDemo(r: 'superadmin' | 'petugas') {
  role.value     = r
  email.value    = CREDENTIALS[r].email
  password.value = CREDENTIALS[r].password
  errorMessage.value = ''
  resetSuccess.value = ''
}

onMounted(() => {
  if (route.query.email) {
    email.value = String(route.query.email)
    password.value = '' // Kosongkan agar user mengetik password barunya
    if (route.query.reset === '1') {
      resetSuccess.value = 'Password berhasil diubah! Silakan masukkan password baru Anda.'
    }
  } else {
    // Default akun demo pertama kali
    email.value    = CREDENTIALS.superadmin.email
    password.value = CREDENTIALS.superadmin.password
  }
})

// ── Login ─────────────────────────────────────────────────
async function login() {
  errorMessage.value = ''
  if (!email.value.trim()) { errorMessage.value = 'Email wajib diisi.'; return }
  if (!password.value)     { errorMessage.value = 'Password wajib diisi.'; return }

  loading.value = true
  try {
    const res: any = await $fetch('http://127.0.0.1:8000/api/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: { email: email.value.trim(), password: password.value },
    })

    if (!res?.success) { errorMessage.value = res?.message || 'Login gagal.'; return }

    const user = res.user

    // Simpan ke localStorage
    localStorage.setItem('role',  user.role)
    localStorage.setItem('user',  JSON.stringify(user))
    localStorage.setItem('token', user.id?.toString() || '')

    // Redirect otomatis berdasarkan role dari database
    await navigateTo(user.role === 'superadmin' ? '/superadmin' : '/')

  } catch (err: any) {
    console.error(err)
    errorMessage.value = err?.data?.message || 'Gagal menghubungi server. Pastikan backend berjalan.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 flex items-center justify-center px-4 py-8 relative overflow-hidden">

    <!-- Dekorasi background -->
    <div class="absolute top-0 left-0 w-full h-full pointer-events-none">
      <div class="absolute top-[-80px] left-[-80px] w-72 h-72 bg-blue-600/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-[-80px] right-[-80px] w-72 h-72 bg-cyan-500/10 rounded-full blur-3xl"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-blue-900/20 rounded-full blur-3xl"></div>
    </div>

    <!-- Card utama -->
    <div class="relative z-10 w-full max-w-md">

      <!-- Logo + judul di atas card -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-2xl shadow-blue-500/40 text-4xl mb-4">
          🚗
        </div>
        <h1 class="text-3xl font-black text-white tracking-tight">E-PARKIR</h1>
        <p class="text-slate-400 text-sm mt-1">Plaza Andalas Parking System</p>
      </div>

      <!-- Card Form -->
      <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">

        <div class="mb-6">
          <h2 class="text-xl font-bold text-white">Masuk ke Sistem</h2>
          <p class="text-slate-400 text-sm mt-1">Pilih peran Anda untuk melanjutkan</p>
        </div>

        <!-- Pilih Role -->
        <div class="grid grid-cols-2 gap-3 mb-6">

          <!-- Superadmin -->
          <button
            type="button"
            @click="selectRole('superadmin')"
            :class="role === 'superadmin'
              ? 'bg-blue-600 border-blue-500 text-white shadow-lg shadow-blue-600/30 scale-[1.02]'
              : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10 hover:border-white/20 hover:text-white'"
            class="relative flex flex-col items-center gap-2 px-4 py-4 rounded-2xl border transition-all duration-200 cursor-pointer"
          >
            <span class="text-2xl">👑</span>
            <span class="text-sm font-bold">Super Admin</span>
            <span :class="role === 'superadmin' ? 'text-blue-200' : 'text-slate-500'" class="text-[11px]">Kelola sistem</span>
            <!-- Indikator aktif -->
            <span v-if="role === 'superadmin'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-blue-300 animate-pulse"></span>
          </button>

          <!-- Petugas -->
          <button
            type="button"
            @click="selectRole('petugas')"
            :class="role === 'petugas'
              ? 'bg-cyan-600 border-cyan-500 text-white shadow-lg shadow-cyan-600/30 scale-[1.02]'
              : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10 hover:border-white/20 hover:text-white'"
            class="relative flex flex-col items-center gap-2 px-4 py-4 rounded-2xl border transition-all duration-200 cursor-pointer"
          >
            <span class="text-2xl">🛡️</span>
            <span class="text-sm font-bold">Petugas</span>
            <span :class="role === 'petugas' ? 'text-cyan-200' : 'text-slate-500'" class="text-[11px]">Operasional</span>
            <!-- Indikator aktif -->
            <span v-if="role === 'petugas'" class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
          </button>

        </div>

        <!-- Divider -->
        <div class="relative mb-6">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-white/10"></div>
          </div>
          <div class="relative flex justify-center">
            <span class="bg-transparent px-3 text-xs text-slate-500">Masuk sebagai {{ role === 'superadmin' ? 'Super Admin' : 'Petugas' }}</span>
          </div>
        </div>

        <!-- Banner Sukses Reset Password -->
        <div v-if="resetSuccess" class="flex items-start gap-2.5 bg-emerald-500/15 border border-emerald-500/40 text-emerald-300 rounded-2xl p-4 text-xs font-semibold leading-relaxed mb-6">
          <span class="text-base leading-none">🎉</span>
          <div>
            <p class="font-bold text-white mb-0.5">Password Berhasil Diperbarui!</p>
            <p>{{ resetSuccess }}</p>
          </div>
        </div>

        <!-- Form -->
        <form @submit.prevent="login" class="space-y-4">

          <!-- Email -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Email</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">✉️</span>
              <input
                v-model="email"
                type="email"
                placeholder="email@contoh.com"
                autocomplete="email"
                class="w-full h-12 bg-white/5 border border-white/10 rounded-xl pl-11 pr-4 text-white text-sm placeholder:text-slate-600 focus:outline-none focus:border-blue-500 focus:bg-white/10 transition-all"
              />
            </div>
          </div>

          <!-- Password -->
          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1.5">Password</label>
            <div class="relative">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 text-sm">🔒</span>
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Masukkan password"
                autocomplete="current-password"
                class="w-full h-12 bg-white/5 border border-white/10 rounded-xl pl-11 pr-12 text-white text-sm placeholder:text-slate-600 focus:outline-none focus:border-blue-500 focus:bg-white/10 transition-all"
              />
              <button type="button" @click="showPassword = !showPassword"
                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-slate-300 transition-colors text-sm cursor-pointer">
                {{ showPassword ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>

          <!-- Error -->
          <div v-if="errorMessage"
            class="flex items-start gap-2 bg-red-500/10 border border-red-500/30 text-red-300 rounded-xl px-4 py-3 text-sm">
            <span class="shrink-0 mt-0.5">⚠️</span>
            <span>{{ errorMessage }}</span>
          </div>

          <!-- Tombol Login -->
          <button
            type="submit"
            :disabled="loading"
            :class="role === 'superadmin'
              ? 'bg-blue-600 hover:bg-blue-500 shadow-blue-600/30'
              : 'bg-cyan-600 hover:bg-cyan-500 shadow-cyan-600/30'"
            class="w-full h-12 rounded-xl text-white font-bold text-sm transition-all shadow-lg disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <span v-if="loading" class="flex items-center justify-center gap-2">
              <span class="animate-spin">⏳</span> Memproses...
            </span>
            <span v-else class="flex items-center justify-center gap-2">
              {{ role === 'superadmin' ? '👑' : '🛡️' }}
              Masuk sebagai {{ role === 'superadmin' ? 'Super Admin' : 'Petugas' }}
            </span>
          </button>

        </form>

        <!-- Lupa password -->
        <div class="mt-5 text-center">
          <button
            type="button"
            @click="navigateTo('/lupa-password')"
            class="text-xs text-slate-500 hover:text-slate-300 transition-colors cursor-pointer"
          >
            Lupa password? Klik di sini
          </button>
        </div>

      </div>

      <!-- Footer -->
      <p class="text-center text-slate-600 text-xs mt-6">
        © 2026 E-Parkir Plaza Andalas. All rights reserved.
      </p>

    </div>
  </div>
</template>