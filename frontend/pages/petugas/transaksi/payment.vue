<template>
  <div class="min-h-screen bg-slate-950 text-white">

    <!-- Header -->
    <div class="bg-slate-900 border-b border-slate-800 px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button
          @click="navigateTo('/')"
          class="flex items-center gap-2 text-slate-400 hover:text-cyan-400 transition-colors text-sm font-medium cursor-pointer"
        >
          ← Dashboard
        </button>
        <span class="text-slate-700">|</span>
        <h1 class="text-xl font-bold text-white">💳 Riwayat Pembayaran</h1>
      </div>
      <button
        @click="refreshData"
        :disabled="loading"
        class="flex items-center gap-2 px-4 py-2 bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg text-sm font-medium transition-colors cursor-pointer"
      >
        <span :class="loading ? 'animate-spin' : ''">🔄</span>
        Refresh
      </button>
    </div>

    <div class="p-6 space-y-6">

      <!-- Error -->
      <div v-if="error" class="bg-red-900/40 border border-red-700 text-red-300 rounded-xl px-4 py-3 text-sm">
        ⚠️ {{ error }}
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-slate-900 border border-emerald-800/50 rounded-xl p-5">
          <p class="text-xs text-slate-400 mb-1 uppercase tracking-wider">Total Pemasukan</p>
          <p class="text-emerald-400 text-2xl font-black">{{ formatRupiah(totalPemasukanSemua) }}</p>
          <p class="text-xs text-slate-500 mt-1">Hari ini: <span class="text-emerald-500">{{ formatRupiah(totalPemasukanHariIni) }}</span></p>
        </div>
        <div class="bg-slate-900 border border-cyan-800/50 rounded-xl p-5">
          <p class="text-xs text-slate-400 mb-1 uppercase tracking-wider">Total Kendaraan</p>
          <p class="text-cyan-400 text-2xl font-black">{{ totalKendaraanSemua }}</p>
          <p class="text-xs text-slate-500 mt-1">Hari ini: <span class="text-cyan-500">{{ totalKendaraanHariIni }}</span> kendaraan</p>
        </div>
        <div class="bg-slate-900 border border-teal-800/50 rounded-xl p-5">
          <p class="text-xs text-slate-400 mb-1 uppercase tracking-wider">Total Member Aktif</p>
          <p class="text-teal-400 text-2xl font-black">{{ totalMemberAktif }}</p>
          <p class="text-xs text-slate-500 mt-1">Status lunas</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="flex gap-1 bg-slate-900 p-1 rounded-xl w-fit border border-slate-800">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'px-5 py-2 rounded-lg text-sm font-medium transition-colors cursor-pointer',
            activeTab === tab.key
              ? 'bg-cyan-600 text-white'
              : 'text-slate-400 hover:text-white hover:bg-slate-800'
          ]"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- ── TAB TIKET ── -->
      <div v-if="activeTab === 'tiket'" class="space-y-4">

        <!-- Controls -->
        <div class="flex flex-wrap gap-3 items-center">
          <div class="relative flex-1 min-w-48">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
            <input
              v-model="searchTiket"
              type="text"
              placeholder="Cari kode tiket..."
              class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-cyan-500"
            />
          </div>
          <div class="flex gap-1 bg-slate-900 border border-slate-700 rounded-lg p-1">
            <button
              v-for="f in filterWaktuOptions"
              :key="f.key"
              @click="filterTiket = f.key"
              :class="['px-3 py-1.5 rounded text-xs font-medium transition-colors cursor-pointer', filterTiket === f.key ? 'bg-cyan-600 text-white' : 'text-slate-400 hover:text-white']"
            >
              {{ f.label }}
            </button>
          </div>
        </div>

        <!-- Card total -->
        <div class="bg-emerald-900/20 border border-emerald-700/40 rounded-xl px-4 py-3 flex items-center justify-between">
          <span class="text-emerald-300 text-sm font-medium">💰 Total Pendapatan Tiket (filter aktif)</span>
          <span class="text-emerald-400 font-black text-lg">{{ formatRupiah(totalPendapatanTiketFiltered) }}</span>
        </div>

        <!-- Table tiket -->
        <div class="bg-slate-900 border border-slate-700 rounded-xl overflow-hidden">
          <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
            <span class="animate-spin text-2xl mr-3">⏳</span> Memuat data...
          </div>
          <div v-else-if="filteredTiket.length === 0" class="text-center py-16 text-slate-500">
            <div class="text-4xl mb-2">🎫</div>
            <p>Tidak ada transaksi tiket ditemukan</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-800 text-slate-400 text-xs uppercase tracking-wider">
                <tr>
                  <th class="text-left px-4 py-3">Kode</th>
                  <th class="text-left px-4 py-3">Kendaraan</th>
                  <th class="text-left px-4 py-3">Waktu Masuk</th>
                  <th class="text-left px-4 py-3">Waktu Keluar</th>
                  <th class="text-left px-4 py-3">Durasi</th>
                  <th class="text-right px-4 py-3">Total Bayar</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="t in filteredTiket" :key="t.id" class="hover:bg-slate-800/40 transition-colors">
                  <td class="px-4 py-3 font-mono text-cyan-400 font-bold">{{ t.kode_tiket }}</td>
                  <td class="px-4 py-3">
                    <span class="flex items-center gap-1">
                      {{ t.jenis_kendaraan === 'mobil' ? '🚗' : '🏍️' }}
                      <span class="capitalize">{{ t.jenis_kendaraan }}</span>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ formatDateTime(t.waktu_masuk) }}</td>
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ formatDateTime(t.waktu_keluar) }}</td>
                  <td class="px-4 py-3 text-slate-300">{{ formatDurasi(t.waktu_masuk, t.waktu_keluar) }}</td>
                  <td class="px-4 py-3 text-right font-bold text-emerald-400">{{ formatRupiah(t.total_bayar) }}</td>
                </tr>
              </tbody>
            </table>
            <p class="text-xs text-slate-500 px-4 py-2 text-right">Menampilkan {{ filteredTiket.length }} tiket</p>
          </div>
        </div>
      </div>

      <!-- ── TAB MEMBER ── -->
      <div v-if="activeTab === 'member'" class="space-y-4">

        <!-- Filter member -->
        <div class="flex gap-1 bg-slate-900 border border-slate-700 rounded-lg p-1 w-fit">
          <button
            v-for="f in filterMemberOptions"
            :key="f.key"
            @click="filterMember = f.key"
            :class="['px-4 py-1.5 rounded text-xs font-medium transition-colors cursor-pointer', filterMember === f.key ? 'bg-teal-600 text-white' : 'text-slate-400 hover:text-white']"
          >
            {{ f.label }}
          </button>
        </div>

        <!-- Card total -->
        <div class="bg-teal-900/20 border border-teal-700/40 rounded-xl px-4 py-3 flex items-center justify-between">
          <span class="text-teal-300 text-sm font-medium">💰 Total Pendapatan Member (filter aktif)</span>
          <span class="text-teal-400 font-black text-lg">{{ formatRupiah(totalPendapatanMemberFiltered) }}</span>
        </div>

        <!-- Table member -->
        <div class="bg-slate-900 border border-slate-700 rounded-xl overflow-hidden">
          <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
            <span class="animate-spin text-2xl mr-3">⏳</span> Memuat data...
          </div>
          <div v-else-if="filteredMember.length === 0" class="text-center py-16 text-slate-500">
            <div class="text-4xl mb-2">👤</div>
            <p>Tidak ada data member ditemukan</p>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-800 text-slate-400 text-xs uppercase tracking-wider">
                <tr>
                  <th class="text-left px-4 py-3">Kode Member</th>
                  <th class="text-left px-4 py-3">Nama</th>
                  <th class="text-left px-4 py-3">Perusahaan</th>
                  <th class="text-right px-4 py-3">Tagihan</th>
                  <th class="text-right px-4 py-3">Dibayar</th>
                  <th class="text-right px-4 py-3">Kembalian</th>
                  <th class="text-left px-4 py-3">Tgl Bayar</th>
                  <th class="text-left px-4 py-3">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="m in filteredMember" :key="m.id" class="hover:bg-slate-800/40 transition-colors">
                  <td class="px-4 py-3 font-mono text-purple-400 font-bold">{{ m.kode_member }}</td>
                  <td class="px-4 py-3 font-medium">{{ m.nama_member }}</td>
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ m.nama_perusahaan }}</td>
                  <td class="px-4 py-3 text-right text-slate-300">{{ formatRupiah(m.total_harga) }}</td>
                  <td class="px-4 py-3 text-right text-emerald-400 font-bold">{{ formatRupiah(m.jumlah_bayar) }}</td>
                  <td class="px-4 py-3 text-right text-yellow-400">{{ formatRupiah(m.kembalian) }}</td>
                  <td class="px-4 py-3 text-slate-400 text-xs">{{ m.tanggal_bayar ? formatDateTime(m.tanggal_bayar) : '-' }}</td>
                  <td class="px-4 py-3">
                    <span
                      :class="[
                        'inline-block px-2 py-0.5 rounded-full text-xs font-bold',
                        (m.status || '').toLowerCase() === 'lunas'
                          ? 'bg-emerald-900/60 text-emerald-300 border border-emerald-700/50'
                          : 'bg-red-900/60 text-red-300 border border-red-700/50'
                      ]"
                    >
                      {{ (m.status || '').toLowerCase() === 'lunas' ? '✅ Lunas' : '❌ Belum Lunas' }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
            <p class="text-xs text-slate-500 px-4 py-2 text-right">Menampilkan {{ filteredMember.length }} member</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Tiket {
  id: number
  kode_tiket: string
  jenis_kendaraan: string
  tarif_per_jam: number
  total_bayar: number
  status: string
  waktu_masuk: string | null
  waktu_keluar: string | null
}

interface Member {
  id: number
  kode_member: string
  nama_member: string
  nama_perusahaan: string
  status: string
  total_harga: number
  jumlah_bayar: number
  kembalian: number
  tanggal_bayar: string | null
  tanggal_mulai: string | null
}

const config  = useRuntimeConfig()
const apiBase = String(config.public.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')

// ── State ──────────────────────────────────────────────────
const tiketList  = ref<Tiket[]>([])
const memberList = ref<Member[]>([])
const loading    = ref(false)
const error      = ref('')
const activeTab  = ref<'tiket' | 'member'>('tiket')
const searchTiket   = ref('')
const filterTiket   = ref<'hari_ini' | 'minggu_ini' | 'semua'>('hari_ini')
const filterMember  = ref<'semua' | 'lunas' | 'belum_lunas'>('semua')

const tabs = [
  { key: 'tiket',  label: '🎫 Tiket Kendaraan' },
  { key: 'member', label: '👤 Member' },
]

const filterWaktuOptions = [
  { key: 'hari_ini',   label: 'Hari Ini' },
  { key: 'minggu_ini', label: 'Minggu Ini' },
  { key: 'semua',      label: 'Semua' },
]

const filterMemberOptions = [
  { key: 'semua',       label: 'Semua' },
  { key: 'lunas',       label: '✅ Lunas' },
  { key: 'belum_lunas', label: '❌ Belum Lunas' },
]

// ── Helpers ────────────────────────────────────────────────
const formatRupiah = (val: number | string) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val || 0))

