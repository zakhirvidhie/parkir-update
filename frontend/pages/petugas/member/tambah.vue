<script setup lang="ts">
import { ref, computed } from 'vue'
import QRCode from 'qrcode'

// =========================================================
// CONFIG
// =========================================================

const config = useRuntimeConfig()
const apiBase = String(config.public.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')

// =========================================================
// FORM
// =========================================================

const namaMember = ref('')
const namaPerusahaan = ref('')
const hargaMember = 150000
const jumlahBayar = ref<number>(150000)

const loading = ref(false)
const error = ref('')
const success = ref('')

// INVOICE MODAL STATE
const showInvoice = ref(false)
const createdMember = ref<any>(null)
const invoiceQr = ref('')
const invoiceDate = ref('')

// =========================================================
// FORMAT RUPIAH & TANGGAL
// =========================================================

const formatRupiah = (value: any) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(value || 0))
}

const formatTanggal = (dateStr: string) => {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    return d.toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    })
  } catch {
    return dateStr
  }
}

// =========================================================
// KEMBALIAN & STATUS
// =========================================================

const kembalian = computed(() => {
  const bayar = Number(jumlahBayar.value || 0)
  if (bayar <= hargaMember) {
    return 0
  }
  return bayar - hargaMember
})

const statusPembayaran = computed(() => {
  const bayar = Number(jumlahBayar.value || 0)
  if (bayar >= hargaMember) {
    return 'Lunas'
  }
  return 'Belum Lunas'
})

// =========================================================
// SIMPAN MEMBER
// =========================================================

const simpanMember = async () => {
  error.value = ''
  success.value = ''

  if (loading.value) return

  if (!namaMember.value.trim()) {
    error.value = 'Nama member wajib diisi.'
    return
  }

  if (!namaPerusahaan.value.trim()) {
    error.value = 'Nama perusahaan wajib diisi.'
    return
  }

  const bayar = Number(jumlahBayar.value || 0)
  if (bayar < hargaMember) {
    error.value = `Pembayaran member per bulan adalah ${formatRupiah(hargaMember)}. Uang bayar tidak boleh kurang.`
    return
  }

  loading.value = true

  try {
    const payload = {
      nama_member: namaMember.value.trim(),
      nama_perusahaan: namaPerusahaan.value.trim(),
      jumlah_bayar: bayar
    }

    const response: any = await $fetch(`${apiBase}/member`, {
      method: 'POST',
      body: payload,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
      }
    })

    const memberData = response?.data || {}
    createdMember.value = memberData
    invoiceDate.value = new Date().toLocaleString('id-ID', {
      dateStyle: 'full',
      timeStyle: 'short'
    })

    // Generate QR Code for invoice
    try {
      const qrContent = JSON.stringify({
        id: memberData.id,
        kode_member: memberData.kode_member,
        nama_member: memberData.nama_member,
        token: memberData.token
      })
      invoiceQr.value = await QRCode.toDataURL(qrContent, {
        width: 250,
        margin: 2,
        errorCorrectionLevel: 'M'
      })
    } catch (qrErr) {
      console.error('Gagal generate QR invoice:', qrErr)
    }

    success.value = response?.message || 'Member berhasil ditambahkan dan lunas.'
    showInvoice.value = true

    // Reset Form input
    namaMember.value = ''
    namaPerusahaan.value = ''
    jumlahBayar.value = 150000

  } catch (err: any) {
    console.error('Gagal menambahkan member:', err)
    if (err?.data?.message) {
      error.value = err.data.message
    } else if (err?.data?.errors) {
      const errors = err.data.errors
      const firstError = Object.values(errors)?.[0]?.[0]
      error.value = (firstError as string) || 'Data member tidak valid.'
    } else {
      error.value = 'Member gagal ditambahkan. Pastikan server Laravel sedang berjalan.'
    }
  } finally {
    loading.value = false
  }
}

// =========================================================
// CETAK INVOICE
// =========================================================

const cetakInvoice = () => {
  window.print()
}

// =========================================================
// KEMBALI
// =========================================================

const kembali = () => {
  navigateTo('/petugas/member/select')
}
</script>

