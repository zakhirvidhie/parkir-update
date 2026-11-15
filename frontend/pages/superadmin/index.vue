<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 text-white">

    <!-- SIDEBAR + MAIN LAYOUT -->
    <div class="flex min-h-screen">

      <!-- SIDEBAR -->
      <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col fixed inset-y-0 left-0 z-30">
        <!-- Logo -->
        <div class="px-6 py-5 border-b border-slate-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center text-white font-black text-lg">
              S
            </div>
            <div>
              <div class="font-black text-white text-sm leading-tight">PARKIR</div>
              <div class="text-xs text-violet-400 font-semibold">Super Admin</div>
            </div>
          </div>
        </div>

        <!-- Nav -->
        <nav class="flex-1 px-3 py-4 space-y-1">
          <button
            @click="navigateTo('/superadmin')"
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors cursor-pointer bg-violet-600/20 text-violet-300 border border-violet-600/30"
          >
            🏠 Beranda
          </button>
          <button
            @click="navigateTo('/superadmin/petugas')"
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer text-slate-400 hover:bg-slate-800 hover:text-white"
          >
            👮 Kelola Petugas
          </button>
        </nav>

        <!-- User info + Logout -->
        <div class="px-4 py-4 border-t border-slate-800 space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-violet-600 flex items-center justify-center text-white text-sm font-bold shrink-0">
              {{ userInitial }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-white truncate">{{ userName }}</p>
              <p class="text-xs text-violet-400">Super Admin</p>
            </div>
          </div>
          <button @click="handleLogout"
            class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer text-red-400 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/40">
            🚪 Logout
          </button>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="ml-64 flex-1 p-6 space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-black text-white">🏠 Beranda</h1>
            <p class="text-slate-400 text-sm mt-1">Selamat datang, {{ userName }}. Berikut ringkasan sistem parkir.</p>
          </div>
          <button
            @click="fetchStats"
            :disabled="loading"
            class="flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-medium transition-colors cursor-pointer"
          >
            <span :class="loading ? 'animate-spin' : ''">🔄</span>
            Refresh
          </button>
        </div>

        <!-- Error -->
        <div v-if="error" class="bg-red-900/30 border border-red-700/50 text-red-300 rounded-xl px-4 py-3 text-sm">
          ⚠️ {{ error }}
        </div>

        <!-- STAT CARDS -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
          <div class="bg-slate-900 border border-violet-700/40 rounded-2xl p-5">
            <div class="text-3xl mb-2">👮</div>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Total Petugas</p>
            <p class="text-3xl font-black text-violet-400">{{ stats.total_petugas }}</p>
          </div>
          <div class="bg-slate-900 border border-cyan-700/40 rounded-2xl p-5">
            <div class="text-3xl mb-2">👤</div>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Total Member</p>
            <p class="text-3xl font-black text-cyan-400">{{ stats.total_member }}</p>
          </div>
          <div class="bg-slate-900 border border-emerald-700/40 rounded-2xl p-5">
            <div class="text-3xl mb-2">🎫</div>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Tiket Aktif</p>
            <p class="text-3xl font-black text-emerald-400">{{ stats.tiket_aktif }}</p>
          </div>
          <div class="bg-slate-900 border border-yellow-700/40 rounded-2xl p-5">
            <div class="text-3xl mb-2">💰</div>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Pendapatan Hari Ini</p>
            <p class="text-xl font-black text-yellow-400">{{ formatRupiah(stats.pendapatan_hari_ini) }}</p>
          </div>
          <div class="bg-slate-900 border border-teal-700/40 rounded-2xl p-5 md:col-span-2">
            <div class="text-3xl mb-2">🏦</div>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Total Pendapatan</p>
            <p class="text-2xl font-black text-teal-400">{{ formatRupiah(stats.pendapatan_total) }}</p>
          </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6">
          <h2 class="text-base font-bold text-white mb-4">⚡ Aksi Cepat</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button
              @click="navigateTo('/superadmin/petugas')"
              class="flex flex-col items-center gap-2 p-4 bg-slate-800 hover:bg-violet-900/40 border border-slate-700 hover:border-violet-600/50 rounded-xl transition-all cursor-pointer group"
            >
              <span class="text-2xl">👮</span>
              <span class="text-xs font-semibold text-slate-300 group-hover:text-violet-300">Kelola Petugas</span>
            </button>
            <button
              @click="navigateTo('/petugas/member/select')"
              class="flex flex-col items-center gap-2 p-4 bg-slate-800 hover:bg-cyan-900/40 border border-slate-700 hover:border-cyan-600/50 rounded-xl transition-all cursor-pointer group"
            >
              <span class="text-2xl">👤</span>
              <span class="text-xs font-semibold text-slate-300 group-hover:text-cyan-300">Data Member</span>
            </button>
            <button
              @click="navigateTo('/petugas/tiket')"
              class="flex flex-col items-center gap-2 p-4 bg-slate-800 hover:bg-emerald-900/40 border border-slate-700 hover:border-emerald-600/50 rounded-xl transition-all cursor-pointer group"
            >
              <span class="text-2xl">🎫</span>
              <span class="text-xs font-semibold text-slate-300 group-hover:text-emerald-300">Data Tiket</span>
            </button>
            <button
              @click="navigateTo('/petugas/laporan')"
              class="flex flex-col items-center gap-2 p-4 bg-slate-800 hover:bg-teal-900/40 border border-slate-700 hover:border-teal-600/50 rounded-xl transition-all cursor-pointer group"
            >
              <span class="text-2xl">📊</span>
              <span class="text-xs font-semibold text-slate-300 group-hover:text-teal-300">Laporan</span>
            </button>
          </div>
        </div>

      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

const config  = useRuntimeConfig()
const apiBase = String(config.public.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')

// ── Auth ───────────────────────────────────────────────────
const userRaw  = process.client ? localStorage.getItem('user') : null
const userData = userRaw ? JSON.parse(userRaw) : {}
const userName   = computed(() => userData.name || userData.email || 'Admin')
const userInitial = computed(() => (userName.value[0] || 'A').toUpperCase())

const handleLogout = () => {
  if (process.client) {
    localStorage.removeItem('role')
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }
  navigateTo('/login')
}

// ── State ──────────────────────────────────────────────────
const loading = ref(false)
const error   = ref('')
const stats   = ref({
  total_petugas:       0,
  total_superadmin:    0,
  total_member:        0,
  tiket_aktif:         0,
  pendapatan_total:    0,
  pendapatan_hari_ini: 0,
})

// ── Helpers ────────────────────────────────────────────────
const formatRupiah = (val: number | string) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val || 0))

// ── Fetch ──────────────────────────────────────────────────
const fetchStats = async () => {
  loading.value = true
  error.value   = ''
  try {
    const res: any = await $fetch(`${apiBase}/users/stats`, { headers: { Accept: 'application/json' } })
    stats.value = res?.data ?? res
  } catch (e: any) {
    error.value = e?.data?.message ?? 'Gagal memuat statistik'
  } finally {
    loading.value = false
  }
}

onMounted(fetchStats)
</script>