const formatDateTime = (dt: string | null) => {
  if (!dt) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(dt))
}

const formatDurasi = (masuk: string | null, keluar: string | null): string => {
  if (!masuk || !keluar) return '-'
  const ms  = new Date(keluar).getTime() - new Date(masuk).getTime()
  const jam = Math.max(1, Math.ceil(ms / 3600000))
  return `${jam} jam`
}

const isHariIni = (dt: string | null): boolean => {
  if (!dt) return false
  const d   = new Date(dt)
  const now = new Date()
  return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth() && d.getDate() === now.getDate()
}

const isMingguIni = (dt: string | null): boolean => {
  if (!dt) return false
  const d   = new Date(dt)
  const now = new Date()
  const startOfWeek = new Date(now)
  startOfWeek.setDate(now.getDate() - now.getDay())
  startOfWeek.setHours(0, 0, 0, 0)
  return d >= startOfWeek
}

// ── Computed: Tiket ────────────────────────────────────────
const tiketKeluar = computed(() =>
  tiketList.value.filter(t => (t.status ?? '').toLowerCase() === 'keluar')
)

const filteredTiket = computed(() => {
  let list = tiketKeluar.value
  if (filterTiket.value === 'hari_ini')   list = list.filter(t => isHariIni(t.waktu_keluar))
  if (filterTiket.value === 'minggu_ini') list = list.filter(t => isMingguIni(t.waktu_keluar))
  const q = searchTiket.value.trim().toLowerCase()
  if (q) list = list.filter(t => t.kode_tiket?.toLowerCase().includes(q))
  return list
})