<template>
  <div class="min-h-screen bg-[#eef9fc] flex items-start justify-center px-4 py-10">

    <!-- CARD FORM TAMBAH MEMBER -->
    <div class="w-[440px] max-w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8">

      <!-- HEADER -->
      <div class="text-center mb-6">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-3 font-black text-xl shadow-sm">
          +
        </div>
        <h1 class="text-2xl font-black text-slate-800">
          Tambah Member
        </h1>
        <p class="text-xs text-slate-500 mt-1">
          Daftarkan member baru dengan tarif langganan bulanan
        </p>
      </div>

      <!-- ERROR MESSAGE -->
      <div
        v-if="error"
        class="mb-5 rounded-xl border border-red-200 bg-red-50 p-3.5 text-xs font-semibold text-red-600 flex items-center gap-2"
      >
        <span>⚠️</span>
        <span>{{ error }}</span>
      </div>

      <!-- SUCCESS MESSAGE -->
      <div
        v-if="success && !showInvoice"
        class="mb-5 rounded-xl border border-green-200 bg-green-50 p-3.5 text-xs font-semibold text-green-600 flex items-center gap-2"
      >
        <span class="text-green-600 flex-shrink-0">✅</span>
        <span>{{ success }}</span>
      </div>

      <!-- FORM FIELDS -->
      <form @submit.prevent="simpanMember" class="space-y-4">

        <!-- NAMA MEMBER -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">
            Nama Member
          </label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">👤</span>
            <input
              v-model="namaMember"
              type="text"
              placeholder="Contoh: Budi Santoso"
              required
              class="w-full h-11 rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-sm text-slate-800 outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-100 transition"
            />
          </div>
        </div>

        <!-- PERUSAHAAN -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">
            Nama Perusahaan / Instansi
          </label>
          <div class="relative">
            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🏢</span>
            <input
              v-model="namaPerusahaan"
              type="text"
              placeholder="Contoh: PT. Sumber Makmur"
              required
              class="w-full h-11 rounded-xl border border-slate-200 bg-slate-50/50 pl-11 pr-4 text-sm text-slate-800 outline-none focus:border-blue-600 focus:bg-white focus:ring-2 focus:ring-blue-100 transition"
            />
          </div>
        </div>

        <!-- INFO BIAYA BULANAN -->
        <div class="rounded-2xl bg-blue-50 border border-blue-100 p-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-sm">
                <span class="text-white text-lg">💵</span>
              </div>
              <div>
                <p class="text-[11px] font-bold text-blue-900 uppercase tracking-wider">
                  Tarif Langganan
                </p>
                <p class="text-xs text-blue-700">
                  Paket 1 Bulan (Langsung Lunas)
                </p>
              </div>
            </div>
            <div class="text-right">
              <span class="text-base font-black text-blue-700">
                {{ formatRupiah(hargaMember) }}
              </span>
            </div>
          </div>
        </div>

        <!-- JUMLAH BAYAR -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1.5">
            Nominal Pembayaran (Rp)
          </label>
          <input
            v-model.number="jumlahBayar"
            type="number"
            min="150000"
            step="1000"
            placeholder="150000"
            class="w-full h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-800 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-100 transition"
          />
          <p class="text-[11px] text-slate-500 mt-1">
            * Minimal pembayaran adalah {{ formatRupiah(hargaMember) }} agar langsung lunas.
          </p>
        </div>

        <!-- RINCIAN BAYAR -->
        <div class="rounded-2xl bg-slate-50 border border-slate-200/80 p-4 text-xs space-y-2">
          <div class="flex justify-between text-slate-600">
            <span>Tagihan:</span>
            <span class="font-bold text-slate-800">{{ formatRupiah(hargaMember) }}</span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Uang Diterima:</span>
            <span class="font-bold text-slate-800">{{ formatRupiah(jumlahBayar) }}</span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Kembalian:</span>
            <span class="font-bold text-emerald-600">{{ formatRupiah(kembalian) }}</span>
          </div>
          <div class="pt-2 border-t border-slate-200 flex justify-between items-center">
            <span class="font-bold text-slate-700">Status Pembayaran:</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700">
              {{ statusPembayaran }}
            </span>
          </div>
        </div>

        <!-- BUTTON SIMPAN -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full h-12 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-lg shadow-blue-500/25 transition disabled:opacity-50 flex items-center justify-center gap-2 mt-4 cursor-pointer"
        >
          <span v-if="loading">Menyimpan & Membuat Invoice...</span>
          <span v-else>Bayar & Simpan Member (Lunas)</span>
        </button>

        <!-- BUTTON KEMBALI -->
        <button
          type="button"
          @click="kembali"
          class="w-full text-center text-xs font-semibold text-slate-500 hover:text-slate-800 pt-2 transition cursor-pointer"
        >
          ← Kembali ke Data Member
        </button>

      </form>

    </div>

    <!-- ===================================================== -->
    <!-- MODAL INVOICE PEMBAYARAN MEMBER (POPUP RESMI) -->
    <!-- ===================================================== -->
    <div
      v-if="showInvoice && createdMember"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 print:shadow-none print:border-none print:m-0 print:w-full">

        <!-- INVOICE CONTENT TO PRINT -->
        <div id="invoice-printable" class="p-8">

          <!-- INVOICE HEADER -->
          <div class="flex items-start justify-between border-b border-slate-200 pb-6 mb-6">
            <div>
              <div class="flex items-center gap-2 mb-1">
                <span class="w-8 h-8 rounded-lg bg-blue-600 text-white font-black flex items-center justify-center text-base">P</span>
                <span class="font-black text-xl tracking-tight text-slate-900">PARKZONE</span>
              </div>
              <p class="text-xs text-slate-500">Plaza Andalas Smart Parking</p>
              <p class="text-[11px] text-slate-400">Jl. Pemuda No. 1, Padang</p>
            </div>
            <div class="text-right">
              <span class="inline-block px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 uppercase tracking-wider mb-1">
                LUNAS
              </span>
              <p class="text-xs font-mono font-bold text-slate-700">
                INV-{{ createdMember.kode_member || createdMember.id }}
              </p>
              <p class="text-[10px] text-slate-400 mt-0.5">
                {{ invoiceDate }}
              </p>
            </div>
          </div>

          <!-- MEMBER INFO CARD -->
          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 mb-6">
            <div class="grid grid-cols-2 gap-3 text-xs">
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Nama Member</span>
                <span class="font-bold text-slate-800 text-sm">{{ createdMember.nama_member }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Perusahaan</span>
                <span class="font-bold text-slate-800">{{ createdMember.nama_perusahaan }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Kode Member</span>
                <span class="font-mono font-bold text-blue-600">{{ createdMember.kode_member }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Periode Aktif</span>
                <span class="font-bold text-slate-800">{{ formatTanggal(createdMember.tanggal_mulai) }} - {{ formatTanggal(createdMember.tanggal_expired) }}</span>
              </div>
            </div>
          </div>

          <!-- TABLE RINCIAN PEMBAYARAN -->
          <table class="w-full text-xs mb-6">
            <thead>
              <tr class="border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                <th class="text-left pb-2">Deskripsi Layanan</th>
                <th class="text-center pb-2">Durasi</th>
                <th class="text-right pb-2">Jumlah</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-800">
              <tr>
                <td class="py-3 font-semibold">
                  Langganan Parkir Bulanan Member
                  <span class="block text-[10px] text-slate-400 font-normal">Akses parkir tanpa batas selama masa aktif</span>
                </td>
                <td class="py-3 text-center font-bold">1 Bulan</td>
                <td class="py-3 text-right font-bold">{{ formatRupiah(createdMember.total_harga || 150000) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t-2 border-slate-200">
                <td colspan="2" class="pt-3 font-bold text-slate-700">Total Tagihan:</td>
                <td class="pt-3 text-right font-black text-sm text-slate-900">{{ formatRupiah(createdMember.total_harga || 150000) }}</td>
              </tr>
              <tr>
                <td colspan="2" class="pt-1.5 text-slate-500">Jumlah Dibayar:</td>
                <td class="pt-1.5 text-right font-bold text-slate-800">{{ formatRupiah(createdMember.jumlah_bayar || 150000) }}</td>
              </tr>
              <tr>
                <td colspan="2" class="pt-1.5 text-slate-500">Kembalian:</td>
                <td class="pt-1.5 text-right font-bold text-emerald-600">{{ formatRupiah(createdMember.kembalian || 0) }}</td>
              </tr>
            </tfoot>
          </table>

          <!-- QR CODE ACCESS AREA -->
          <div class="bg-blue-50/70 rounded-2xl p-4 border border-blue-100 flex items-center justify-between gap-4 mb-4">
            <div class="text-xs">
              <span class="text-xs font-black text-blue-900 block mb-1">QR Code Akses Parkir</span>
              <p class="text-[11px] text-slate-600 leading-relaxed">
                Scan QR ini di gerbang masuk dan keluar untuk menggunakan hak akses parkir.
              </p>
              <p class="font-mono text-[10px] text-blue-700 font-bold mt-1.5">
                Token: {{ createdMember.token ? createdMember.token.slice(0, 16) + '...' : '-' }}
              </p>
            </div>
            <div v-if="invoiceQr" class="bg-white p-2 rounded-xl shadow-sm border border-blue-200 flex-shrink-0">
              <img :src="invoiceQr" alt="QR Member" class="w-20 h-20" />
            </div>
          </div>

          <div class="text-center text-[10px] text-slate-400 pt-2 border-t border-slate-100">
            Terima kasih atas pembayaran Anda • Struk ini adalah bukti pembayaran yang sah
          </div>

        </div>

        <!-- MODAL ACTION BUTTONS (HIDDEN IN PRINT) -->
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex items-center justify-end gap-3 print:hidden">
          <button
            type="button"
            @click="cetakInvoice"
            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
          >
            <span>🖨️</span>
            <span>Cetak Invoice</span>
          </button>
          <button
            type="button"
            @click="kembali"
            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition cursor-pointer"
          >
            Selesai & Lihat Data Member
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #invoice-printable,
  #invoice-printable * {
    visibility: visible;
  }
  #invoice-printable {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 20px;
    margin: 0;
    box-shadow: none !important;
    border: none !important;
  }
}
</style>