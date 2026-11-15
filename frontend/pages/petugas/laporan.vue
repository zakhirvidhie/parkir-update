<template>
  <div class="min-h-screen bg-slate-950 text-white p-4 md:p-6">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <button
        @click="navigateTo('/')"
        class="flex items-center gap-2 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors text-sm cursor-pointer"
      >
        ← Dashboard
      </button>
      <div>
        <h1 class="text-2xl font-bold text-white">📊 Laporan Parkir</h1>
        <p class="text-slate-400 text-sm">Rekap & statistik pendapatan parkir</p>
      </div>
    </div>

    <!-- Filter Tanggal -->
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-4 mb-6">
      <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3">🗓️ Filter Tanggal</h2>
      <div class="flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs text-slate-400 mb-1">Dari</label>
          <input
            v-model="filterDari"
            type="date"
            class="bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500"
          />
        </div>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Sampai</label>
          <input
            v-model="filterSampai"
            type="date"
            class="bg-slate-800 border border-slate-600 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-emerald-500"
          />
        </div>
        <button
          @click="terapkanFilter"
          class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium rounded-lg transition-colors cursor-pointer"
        >
          Terapkan Filter
        </button>
        <button
          @click="resetFilter"
          class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-300 text-sm font-medium rounded-lg transition-colors cursor-pointer"
        >
          Reset
        </button>
        <div v-if="loading" class="flex items-center gap-2 text-slate-400 text-sm">
          <span class="animate-spin">⏳</span> Memuat data...
        </div>
      </div>
    </div>

    <!-- Statistik Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
      <div class="bg-slate-900 border border-emerald-800/50 rounded-xl p-4">
        <div class="text-2xl mb-1">💰</div>
        <p class="text-xs text-slate-400 mb-1">Pendapatan Tiket</p>
        <p class="text-emerald-400 font-bold text-sm">{{ formatRupiah(statPendapatanTiket) }}</p>
      </div>
      <div class="bg-slate-900 border border-cyan-800/50 rounded-xl p-4">
        <div class="text-2xl mb-1">🚗</div>
        <p class="text-xs text-slate-400 mb-1">Kendaraan Masuk</p>
        <p class="text-cyan-400 font-bold text-xl">{{ statKendaraanMasuk }}</p>
      </div>
      <div class="bg-slate-900 border border-teal-800/50 rounded-xl p-4">
        <div class="text-2xl mb-1">✅</div>
        <p class="text-xs text-slate-400 mb-1">Kendaraan Keluar</p>
        <p class="text-teal-400 font-bold text-xl">{{ statKendaraanKeluar }}</p>
      </div>
      <div class="bg-slate-900 border border-yellow-800/50 rounded-xl p-4">
        <div class="text-2xl mb-1">⏱️</div>
        <p class="text-xs text-slate-400 mb-1">Rata-rata Durasi</p>
        <p class="text-yellow-400 font-bold text-sm">{{ statRataDurasi }} jam</p>
      </div>
      <div class="bg-slate-900 border border-purple-800/50 rounded-xl p-4">
        <div class="text-2xl mb-1">👤</div>
        <p class="text-xs text-slate-400 mb-1">Member Aktif</p>
        <p class="text-purple-400 font-bold text-xl">{{ statMemberAktif }}</p>
      </div>
      <div class="bg-slate-900 border border-pink-800/50 rounded-xl p-4">
        <div class="text-2xl mb-1">🏢</div>
        <p class="text-xs text-slate-400 mb-1">Pendapatan Member</p>
        <p class="text-pink-400 font-bold text-sm">{{ formatRupiah(statPendapatanMember) }}</p>
      </div>
    </div>

    <!-- Bar Chart -->
    <div class="bg-slate-900 border border-slate-700 rounded-xl p-5 mb-6">
      <h2 class="text-sm font-semibold text-slate-300 mb-4">📈 Pendapatan Harian (Rentang Tanggal)</h2>
      <div class="flex items-end gap-1 h-32 overflow-x-auto pb-2">
        <div
          v-for="item in grafikData"
          :key="item.tanggal"
          class="flex flex-col items-center gap-1 min-w-[32px] group cursor-default"
        >
          <div class="relative w-full flex flex-col items-center justify-end h-24">
            <div
              class="w-6 bg-emerald-500 hover:bg-emerald-400 rounded-t transition-all duration-300 relative"
              :style="{ height: barHeight(item.total) }"
            >
              <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-slate-800 text-white text-[10px] px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10 border border-slate-600">
                {{ formatRupiahShort(item.total) }}
              </div>
            </div>
          </div>
          <span class="text-[9px] text-slate-500 rotate-45 origin-left translate-y-2">{{ item.tanggalLabel }}</span>
        </div>
        <div v-if="grafikData.length === 0" class="flex items-center justify-center w-full h-24 text-slate-500 text-sm">
          Tidak ada data dalam rentang tanggal ini
        </div>
      </div>
      <div class="flex justify-between text-xs text-slate-500 mt-4">
        <span>Max: {{ formatRupiah(maxGrafik) }}</span>
        <span>Total: {{ formatRupiah(statPendapatanTiket) }}</span>
      </div>
    </div>

    <!-- Tabel Rekap + Tombol Export -->
    <div class="bg-slate-900 border border-slate-700 rounded-xl overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-700">
        <h2 class="text-sm font-semibold text-slate-300">
          🎫 Rekap Tiket Keluar ({{ tiketFiltered.length }} tiket)
        </h2>
        <div class="flex gap-2">
          <button
            @click="exportCSV"
            :disabled="tiketFiltered.length === 0"
            class="flex items-center gap-2 px-3 py-1.5 bg-teal-700 hover:bg-teal-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-medium rounded-lg transition-colors cursor-pointer"
          >
            📥 Export CSV
          </button>
          <button
            @click="exportPDF"
            :disabled="tiketFiltered.length === 0 || loadingPdf"
            class="flex items-center gap-2 px-3 py-1.5 bg-red-700 hover:bg-red-600 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-medium rounded-lg transition-colors cursor-pointer"
          >
            <span v-if="loadingPdf" class="animate-spin">⏳</span>
            <span v-else>📄</span>
            {{ loadingPdf ? 'Membuat PDF...' : 'Download PDF' }}
          </button>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-16 text-slate-400">
        <span class="animate-spin text-2xl mr-3">⏳</span> Memuat data...
      </div>

      <!-- Empty -->
      <div v-else-if="tiketFiltered.length === 0" class="text-center py-16 text-slate-500">
        <div class="text-4xl mb-2">📭</div>
        <p>Tidak ada tiket keluar pada rentang tanggal ini</p>
      </div>

      <!-- Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-800 text-slate-400 text-xs uppercase tracking-wider">
            <tr>
              <th class="text-left px-4 py-3">No</th>
              <th class="text-left px-4 py-3">Kode</th>
              <th class="text-left px-4 py-3">Jenis</th>
              <th class="text-left px-4 py-3">Waktu Masuk</th>
              <th class="text-left px-4 py-3">Waktu Keluar</th>
              <th class="text-left px-4 py-3">Durasi</th>
              <th class="text-right px-4 py-3">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800">
            <tr v-for="(t, i) in tiketFiltered" :key="t.id" class="hover:bg-slate-800/50 transition-colors">
              <td class="px-4 py-3 text-slate-400">{{ i + 1 }}</td>
              <td class="px-4 py-3 font-mono text-cyan-400 font-bold">{{ t.kode_tiket }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                  :class="t.jenis_kendaraan === 'mobil' ? 'bg-blue-900/50 text-blue-300' : 'bg-orange-900/50 text-orange-300'"
                >
                  {{ t.jenis_kendaraan === 'mobil' ? '🚗' : '🏍️' }} {{ capitalize(t.jenis_kendaraan) }}
                </span>
              </td>
              <td class="px-4 py-3 text-slate-300 text-xs">{{ formatDateTime(t.waktu_masuk) }}</td>
              <td class="px-4 py-3 text-slate-300 text-xs">{{ formatDateTime(t.waktu_keluar) }}</td>
              <td class="px-4 py-3 text-slate-300">{{ hitungDurasi(t.waktu_masuk, t.waktu_keluar) }}</td>
              <td class="px-4 py-3 text-right font-bold text-emerald-400">{{ formatRupiah(t.total_bayar) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-slate-800/50">
            <tr>
              <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-slate-300">TOTAL PENDAPATAN</td>
              <td class="px-4 py-3 text-right font-black text-emerald-400 text-base">{{ formatRupiah(statPendapatanTiket) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { jsPDF } from 'jspdf'

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
  tanggal_mulai: string | null
  tanggal_bayar: string | null
}

const config = useRuntimeConfig()
const apiBase = String(config.public.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')

// ── State ──────────────────────────────────────────────────
const allTiket  = ref<Tiket[]>([])
const allMember = ref<Member[]>([])
const loading   = ref(false)
const loadingPdf = ref(false)

const today = new Date().toISOString().split('T')[0]
const filterDari   = ref(today)
const filterSampai = ref(today)
const appliedDari   = ref(today)
const appliedSampai = ref(today)

// ── Filter ─────────────────────────────────────────────────
const terapkanFilter = () => {
  appliedDari.value   = filterDari.value
  appliedSampai.value = filterSampai.value
}

const resetFilter = () => {
  filterDari.value    = today
  filterSampai.value  = today
  appliedDari.value   = today
  appliedSampai.value = today
}

// ── Helpers ────────────────────────────────────────────────
const dateOnly = (s: string | null) => (s ?? '').slice(0, 10)

const durasiJam = (masuk: string | null, keluar: string | null): number => {
  if (!masuk || !keluar) return 0
  return Math.max(1, Math.ceil((new Date(keluar).getTime() - new Date(masuk).getTime()) / 3600000))
}

const hitungDurasi = (masuk: string | null, keluar: string | null): string => {
  if (!masuk || !keluar) return '-'
  const jam = durasiJam(masuk, keluar)
  return `${jam} jam`
}

const formatRupiah = (val: number | string) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val || 0))

const formatRupiahShort = (val: number): string => {
  if (val >= 1_000_000) return `${(val / 1_000_000).toFixed(1)}jt`
  if (val >= 1_000)     return `${(val / 1_000).toFixed(0)}rb`
  return String(val)
}

const formatDateTime = (dt: string | null) => {
  if (!dt) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  }).format(new Date(dt))
}

const capitalize = (s: string) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '-'

// ── Computed: tiket dalam rentang ──────────────────────────
const tiketFiltered = computed(() =>
  allTiket.value.filter(t =>
    t.status === 'keluar' &&
    dateOnly(t.waktu_keluar) >= appliedDari.value &&
    dateOnly(t.waktu_keluar) <= appliedSampai.value
  )
)

const tiketDalamRentang = computed(() =>
  allTiket.value.filter(t =>
    dateOnly(t.waktu_masuk) >= appliedDari.value &&
    dateOnly(t.waktu_masuk) <= appliedSampai.value
  )
)

// ── Computed: statistik ────────────────────────────────────
const statPendapatanTiket = computed(() =>
  tiketFiltered.value.reduce((sum, t) => sum + Number(t.total_bayar || 0), 0)
)

const statKendaraanMasuk = computed(() => tiketDalamRentang.value.length)

const statKendaraanKeluar = computed(() => tiketFiltered.value.length)

const statRataDurasi = computed<string>(() => {
  if (tiketFiltered.value.length === 0) return '0'
  const total = tiketFiltered.value.reduce((sum, t) => sum + durasiJam(t.waktu_masuk, t.waktu_keluar), 0)
  return (total / tiketFiltered.value.length).toFixed(1)
})

const statMemberAktif = computed(() =>
  allMember.value.filter(m => (m.status || '').toLowerCase() === 'lunas').length
)

const statPendapatanMember = computed(() => {
  const now = new Date()
  const bulanIni = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}`
  return allMember.value
    .filter(m => (m.status || '').toLowerCase() === 'lunas' && (m.tanggal_mulai || '').slice(0, 7) === bulanIni)
    .reduce((sum, m) => sum + Number(m.jumlah_bayar || 0), 0)
})

// ── Grafik ─────────────────────────────────────────────────
function dateRange(dari: string, sampai: string): string[] {
  const dates: string[] = []
  const start = new Date(dari)
  const end   = new Date(sampai)
  if (isNaN(start.getTime()) || isNaN(end.getTime())) return dates
  const cur = new Date(start)
  while (cur <= end) {
    dates.push(cur.toISOString().split('T')[0])
    cur.setDate(cur.getDate() + 1)
  }
  return dates
}

const grafikData = computed(() =>
  dateRange(appliedDari.value, appliedSampai.value).map(d => ({
    tanggal: d,
    tanggalLabel: new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }),
    total: allTiket.value
      .filter(t => t.status === 'keluar' && dateOnly(t.waktu_keluar) === d)
      .reduce((sum, t) => sum + Number(t.total_bayar || 0), 0),
  }))
)

const maxGrafik = computed(() => grafikData.value.reduce((m, g) => Math.max(m, g.total), 0))

const barHeight = (val: number): string => {
  if (maxGrafik.value === 0) return '4px'
  return `${Math.max((val / maxGrafik.value) * 100, val > 0 ? 4 : 1)}%`
}

// ── Fetch ──────────────────────────────────────────────────
async function fetchData() {
  loading.value = true
  try {
    const [tiketRes, memberRes]: any[] = await Promise.all([
      $fetch(`${apiBase}/tiket`,  { headers: { Accept: 'application/json' } }),
      $fetch(`${apiBase}/member`, { headers: { Accept: 'application/json' } }),
    ])
    allTiket.value  = tiketRes?.data  ?? tiketRes  ?? []
    allMember.value = memberRes?.data ?? memberRes ?? []
  } catch (e) {
    console.error('Gagal fetch data laporan:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)

// ── Export CSV ─────────────────────────────────────────────
function exportCSV() {
  const rows: string[][] = [
    ['No', 'Kode Tiket', 'Jenis', 'Waktu Masuk', 'Waktu Keluar', 'Durasi (jam)', 'Total Bayar'],
    ...tiketFiltered.value.map((t, i) => [
      String(i + 1),
      t.kode_tiket,
      capitalize(t.jenis_kendaraan),
      t.waktu_masuk || '',
      t.waktu_keluar || '',
      hitungDurasi(t.waktu_masuk, t.waktu_keluar),
      String(t.total_bayar || 0),
    ]),
    ['', '', '', '', 'TOTAL', '', String(statPendapatanTiket.value)],
  ]
  const csv  = rows.map(r => r.map(c => `"${c.replace(/"/g, '""')}"`).join(',')).join('\r\n')
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
  const url  = URL.createObjectURL(blob)
  const a    = document.createElement('a')
  a.href     = url
  a.download = `laporan-parkir-${appliedDari.value}-sd-${appliedSampai.value}.csv`
  a.click()
  URL.revokeObjectURL(url)
}