const totalPendapatanTiketFiltered = computed(() =>
  filteredTiket.value.reduce((sum, t) => sum + Number(t.total_bayar || 0), 0)
)

// ── Computed: Member ────────────────────────────────────────
const filteredMember = computed(() => {
  if (filterMember.value === 'lunas')       return memberList.value.filter(m => (m.status ?? '').toLowerCase() === 'lunas')
  if (filterMember.value === 'belum_lunas') return memberList.value.filter(m => (m.status ?? '').toLowerCase() !== 'lunas')
  return memberList.value
})

const totalPendapatanMemberFiltered = computed(() =>
  filteredMember.value
    .filter(m => (m.status ?? '').toLowerCase() === 'lunas')
    .reduce((sum, m) => sum + Number(m.jumlah_bayar || 0), 0)
)

// ── Computed: Statistik ─────────────────────────────────────
const totalKendaraanHariIni = computed(() =>
  tiketKeluar.value.filter(t => isHariIni(t.waktu_keluar)).length
)

const totalPemasukanTiketHariIni = computed(() =>
  tiketKeluar.value
    .filter(t => isHariIni(t.waktu_keluar))
    .reduce((sum, t) => sum + Number(t.total_bayar || 0), 0)
)

const totalPemasukanMemberHariIni = computed(() =>
  memberList.value
    .filter(m => (m.status ?? '').toLowerCase() === 'lunas' && isHariIni(m.tanggal_bayar))
    .reduce((sum, m) => sum + Number(m.jumlah_bayar || 0), 0)
)

