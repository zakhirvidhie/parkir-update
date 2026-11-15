<template>
  <div class="min-h-screen bg-slate-950 text-white">
    <!-- Header -->
    <div class="bg-slate-900 border-b border-slate-800 px-4 py-4">
      <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
          <button
            @click="navigateTo('/')"
            class="flex items-center gap-2 text-slate-400 hover:text-cyan-400 transition-colors text-sm font-medium"
          >
            ← Dashboard
          </button>
          <span class="text-slate-700">|</span>
          <h1 class="text-xl font-bold text-white flex items-center gap-2">
            🎫 Daftar Tiket Parkir
          </h1>
        </div>
        <button
          @click="fetchTiket"
          :disabled="loading"
          class="flex items-center gap-2 bg-cyan-600 hover:bg-cyan-500 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
        >
          <span :class="{ 'animate-spin': loading }">🔄</span>
          Refresh
        </button>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">

      <!-- Statistik Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Total Tiket -->
        <div class="bg-slate-900 border border-slate-800 rounded-xl p-5 flex items-center gap-4">
          <div class="text-4xl">🎫</div>
          <div>
            <p class="text-slate-400 text-xs uppercase tracking-wide">Total Tiket</p>
            <p class="text-2xl font-bold text-white">{{ tiketList.length }}</p>
          </div>
        </div>

        <!-- Tiket Aktif (Masuk) -->
        <div class="bg-slate-900 border border-emerald-800/50 rounded-xl p-5 flex items-center gap-4">
          <div class="text-4xl">🅿️</div>
          <div>
            <p class="text-slate-400 text-xs uppercase tracking-wide">Sedang Parkir</p>
            <p class="text-2xl font-bold text-emerald-400">{{ tiketMasuk.length }}</p>
          </div>
        </div>

        <!-- Pendapatan Hari Ini -->
        <div class="bg-slate-900 border border-cyan-800/50 rounded-xl p-5 flex items-center gap-4">
          <div class="text-4xl">💰</div>
          <div>
            <p class="text-slate-400 text-xs uppercase tracking-wide">Pendapatan Hari Ini</p>
            <p class="text-xl font-bold text-cyan-400">{{ formatRupiah(pendapatanHariIni) }}</p>
          </div>
        </div>
      </div>

      <!-- Filter & Search -->
      <div class="bg-slate-900 border border-slate-800 rounded-xl p-4 space-y-4">
        <!-- Tab Filter -->
        <div class="flex gap-2 flex-wrap">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            :class="[
              'px-4 py-2 rounded-lg text-sm font-medium transition-colors',
              activeTab === tab.value
                ? 'bg-cyan-600 text-white'
                : 'bg-slate-800 text-slate-400 hover:text-white hover:bg-slate-700'
            ]"
          >
            {{ tab.label }}
            <span class="ml-1 text-xs opacity-75">({{ getTabCount(tab.value) }})</span>
          </button>
        </div>

        <!-- Search -->
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔍</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Cari kode tiket..."
            class="w-full bg-slate-800 border border-slate-700 text-white placeholder-slate-500 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-cyan-500 transition-colors"
          />
        </div>
      </div>

      <!-- Tabel Tiket -->
      <div class="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">

        <!-- Loading Skeleton -->
        <template v-if="loading && tiketList.length === 0">
          <div class="p-4 space-y-3">
            <div v-for="i in 5" :key="i" class="animate-pulse flex gap-4 items-center">
              <div class="h-4 bg-slate-800 rounded w-8"></div>
              <div class="h-4 bg-slate-800 rounded w-32"></div>
              <div class="h-4 bg-slate-800 rounded w-24"></div>
              <div class="h-4 bg-slate-800 rounded w-28"></div>
              <div class="h-4 bg-slate-800 rounded w-28"></div>
              <div class="h-4 bg-slate-800 rounded w-36"></div>
              <div class="h-4 bg-slate-800 rounded w-36"></div>
              <div class="h-6 bg-slate-800 rounded-full w-16"></div>
            </div>
          </div>
        </template>

        <!-- Empty State -->
        <template v-else-if="filteredTiket.length === 0">
          <div class="flex flex-col items-center justify-center py-16 text-center px-4">
            <div class="text-6xl mb-4">🎫</div>
            <p class="text-slate-400 text-lg font-medium">Belum ada tiket parkir</p>
            <p class="text-slate-600 text-sm mt-1">
              {{ searchQuery ? 'Tidak ada tiket yang cocok dengan pencarian Anda.' : 'Tiket akan muncul di sini setelah kendaraan masuk.' }}
            </p>
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="mt-4 text-cyan-400 hover:text-cyan-300 text-sm underline"
            >
              Hapus filter
            </button>
          </div>
        </template>

        <!-- Tabel Data -->
        <template v-else>
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-900/80">
                  <th class="text-left text-slate-400 font-medium px-4 py-3 w-10">No</th>
                  <th class="text-left text-slate-400 font-medium px-4 py-3">Kode Tiket</th>
                  <th class="text-left text-slate-400 font-medium px-4 py-3">Jenis Kendaraan</th>
                  <th class="text-right text-slate-400 font-medium px-4 py-3">Tarif/Jam</th>
                  <th class="text-right text-slate-400 font-medium px-4 py-3">Total Bayar</th>
                  <th class="text-left text-slate-400 font-medium px-4 py-3">Waktu Masuk</th>
                  <th class="text-left text-slate-400 font-medium px-4 py-3">Waktu Keluar</th>
                  <th class="text-center text-slate-400 font-medium px-4 py-3">Status</th>
                  <th class="text-center text-slate-400 font-medium px-4 py-3">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(tiket, index) in filteredTiket"
                  :key="tiket.id"
                  class="border-b border-slate-800/60 hover:bg-slate-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-slate-500">{{ index + 1 }}</td>
                  <td class="px-4 py-3">
                    <span class="font-mono font-semibold text-cyan-400">{{ tiket.kode_tiket }}</span>
                  </td>
                  <td class="px-4 py-3 text-slate-300">
                    <span class="flex items-center gap-2">
                      {{ getKendaraanEmoji(tiket.jenis_kendaraan) }}
                      {{ tiket.jenis_kendaraan }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right text-slate-300">{{ formatRupiah(tiket.tarif_per_jam) }}</td>
                  <td class="px-4 py-3 text-right font-semibold text-emerald-400">
                    {{ tiket.total_bayar ? formatRupiah(tiket.total_bayar) : '-' }}
                  </td>
                  <td class="px-4 py-3 text-slate-300 whitespace-nowrap">{{ formatDateTime(tiket.waktu_masuk) }}</td>
                  <td class="px-4 py-3 text-slate-300 whitespace-nowrap">
                    {{ tiket.waktu_keluar ? formatDateTime(tiket.waktu_keluar) : '-' }}
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span
                      :class="[
                        'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold',
                        tiket.status === 'masuk'
                          ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30'
                          : 'bg-slate-700/60 text-slate-400 border border-slate-600/30'
                      ]"
                    >
                      <span class="w-1.5 h-1.5 rounded-full"
                        :class="tiket.status === 'masuk' ? 'bg-emerald-400' : 'bg-slate-500'"
                      ></span>
                      {{ tiket.status === 'masuk' ? 'Parkir' : 'Keluar' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <button
                      @click="confirmHapus(tiket)"
                      class="text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-colors p-2 rounded-lg"
                      title="Hapus tiket"
                    >
                      🗑️
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Footer tabel -->
          <div class="px-4 py-3 border-t border-slate-800 text-slate-500 text-xs flex justify-between items-center">
            <span>Menampilkan {{ filteredTiket.length }} dari {{ tiketList.length }} tiket</span>
            <span v-if="lastRefresh">Terakhir diperbarui: {{ formatDateTime(lastRefresh) }}</span>
          </div>
        </template>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <Transition name="modal">
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="showDeleteModal = false"
      >
        <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6 w-full max-w-sm shadow-2xl">
          <div class="text-center mb-5">
            <div class="text-5xl mb-3">⚠️</div>
            <h3 class="text-lg font-bold text-white mb-1">Hapus Tiket?</h3>
            <p class="text-slate-400 text-sm">
              Anda akan menghapus tiket
              <span class="font-mono font-bold text-cyan-400">{{ selectedTiket?.kode_tiket }}</span>.
              Tindakan ini tidak dapat dibatalkan.
            </p>
          </div>
          <div class="flex gap-3">
            <button
              @click="showDeleteModal = false"
              class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium py-2.5 rounded-lg transition-colors"
            >
              Batal
            </button>
            <button
              @click="hapusTiket"
              :disabled="deleting"
              class="flex-1 bg-red-600 hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-medium py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2"
            >
              <span v-if="deleting" class="animate-spin">⏳</span>
              <span>{{ deleting ? 'Menghapus...' : 'Hapus' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
interface Tiket {
  id: number
  kode_tiket: string
  jenis_kendaraan: string
  tarif_per_jam: number | string
  total_bayar: number | string | null
  status: 'masuk' | 'keluar'
  waktu_masuk: string
  waktu_keluar: string | null
}

type TabValue = 'semua' | 'masuk' | 'keluar'

const config = useRuntimeConfig()
const apiBase = config.public.apiBase

// State
const tiketList = ref<Tiket[]>([])
const loading = ref(false)
const deleting = ref(false)
const searchQuery = ref('')
const activeTab = ref<TabValue>('semua')
const showDeleteModal = ref(false)
const selectedTiket = ref<Tiket | null>(null)
const lastRefresh = ref<string | null>(null)
let refreshInterval: ReturnType<typeof setInterval> | null = null

// Tabs
const tabs = [
  { label: 'Semua', value: 'semua' as TabValue },
  { label: '🟢 Masuk', value: 'masuk' as TabValue },
  { label: '⚫ Keluar', value: 'keluar' as TabValue },
]

// Computed: tiket aktif
const tiketMasuk = computed(() => tiketList.value.filter(t => t.status === 'masuk'))

// Computed: pendapatan hari ini (tiket keluar hari ini)
const pendapatanHariIni = computed(() => {
  const today = new Date().toISOString().slice(0, 10)
  return tiketList.value
    .filter(t => t.status === 'keluar' && t.waktu_keluar?.startsWith(today))
    .reduce((sum, t) => sum + Number(t.total_bayar || 0), 0)
})

// Computed: tiket setelah filter tab & search
const filteredTiket = computed(() => {
  let list = tiketList.value

  if (activeTab.value !== 'semua') {
    list = list.filter(t => t.status === activeTab.value)
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.trim().toLowerCase()
    list = list.filter(t => t.kode_tiket.toLowerCase().includes(q))
  }

  return list
})

// Helpers
const getTabCount = (tab: TabValue) => {
  if (tab === 'semua') return tiketList.value.length
  return tiketList.value.filter(t => t.status === tab).length
}

const formatRupiah = (val: number | string) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val || 0))

const formatDateTime = (dt: string | null) => {
  if (!dt) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(dt))
}

const getKendaraanEmoji = (jenis: string) => {
  const map: Record<string, string> = {
    motor: '🏍️',
    mobil: '🚗',
    truk: '🚛',
    bus: '🚌',
  }
  return map[jenis?.toLowerCase()] ?? '🚘'
}

// Fetch tiket
const fetchTiket = async () => {
  loading.value = true
  try {
    const res: any = await $fetch(`${apiBase}/tiket`, {
      headers: { Accept: 'application/json' },
    })
    tiketList.value = res?.data ?? res ?? []
    lastRefresh.value = new Date().toISOString()
  } catch (err) {
    console.error('Gagal memuat tiket:', err)
  } finally {
    loading.value = false
  }
}

// Konfirmasi hapus
const confirmHapus = (tiket: Tiket) => {
  selectedTiket.value = tiket
  showDeleteModal.value = true
}

// Hapus tiket
const hapusTiket = async () => {
  if (!selectedTiket.value) return
  deleting.value = true
  try {
    await $fetch(`${apiBase}/tiket/${selectedTiket.value.id}`, { method: 'DELETE' })
    tiketList.value = tiketList.value.filter(t => t.id !== selectedTiket.value!.id)
    showDeleteModal.value = false
    selectedTiket.value = null
  } catch (err) {
    console.error('Gagal menghapus tiket:', err)
  } finally {
    deleting.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchTiket()
  refreshInterval = setInterval(fetchTiket, 30_000)
})

onUnmounted(() => {
  if (refreshInterval) clearInterval(refreshInterval)
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .bg-slate-900,
.modal-leave-active .bg-slate-900 {
  transition: transform 0.2s ease;
}
.modal-enter-from .bg-slate-900,
.modal-leave-to .bg-slate-900 {
  transform: scale(0.95);
}
</style>