// ── Export PDF ─────────────────────────────────────────────
function exportPDF() {
  loadingPdf.value = true
  try {
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' })

    // ── Header ──
    doc.setFontSize(16)
    doc.setFont('helvetica', 'bold')
    doc.text('LAPORAN PARKIR', 105, 18, { align: 'center' })
    doc.setFontSize(9)
    doc.setFont('helvetica', 'normal')
    doc.setTextColor(100, 116, 139)
    doc.text('Plaza Andalas Parking Management System', 105, 24, { align: 'center' })
    doc.text(`Periode: ${appliedDari.value} s/d ${appliedSampai.value}`, 105, 29, { align: 'center' })
    doc.text(`Dicetak: ${new Date().toLocaleString('id-ID')}`, 105, 34, { align: 'center' })
    doc.setTextColor(0, 0, 0)

    // Garis header
    doc.setDrawColor(15, 118, 110)
    doc.setLineWidth(0.6)
    doc.line(14, 38, 196, 38)

    // ── Ringkasan Statistik ──
    doc.setFontSize(9)
    doc.setFont('helvetica', 'bold')
    doc.text('RINGKASAN STATISTIK', 14, 45)

    const statsData = [
      ['Pendapatan Tiket', formatRupiah(statPendapatanTiket.value)],
      ['Kendaraan Masuk', String(statKendaraanMasuk.value)],
      ['Kendaraan Keluar', String(statKendaraanKeluar.value)],
      ['Rata-rata Durasi', `${statRataDurasi.value} jam`],
      ['Member Aktif', String(statMemberAktif.value)],
      ['Pendapatan Member', formatRupiah(statPendapatanMember.value)],
    ]

    doc.setFont('helvetica', 'normal')
    doc.setFontSize(8)
    statsData.forEach(([label, val], i) => {
      const col = i < 3 ? 0 : 1
      const row = i % 3
      const x = 14 + col * 92
      const y = 52 + row * 8
      doc.setTextColor(100, 116, 139)
      doc.text(label + ':', x, y)
      doc.setTextColor(15, 118, 110)
      doc.setFont('helvetica', 'bold')
      doc.text(val, x + 48, y)
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(0, 0, 0)
    })

    // Garis pemisah sebelum tabel
    doc.setDrawColor(226, 232, 240)
    doc.setLineWidth(0.3)
    doc.line(14, 76, 196, 76)

    // ── Header Tabel ──
    const tableStartY = 80
    const colX = [14, 22, 54, 76, 112, 148, 168]
    const colW = [7,  30, 20, 34,  34,  18,  28]
    const headers = ['No', 'Kode Tiket', 'Jenis', 'Waktu Masuk', 'Waktu Keluar', 'Durasi', 'Total']

    // Background header tabel
    doc.setFillColor(15, 118, 110)
    doc.rect(14, tableStartY - 4, 182, 8, 'F')
    doc.setTextColor(255, 255, 255)
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(7.5)
    headers.forEach((h, i) => {
      doc.text(h, colX[i], tableStartY, { align: i === 6 ? 'right' : 'left', maxWidth: colW[i] })
    })

    // ── Baris Tabel ──
    doc.setFont('helvetica', 'normal')
    doc.setFontSize(7.5)
    let y = tableStartY + 7

    tiketFiltered.value.forEach((t, idx) => {
      if (y > 268) {
        doc.addPage()
        y = 20
        // Ulangi header di halaman baru
        doc.setFillColor(15, 118, 110)
        doc.rect(14, y - 4, 182, 8, 'F')
        doc.setTextColor(255, 255, 255)
        doc.setFont('helvetica', 'bold')
        headers.forEach((h, i) => {
          doc.text(h, colX[i], y, { align: i === 6 ? 'right' : 'left', maxWidth: colW[i] })
        })
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(0, 0, 0)
        y += 7
      }

      // Baris zebra
      if (idx % 2 === 0) {
        doc.setFillColor(240, 253, 250)
        doc.rect(14, y - 4, 182, 6.5, 'F')
      }

      doc.setTextColor(0, 0, 0)
      doc.text(String(idx + 1), colX[0], y)
      doc.setTextColor(0, 120, 180)
      doc.setFont('helvetica', 'bold')
      doc.text(t.kode_tiket, colX[1], y, { maxWidth: colW[1] })
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(0, 0, 0)
      doc.text(t.jenis_kendaraan === 'mobil' ? 'Mobil' : 'Motor', colX[2], y)
      doc.setTextColor(71, 85, 105)
      const masukStr = formatDateTime(t.waktu_masuk)
      const keluarStr = formatDateTime(t.waktu_keluar)
      doc.text(masukStr.length > 16 ? masukStr.slice(0, 16) : masukStr, colX[3], y, { maxWidth: colW[3] })
      doc.text(keluarStr.length > 16 ? keluarStr.slice(0, 16) : keluarStr, colX[4], y, { maxWidth: colW[4] })
      doc.setTextColor(0, 0, 0)
      doc.text(hitungDurasi(t.waktu_masuk, t.waktu_keluar), colX[5], y)
      doc.setTextColor(15, 118, 110)
      doc.setFont('helvetica', 'bold')
      doc.text(formatRupiah(t.total_bayar), 196, y, { align: 'right' })
      doc.setFont('helvetica', 'normal')
      doc.setTextColor(0, 0, 0)

      // Garis bawah baris
      doc.setDrawColor(226, 232, 240)
      doc.setLineWidth(0.1)
      doc.line(14, y + 2.5, 196, y + 2.5)

      y += 6.5
    })

    // ── Baris Total ──
    doc.setFillColor(240, 253, 250)
    doc.rect(14, y - 1, 182, 8, 'F')
    doc.setDrawColor(15, 118, 110)
    doc.setLineWidth(0.5)
    doc.line(14, y - 1, 196, y - 1)
    doc.setFont('helvetica', 'bold')
    doc.setFontSize(8)
    doc.setTextColor(30, 41, 59)
    doc.text('TOTAL PENDAPATAN', colX[5], y + 4)
    doc.setTextColor(15, 118, 110)
    doc.text(formatRupiah(statPendapatanTiket.value), 196, y + 4, { align: 'right' })

    // ── Footer ──
    const pageCount = (doc as any).internal.getNumberOfPages()
    for (let pg = 1; pg <= pageCount; pg++) {
      doc.setPage(pg)
      doc.setFont('helvetica', 'normal')
      doc.setFontSize(7)
      doc.setTextColor(148, 163, 184)
      doc.text(`Halaman ${pg} / ${pageCount}`, 196, 290, { align: 'right' })
      doc.text('Plaza Andalas Parking Management System', 14, 290)
    }

    doc.save(`laporan-parkir-${appliedDari.value}-sd-${appliedSampai.value}.pdf`)
  } catch (err) {
    console.error('Gagal buat PDF:', err)
    alert('Gagal membuat PDF. Silakan coba lagi.')
  } finally {
    loadingPdf.value = false
  }
}
</script>