const totalPemasukanHariIni = computed(() =>
  totalPemasukanTiketHariIni.value + totalPemasukanMemberHariIni.value
)

const totalMemberAktif = computed(() =>
  memberList.value.filter(m => (m.status ?? '').toLowerCase() === 'lunas').length
)

// Semua waktu (tidak dibatasi hari ini)
const totalKendaraanSemua = computed(() => tiketKeluar.value.length)

const totalPemasukanSemua = computed(() => {
  const tiket  = tiketKeluar.value.reduce((sum, t) => sum + Number(t.total_bayar || 0), 0)
  const member = memberList.value
    .filter(m => (m.status ?? '').toLowerCase() === 'lunas')
    .reduce((sum, m) => sum + Number(m.jumlah_bayar || 0), 0)
  return tiket + member
})

// ── Fetch ──────────────────────────────────────────────────
async function fetchData() {
  loading.value = true
  error.value   = ''
  try {
    const [tiketRes, memberRes]: any[] = await Promise.all([
      $fetch(`${apiBase}/tiket`,  { headers: { Accept: 'application/json' } }),
      $fetch(`${apiBase}/member`, { headers: { Accept: 'application/json' } }),
    ])
    tiketList.value  = tiketRes?.data  ?? tiketRes  ?? []
    memberList.value = memberRes?.data ?? memberRes ?? []
  } catch (e: any) {
    error.value = e?.data?.message ?? e?.message ?? 'Gagal memuat data.'
  } finally {
    loading.value = false
  }
}

async function refreshData() { await fetchData() }

onMounted(fetchData)
</script>
