<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

// =========================================================
// CONFIG
// =========================================================

const config = useRuntimeConfig()
const apiBase = String(config.public.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')

// =========================================================
// STATE
// =========================================================

const kodeInput    = ref('')
const platInput    = ref('')
const bayarInput   = ref<number | ''>('')
const loading      = ref(false)
const errorMsg     = ref('')
const inputRef     = ref<HTMLInputElement | null>(null)
const platRef      = ref<HTMLInputElement | null>(null)
const bayarRef     = ref<HTMLInputElement | null>(null)

// Hasil detect tipe: 'tiket' | 'member' | null
const modeScan = ref<'tiket' | 'member' | null>(null)

// Data tiket yang ditemukan (sebelum proses keluar)
const tiketDitemukan  = ref<any>(null)
// Data member yang ditemukan (sebelum proses keluar)
const memberDitemukan = ref<any>(null)

// Hasil setelah proses keluar
const hasilKeluar  = ref<any>(null)   // tiket
const hasilMember  = ref<any>(null)   // member

// =========================================================
// HELPERS & COMPUTED
// =========================================================

const formatRupiah = (val: number | string) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val || 0))

const formatWaktu = (val: string | null) => {
  if (!val) return '-'
  return new Date(val).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

const hitungDurasi = (masuk: string, keluar: string) =>
  Math.max(1, Math.ceil((new Date(keluar).getTime() - new Date(masuk).getTime()) / 3600000))

// Estimasi durasi parkir
const durasiSaatIni = computed(() => {
  if (!tiketDitemukan.value?.waktu_masuk) return 1
  return hitungDurasi(tiketDitemukan.value.waktu_masuk, new Date().toISOString())
})

// Total biaya parkir otomatis dari durasi * tarif
const totalBiayaOtomatis = computed(() => {
  if (!tiketDitemukan.value) return 0
  const tarif = Number(tiketDitemukan.value.tarif_per_jam || (tiketDitemukan.value.jenis_kendaraan === 'mobil' ? 5000 : 3000))
  return durasiSaatIni.value * tarif
})

// Kembalian otomatis realtime
const kembalian = computed(() => {
  const biaya = totalBiayaOtomatis.value
  const bayar = bayarInput.value === '' ? biaya : Number(bayarInput.value)
  return bayar - biaya
})

const fokusInput = () => setTimeout(() => inputRef.value?.focus(), 100)
const fokusPlat  = () => setTimeout(() => platRef.value?.focus(), 150)

onMounted(() => fokusInput())

// =========================================================
// PARSE INPUT — deteksi otomatis tiket atau member
// =========================================================

const parseInput = (raw: string): { type: 'tiket' | 'member'; kode: string } | null => {
  const teks = raw.trim()

  // 1. JSON
  try {
    const json = JSON.parse(teks)
    if (json?.kode_member) return { type: 'member', kode: String(json.kode_member) }
    if (json?.kode_tiket)  return { type: 'tiket',  kode: String(json.kode_tiket).toUpperCase() }
    if (json?.token)       return { type: 'member', kode: String(json.token) }
  } catch { /* bukan JSON */ }

  // 2. Pola kode tiket murni: A001, A002, dst.
  if (/^A\d{3,}$/i.test(teks))    return { type: 'tiket',  kode: teks.toUpperCase() }

  // 3. Pola kode member eksplisit: MBR-... atau M001
  if (/^MBR-/i.test(teks))        return { type: 'member', kode: teks.toUpperCase() }
  if (/^M\d{3,}$/i.test(teks))    return { type: 'member', kode: teks.toUpperCase() }

  // 4. Teks multiline QR tiket ("Kode: A001")
  const mKode = teks.match(/kode(?:\s+tiket)?:\s*([A-Z0-9\-]+)/i)
  if (mKode) {
    const k = mKode[1].toUpperCase()
    if (k.startsWith('M') && /^M\d{3}/.test(k)) return { type: 'member', kode: k }
    if (k.startsWith('MBR')) return { type: 'member', kode: k }
    return { type: 'tiket', kode: k }
  }

  // 5. Multiline — cari baris yang cocok
  for (const b of teks.split(/\r?\n/)) {
    const c = b.trim()
    if (/^A\d{3,}$/i.test(c))  return { type: 'tiket',  kode: c.toUpperCase() }
    if (/^M\d{3,}$/i.test(c))  return { type: 'member', kode: c.toUpperCase() }
    if (/^MBR-/i.test(c))      return { type: 'member', kode: c.toUpperCase() }
  }

  // 6. Fallback: 1 baris saja → kirim apa adanya (tiket)
  if (!teks.includes('\n')) return { type: 'tiket', kode: teks.toUpperCase() }

  return null
}

// =========================================================
// SCAN — cari tiket atau member
// =========================================================

const scanKeluar = async () => {
  const raw = kodeInput.value.trim()
  if (!raw) return

  errorMsg.value        = ''
  tiketDitemukan.value  = null
  memberDitemukan.value = null
  hasilKeluar.value     = null
  hasilMember.value     = null
  modeScan.value        = null
  platInput.value       = ''
  bayarInput.value      = ''

  const parsed = parseInput(raw)

  if (!parsed) {
    errorMsg.value = '❓ Format QR tidak dikenali. Coba scan ulang atau ketik kode secara manual.'
    kodeInput.value = ''
    fokusInput()
    return
  }

  kodeInput.value = ''
  loading.value   = true
  modeScan.value  = parsed.type

  try {
    if (parsed.type === 'tiket') {
      await cariTiket(parsed.kode)
    } else {
      await cariMember(parsed.kode)
    }
  } finally {
    loading.value = false
    if (tiketDitemukan.value) {
      bayarInput.value = ''
      fokusPlat()
    } else {
      fokusInput()
    }
  }
}

// =========================================================
// CARI TIKET
// =========================================================

const cariTiket = async (kode: string) => {
  try {
    const res: any = await $fetch(`${apiBase}/tiket/kode/${kode}`, {
      headers: { Accept: 'application/json' },
    })
    const tiket = res?.data || res
    if (tiket.status === 'keluar') {
      errorMsg.value = `Tiket ${kode} sudah diproses keluar sebelumnya.`
      modeScan.value = null
    } else {
      tiketDitemukan.value = tiket
    }
  } catch (err: any) {
    const status = err?.status || err?.response?.status
    errorMsg.value = status === 404
      ? `Tiket "${kode}" tidak ditemukan. Pastikan kode tiket benar.`
      : (err?.data?.message || 'Gagal mencari tiket.')
    modeScan.value = null
  }
}

// =========================================================
// CARI MEMBER
// =========================================================

const cariMember = async (kode: string) => {
  try {
    const res: any = await $fetch(`${apiBase}/member/check`, {
      method: 'POST',
      body: { token: kode },
      headers: { Accept: 'application/json' },
    })
    const member = res?.data || res
    if (!member.sedang_parkir) {
      errorMsg.value = `Member ${member.kode_member} (${member.nama_member}) tidak sedang parkir.`
      modeScan.value = null
    } else {
      memberDitemukan.value = member
    }
  } catch (err: any) {
    const status = err?.status || err?.response?.status
    errorMsg.value = status === 404
      ? `Member "${kode}" tidak ditemukan.`
      : (err?.data?.message || 'Gagal mencari data member.')
    modeScan.value = null
  }
}

// Shortcut jumlah bayar
const setBayar = (nominal: number) => {
  bayarInput.value = nominal
}

const setUangPas = () => {
  bayarInput.value = totalBiayaOtomatis.value
}

// =========================================================
// PROSES KELUAR TIKET
// =========================================================

const prosesTiketKeluar = async () => {
  if (!tiketDitemukan.value) return

  const totalBiaya = totalBiayaOtomatis.value
  const uangBayar  = bayarInput.value !== '' ? Number(bayarInput.value) : totalBiaya

  if (uangBayar < totalBiaya) {
    errorMsg.value = `⚠️ Uang bayar kurang! Total biaya: ${formatRupiah(totalBiaya)}, diserahkan: ${formatRupiah(uangBayar)}.`
    return
  }

  loading.value  = true
  errorMsg.value = ''
  try {
    const body: any = {
      no_plat: platInput.value.trim().toUpperCase(),
      total_bayar: totalBiaya,
      jumlah_bayar: uangBayar,
    }
    const res: any = await $fetch(`${apiBase}/tiket/${tiketDitemukan.value.id}/keluar`, {
      method: 'PUT',
      body,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    })
    hasilKeluar.value = res?.data || res

    // Pastikan nilai jumlah_bayar dan kembalian tersimpan di hasilKeluar
    if (hasilKeluar.value) {
      hasilKeluar.value.jumlah_bayar = uangBayar
      hasilKeluar.value.kembalian = uangBayar - totalBiaya
    }

    tiketDitemukan.value = null
    // Otomatis print invoice
    cetakInvoiceTiket(hasilKeluar.value)
  } catch (err: any) {
    errorMsg.value = err?.data?.message || 'Gagal memproses keluar tiket.'
  } finally {
    loading.value = false
  }
}

// =========================================================
// PROSES KELUAR MEMBER
// =========================================================

const prosesMemberKeluar = async () => {
  if (!memberDitemukan.value) return
  loading.value  = true
  errorMsg.value = ''
  try {
    const plat = platInput.value.trim().toUpperCase()
    const waktuMasukAwal = memberDitemukan.value.waktu_masuk_parkir
    const res: any = await $fetch(`${apiBase}/member/keluar`, {
      method: 'POST',
      body: {
        kode_member: memberDitemukan.value.kode_member,
        no_plat: plat,
      },
      headers: { Accept: 'application/json' },
    })
    hasilMember.value = res?.data || res
    if (hasilMember.value) {
      hasilMember.value.no_plat = plat
      hasilMember.value.waktu_masuk = hasilMember.value.waktu_masuk || waktuMasukAwal
      hasilMember.value.waktu_keluar = hasilMember.value.waktu_keluar || new Date().toISOString()
    }
    memberDitemukan.value = null
    // Otomatis print invoice member
    cetakInvoiceMember(hasilMember.value)
  } catch (err: any) {
    errorMsg.value = err?.data?.message || 'Gagal memproses keluar member.'
  } finally {
    loading.value = false
  }
}

// =========================================================
// RESET
// =========================================================

const reset = () => {
  kodeInput.value       = ''
  platInput.value       = ''
  bayarInput.value      = ''
  tiketDitemukan.value  = null
  memberDitemukan.value = null
  hasilKeluar.value     = null
  hasilMember.value     = null
  errorMsg.value        = ''
  modeScan.value        = null
  fokusInput()
}

// =========================================================
// CETAK / PRINT INVOICE TIKET (otomatis)
// =========================================================

const cetakInvoiceTiket = (tiket: any) => {
  if (!tiket) return

  const jenis     = tiket.jenis_kendaraan === 'mobil' ? 'MOBIL' : 'MOTOR'
  const emoji     = tiket.jenis_kendaraan === 'mobil' ? '🚗' : '🏍️'
  const durasi    = hitungDurasi(tiket.waktu_masuk, tiket.waktu_keluar)
  const noPlat    = tiket.no_plat || platInput.value?.toUpperCase() || '-'
  const invoiceNo = `INV-${tiket.kode_tiket}-${new Date().getTime().toString().slice(-6)}`
  const bayar     = Number(tiket.jumlah_bayar || tiket.total_bayar || 0)
  const kembali   = Number(tiket.kembalian ?? (bayar - Number(tiket.total_bayar || 0)))

  const html = `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Invoice Parkir - ${tiket.kode_tiket}</title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:Arial,sans-serif; background:white; color:#111; }
.wrap { width:80mm; margin:0 auto; padding:6mm 7mm; }
/* header */
.hdr { text-align:center; border-bottom:2px solid #0f766e; padding-bottom:4mm; margin-bottom:4mm; }
.logo { width:14mm; height:14mm; margin:0 auto 2mm; border-radius:3mm; background:#0f766e; color:white; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:900; }
.nama { font-size:15px; font-weight:900; }
.sub  { font-size:9px; color:#0f766e; font-weight:700; letter-spacing:1px; }
/* invoice no */
.inv-no { font-size:8px; color:#64748b; margin-top:2mm; }
/* badge */
.badge { display:inline-block; margin:3mm 0; background:#fee2e2; color:#dc2626; border-radius:2mm; padding:1.5mm 5mm; font-size:10px; font-weight:900; letter-spacing:1px; }
/* plat */
.plat-box { margin:2mm 0 3mm; border:2px solid #0f766e; border-radius:3mm; padding:2mm 4mm; display:flex; align-items:center; justify-content:space-between; }
.plat-label { font-size:8px; color:#64748b; font-weight:700; text-transform:uppercase; }
.plat-val   { font-size:16px; font-weight:900; letter-spacing:3px; color:#0f766e; }
/* kendaraan */
.jenis-box { text-align:center; background:#f0fdfa; border:1px solid #99f6e4; border-radius:2mm; padding:2mm 0; font-size:10px; color:#0f766e; font-weight:700; margin-bottom:3mm; }
/* tabel detail */
.detail { width:100%; border-collapse:collapse; font-size:9.5px; }
.detail tr td { padding:1.8mm 1mm; }
.detail tr td:last-child { text-align:right; font-weight:700; }
.detail .sep td { border-top:1px dashed #cbd5e1; padding-top:2mm; }
/* total & bayar */
.total-row td { padding:2.5mm 2mm; font-size:12px; font-weight:900; color:#0f766e; border-top:2px solid #0f766e; background:#f0fdfa; }
.bayar-row td { padding:1.5mm 2mm; font-size:10px; font-weight:700; color:#334155; }
.kembalian-row td { padding:2mm 2mm; font-size:12px; font-weight:900; color:#15803d; border-top:1px dashed #cbd5e1; background:#f0fdf4; }
/* footer */
.footer { margin-top:5mm; border-top:1px dashed #cbd5e1; padding-top:4mm; text-align:center; font-size:8px; color:#94a3b8; line-height:1.6; }
.thanks { font-size:10px; font-weight:700; color:#0f766e; margin-bottom:1mm; }
@media print { @page{size:80mm auto;margin:0} body{width:80mm} }
</style></head>
<body><div class="wrap">
  <div class="hdr">
    <div class="logo">P</div>
    <div class="nama">PARKIR</div>
    <div class="sub">PLAZA ANDALAS</div>
    <div class="inv-no">No. Invoice: ${invoiceNo}</div>
  </div>

  <div style="text-align:center">
    <div class="badge">STRUK KELUAR</div>
  </div>

  <div class="plat-box">
    <div>
      <div class="plat-label">No. Plat Kendaraan</div>
      <div class="plat-val">${noPlat}</div>
    </div>
    <div style="font-size:22px">${emoji}</div>
  </div>

  <div class="jenis-box">${emoji} ${jenis} &nbsp;|&nbsp; Tiket: ${tiket.kode_tiket}</div>

  <table class="detail">
    <tr><td>Waktu Masuk</td><td>${formatWaktu(tiket.waktu_masuk)}</td></tr>
    <tr><td>Waktu Keluar</td><td>${formatWaktu(tiket.waktu_keluar)}</td></tr>
    <tr class="sep"><td>Durasi Parkir</td><td>${durasi} jam</td></tr>
    <tr><td>Tarif / Jam</td><td>${formatRupiah(tiket.tarif_per_jam)}</td></tr>
    <tr class="total-row">
      <td>TOTAL BIAYA</td>
      <td>${formatRupiah(tiket.total_bayar)}</td>
    </tr>
    <tr class="bayar-row">
      <td>Uang Diterima</td>
      <td>${formatRupiah(bayar)}</td>
    </tr>
    <tr class="kembalian-row">
      <td>KEMBALIAN</td>
      <td>${formatRupiah(kembali)}</td>
    </tr>
  </table>

  <div class="footer">
    <div class="thanks">Terima kasih telah parkir di Plaza Andalas!</div>
    Simpan struk ini sebagai bukti pembayaran sah.<br>
    Dicetak: ${new Date().toLocaleString('id-ID')}
  </div>
</div>
<script>window.onload=function(){setTimeout(function(){window.print()},400)}<\/script>
</body></html>`

  const blob = new Blob([html], { type: 'text/html;charset=utf-8' })
  const url  = URL.createObjectURL(blob)
  window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 10000)
}

// Download ulang jika perlu
const downloadInvoice = () => cetakInvoiceTiket(hasilKeluar.value)

// =========================================================
// CETAK / PRINT INVOICE MEMBER (otomatis)
// =========================================================

const cetakInvoiceMember = (member: any) => {
  if (!member) return

  const durasi    = member.waktu_masuk ? hitungDurasi(member.waktu_masuk, member.waktu_keluar || new Date().toISOString()) : 1
  const noPlat    = member.no_plat || platInput.value?.toUpperCase() || '-'
  const invoiceNo = `INV-MBR-${member.kode_member}-${new Date().getTime().toString().slice(-6)}`

  const html = `<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><title>Invoice Member - ${member.kode_member}</title>
<style>
* { box-sizing:border-box; margin:0; padding:0; }
body { font-family:Arial,sans-serif; background:white; color:#111; }
.wrap { width:80mm; margin:0 auto; padding:6mm 7mm; }
/* header */
.hdr { text-align:center; border-bottom:2px solid #7e22ce; padding-bottom:4mm; margin-bottom:4mm; }
.logo { width:14mm; height:14mm; margin:0 auto 2mm; border-radius:3mm; background:#7e22ce; color:white; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:900; }
.nama { font-size:15px; font-weight:900; }
.sub  { font-size:9px; color:#7e22ce; font-weight:700; letter-spacing:1px; }
/* invoice no */
.inv-no { font-size:8px; color:#64748b; margin-top:2mm; }
/* badge */
.badge { display:inline-block; margin:3mm 0; background:#f3e8ff; color:#7e22ce; border:1px solid #d8b4fe; border-radius:2mm; padding:1.5mm 5mm; font-size:10px; font-weight:900; letter-spacing:1px; }
/* plat */
.plat-box { margin:2mm 0 3mm; border:2px solid #7e22ce; border-radius:3mm; padding:2mm 4mm; display:flex; align-items:center; justify-content:space-between; }
.plat-label { font-size:8px; color:#64748b; font-weight:700; text-transform:uppercase; }
.plat-val   { font-size:16px; font-weight:900; letter-spacing:3px; color:#7e22ce; }
/* member info */
.member-box { text-align:center; background:#faf5ff; border:1px solid #e9d5ff; border-radius:2mm; padding:2mm 0; font-size:10px; color:#6b21a8; font-weight:700; margin-bottom:3mm; }
/* tabel detail */
.detail { width:100%; border-collapse:collapse; font-size:9.5px; }
.detail tr td { padding:1.8mm 1mm; }
.detail tr td:last-child { text-align:right; font-weight:700; }
.detail .sep td { border-top:1px dashed #cbd5e1; padding-top:2mm; }
/* total */
.total-row td { padding:2.5mm 2mm; font-size:12px; font-weight:900; color:#7e22ce; border-top:2px solid #7e22ce; background:#faf5ff; }
.gratis-badge { display:inline-block; background:#dcfce7; color:#15803d; border-radius:2mm; padding:1mm 3mm; font-size:9px; font-weight:bold; }
/* footer */
.footer { margin-top:5mm; border-top:1px dashed #cbd5e1; padding-top:4mm; text-align:center; font-size:8px; color:#94a3b8; line-height:1.6; }
.thanks { font-size:10px; font-weight:700; color:#7e22ce; margin-bottom:1mm; }
@media print { @page{size:80mm auto;margin:0} body{width:80mm} }
</style></head>
<body><div class="wrap">
  <div class="hdr">
    <div class="logo">P</div>
    <div class="nama">PARKIR</div>
    <div class="sub">PLAZA ANDALAS</div>
    <div class="inv-no">No. Invoice: ${invoiceNo}</div>
  </div>

  <div style="text-align:center">
    <div class="badge">STRUK KELUAR MEMBER</div>
  </div>

  <div class="plat-box">
    <div>
      <div class="plat-label">No. Plat Kendaraan</div>
      <div class="plat-val">${noPlat}</div>
    </div>
    <div style="font-size:22px">👤</div>
  </div>

  <div class="member-box">👤 Member: ${member.nama_member} (${member.kode_member})</div>

  <table class="detail">
    <tr><td>Perusahaan</td><td>${member.nama_perusahaan || '-'}</td></tr>
    <tr><td>Waktu Masuk</td><td>${formatWaktu(member.waktu_masuk)}</td></tr>
    <tr><td>Waktu Keluar</td><td>${formatWaktu(member.waktu_keluar)}</td></tr>
    <tr class="sep"><td>Durasi Parkir</td><td>${durasi} jam</td></tr>
    <tr><td>Status Langganan</td><td><span class="gratis-badge">MEMBER AKTIF</span></td></tr>
    <tr class="total-row">
      <td>TOTAL BIAYA</td>
      <td>Rp 0 (GRATIS)</td>
    </tr>
  </table>

  <div class="footer">
    <div class="thanks">Terima kasih telah menjadi Member Plaza Andalas!</div>
    Bebas biaya parkir dengan kartu member bulanan sah.<br>
    Dicetak: ${new Date().toLocaleString('id-ID')}
  </div>
</div>
<script>window.onload=function(){setTimeout(function(){window.print()},400)}<\/script>
</body></html>`

  const blob = new Blob([html], { type: 'text/html;charset=utf-8' })
  const url  = URL.createObjectURL(blob)
  window.open(url, '_blank')
  setTimeout(() => URL.revokeObjectURL(url), 10000)
}

const downloadInvoiceMember = () => cetakInvoiceMember(hasilMember.value)
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 flex items-start justify-center px-4 py-10">

    <!-- Dekor background -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden">
      <div class="absolute w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl -top-20 -right-20"></div>
      <div class="absolute w-72 h-72 bg-teal-500/10 rounded-full blur-3xl bottom-0 left-10"></div>
    </div>

    <div class="relative z-10 w-full max-w-lg">

      <!-- HEADER -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <div class="text-cyan-400 text-xs font-bold uppercase tracking-widest mb-1">🅿️ Plaza Andalas</div>
          <h1 class="text-3xl font-black text-white">Scan Keluar</h1>
          <p class="text-sm text-slate-400 mt-1">Untuk kendaraan biasa &amp; member</p>
        </div>
        <button
          type="button"
          @click="navigateTo('/')"
          class="px-4 py-2 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 text-white text-sm font-bold transition cursor-pointer"
        >
          ← Dashboard
        </button>
      </div>

      <!-- CARD -->
      <div class="bg-white/5 border border-white/10 rounded-3xl shadow-2xl p-8 backdrop-blur-sm text-white">

        <!-- ===== FORM SCAN ===== -->
        <div v-if="!hasilKeluar && !hasilMember">

          <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">
            📷 Scan / Kode
          </label>

          <form @submit.prevent="scanKeluar" class="flex gap-3 mb-4">
            <input
              ref="inputRef"
              v-model="kodeInput"
              type="text"
              placeholder="Scan QR atau ketik kode tiket / member..."
              :disabled="loading || !!tiketDitemukan || !!memberDitemukan"
              class="flex-1 h-12 rounded-2xl bg-white/10 border border-white/20 text-white font-bold px-4 outline-none
                     focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/30 placeholder:text-slate-500
                     uppercase disabled:opacity-40 transition text-sm"
            />
            <button
              type="submit"
              :disabled="loading || !kodeInput.trim() || !!tiketDitemukan || !!memberDitemukan"
              class="h-12 px-5 rounded-2xl bg-cyan-600 hover:bg-cyan-500 text-white font-black text-sm
                     disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer whitespace-nowrap"
            >
              {{ loading ? '⏳' : '🔍 Cari' }}
            </button>
          </form>

          <p class="text-xs text-slate-500 text-center mb-5">
            Otomatis deteksi tiket kendaraan atau kartu member
          </p>

          <!-- ERROR -->
          <div v-if="errorMsg" class="rounded-2xl bg-red-500/15 border border-red-500/30 p-4 text-sm text-red-300 font-semibold leading-relaxed mb-4">
            {{ errorMsg }}
          </div>

          <!-- ===== TIKET DITEMUKAN ===== -->
          <div v-if="tiketDitemukan" class="mt-4 rounded-2xl bg-blue-500/10 border border-blue-500/30 p-5 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">🎫 Tiket Kendaraan</span>
              <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 uppercase">Aktif</span>
            </div>

            <!-- Detail Info Tiket -->
            <div class="grid grid-cols-2 gap-4 text-sm bg-black/20 p-3.5 rounded-xl border border-white/5">
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Kode Tiket</p>
                <p class="font-black text-white text-xl tracking-wider">{{ tiketDitemukan.kode_tiket }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Kendaraan</p>
                <p class="font-bold text-white text-base">
                  {{ tiketDitemukan.jenis_kendaraan === 'mobil' ? '🚗 Mobil' : '🏍️ Motor' }}
                </p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Tarif / Jam</p>
                <p class="font-bold text-cyan-400 text-sm">{{ formatRupiah(tiketDitemukan.tarif_per_jam) }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Durasi (Estimasi)</p>
                <p class="font-bold text-white text-sm">{{ durasiSaatIni }} Jam</p>
              </div>
            </div>

            <!-- 1. INPUT PLAT NOMOR -->
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                🚘 No. Plat Kendaraan
              </label>
              <input
                ref="platRef"
                v-model="platInput"
                type="text"
                placeholder="Contoh: B 1234 ABC"
                :disabled="loading"
                class="w-full h-11 rounded-xl bg-white/10 border border-white/20 text-white font-black px-4 outline-none
                       focus:border-cyan-400 focus:ring-2 focus:ring-cyan-400/30 placeholder:text-slate-500
                       uppercase disabled:opacity-40 transition text-base tracking-widest"
                @keydown.enter.prevent="() => bayarRef?.focus()"
              />
            </div>

            <!-- BIAYA PARKIR (OTOMATIS DARI SISTEM) -->
            <div class="bg-gradient-to-r from-cyan-950/70 to-slate-900 border border-cyan-500/30 rounded-2xl p-4 flex items-center justify-between shadow-inner">
              <div>
                <span class="text-xs uppercase font-extrabold text-cyan-400 tracking-wider">💵 Total Biaya Parkir</span>
                <p class="text-xs text-slate-400 mt-0.5">{{ durasiSaatIni }} Jam × {{ formatRupiah(tiketDitemukan.tarif_per_jam) }} (Dihitung Otomatis)</p>
              </div>
              <div class="text-2xl font-black text-cyan-300 tracking-tight">
                {{ formatRupiah(totalBiayaOtomatis) }}
              </div>
            </div>

            <!-- INPUT UANG BAYAR (DITERIMA DARI PENGENDARA) -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-bold text-slate-300 uppercase tracking-widest">
                  💰 Uang Bayar / Diterima
                </label>
                <span class="text-xs text-slate-400">Kosongkan jika uang pas</span>
              </div>
              <div class="relative">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-400 font-bold text-base">Rp</span>
                <input
                  ref="bayarRef"
                  v-model.number="bayarInput"
                  type="number"
                  :placeholder="`Ketik uang diterima (cth: ${totalBiayaOtomatis})`"
                  :disabled="loading"
                  class="w-full h-12 pl-12 pr-4 rounded-xl bg-white/10 border-2 border-white/20 text-white font-black text-xl outline-none
                         focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/40 placeholder:text-slate-500 transition"
                  @keydown.enter.prevent="prosesTiketKeluar"
                />
              </div>

              <!-- Shortcut Uang Cepat -->
              <div class="flex flex-wrap gap-1.5 mt-2">
                <button
                  type="button"
                  @click="setUangPas"
                  class="px-3 py-1 rounded-lg bg-emerald-600/30 hover:bg-emerald-600/50 border border-emerald-500/40 text-emerald-300 text-xs font-bold transition cursor-pointer"
                >
                  Uang Pas ({{ formatRupiah(totalBiayaOtomatis) }})
                </button>
                <button
                  v-for="nom in [5000, 10000, 20000, 50000, 100000]"
                  :key="nom"
                  type="button"
                  @click="setBayar(nom)"
                  class="px-2.5 py-1 rounded-lg bg-white/5 hover:bg-white/15 border border-white/10 text-slate-300 text-xs font-medium transition cursor-pointer"
                >
                  {{ formatRupiah(nom) }}
                </button>
              </div>
            </div>

            <!-- TAMPILAN KEMBALIAN OTOMATIS -->
            <div class="rounded-2xl p-4 border transition-all"
              :class="[
                kembalian > 0
                  ? 'bg-emerald-500/20 border-emerald-500/50 shadow-lg shadow-emerald-950/40'
                  : kembalian === 0
                  ? 'bg-blue-500/20 border-blue-500/40'
                  : 'bg-red-500/20 border-red-500/50'
              ]"
            >
              <div class="flex items-center justify-between">
                <div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-sm">
                      {{ kembalian > 0 ? '💸' : kembalian === 0 ? '✅' : '⚠️' }}
                    </span>
                    <p class="text-xs uppercase tracking-wider font-extrabold"
                      :class="kembalian >= 0 ? 'text-emerald-300' : 'text-red-400'"
                    >
                      {{ kembalian > 0 ? 'KEMBALIAN OTOMATIS' : kembalian === 0 ? 'UANG PAS' : 'UANG KURANG' }}
                    </p>
                  </div>
                  <p class="text-xs text-slate-400 mt-1">
                    {{ kembalian > 0 ? 'Kembalikan uang ini ke pengendara' : kembalian === 0 ? 'Uang pas, tidak ada kembalian' : 'Jumlah uang belum mencukupi tagihan' }}
                  </p>
                </div>
                <div class="text-right">
                  <div class="text-3xl font-black tracking-tight"
                    :class="kembalian >= 0 ? 'text-emerald-300' : 'text-red-300'"
                  >
                    {{ formatRupiah(Math.abs(kembalian)) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex gap-3 pt-2">
              <button
                type="button"
                @click="prosesTiketKeluar"
                :disabled="loading || (bayarInput !== '' && kembalian < 0)"
                class="flex-1 h-12 rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-black text-sm
                       disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-red-900/30 cursor-pointer"
              >
                {{ loading ? '⏳ Memproses...' : '🚗 Selesai & Cetak Invoice' }}
              </button>
              <button type="button" @click="reset" :disabled="loading"
                class="px-5 h-12 rounded-2xl bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold text-sm transition cursor-pointer">
                Batal
              </button>
            </div>
          </div>

          <!-- ===== MEMBER DITEMUKAN ===== -->
          <div v-if="memberDitemukan" class="mt-4 rounded-2xl bg-purple-500/10 border border-purple-500/30 p-5 space-y-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold uppercase tracking-wider text-slate-400">👤 Kartu Member</span>
              <span class="px-3 py-1 rounded-full text-xs font-black bg-purple-500/20 text-purple-300 border border-purple-500/30 uppercase">Sedang Parkir</span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm bg-black/20 p-3.5 rounded-xl border border-white/5">
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Kode Member</p>
                <p class="font-black text-white text-xl tracking-wider">{{ memberDitemukan.kode_member }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Nama</p>
                <p class="font-bold text-white text-base">{{ memberDitemukan.nama_member }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Perusahaan</p>
                <p class="font-bold text-slate-300 text-sm">{{ memberDitemukan.nama_perusahaan }}</p>
              </div>
              <div>
                <p class="text-slate-400 text-xs mb-0.5">Waktu Masuk</p>
                <p class="font-bold text-white text-xs">{{ formatWaktu(memberDitemukan.waktu_masuk_parkir) }}</p>
              </div>
            </div>

            <!-- INPUT NO PLAT MEMBER -->
            <div>
              <label class="block text-xs font-bold text-purple-300 uppercase tracking-widest mb-1.5">
                🚘 No. Plat Kendaraan
              </label>
              <input
                ref="platRef"
                v-model="platInput"
                type="text"
                placeholder="Contoh: B 1234 ABC"
                :disabled="loading"
                class="w-full h-11 rounded-xl bg-white/10 border border-purple-500/30 text-white font-black px-4 outline-none
                       focus:border-purple-400 focus:ring-2 focus:ring-purple-400/30 placeholder:text-slate-500
                       uppercase disabled:opacity-40 transition text-base tracking-widest"
                @keydown.enter.prevent="prosesMemberKeluar"
              />
              <p class="text-xs text-slate-400 mt-1">Masukkan plat untuk dicetak di invoice member</p>
            </div>

            <!-- BIAYA MEMBER (GRATIS) -->
            <div class="bg-gradient-to-r from-purple-950/70 to-slate-900 border border-purple-500/30 rounded-2xl p-4 flex items-center justify-between shadow-inner">
              <div>
                <span class="text-xs uppercase font-extrabold text-purple-400 tracking-wider">💳 Biaya Parkir Member</span>
                <p class="text-xs text-slate-400 mt-0.5">Langganan Bulanan Aktif (Bebas Biaya Parkir)</p>
              </div>
              <div class="text-2xl font-black text-emerald-400 tracking-tight">
                Rp 0 (GRATIS)
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <button
                type="button"
                @click="prosesMemberKeluar"
                :disabled="loading"
                class="flex-1 h-12 rounded-2xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white font-black text-sm
                       disabled:opacity-50 disabled:cursor-not-allowed transition cursor-pointer shadow-lg shadow-purple-900/30"
              >
                {{ loading ? '⏳ Memproses...' : '👤 Selesai & Cetak Invoice Member' }}
              </button>
              <button type="button" @click="reset" :disabled="loading"
                class="px-5 h-12 rounded-2xl bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold text-sm transition cursor-pointer">
                Batal
              </button>
            </div>
          </div>

        </div>

        <!-- ===== HASIL KELUAR TIKET ===== -->
        <div v-if="hasilKeluar">
          <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 text-4xl mb-3">✅</div>
            <h2 class="text-2xl font-black text-white">Keluar Berhasil!</h2>
            <p class="text-sm text-slate-400 mt-1">Invoice sedang dicetak…</p>
          </div>

          <!-- Plat + Kode -->
          <div class="flex items-center justify-between mb-5 bg-black/20 p-3.5 rounded-2xl border border-white/5">
            <div class="text-center flex-1">
              <p class="text-xs text-slate-400 mb-0.5">No. Plat</p>
              <div class="font-black text-cyan-400 text-xl tracking-widest">
                {{ hasilKeluar.no_plat || '-' }}
              </div>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center flex-1">
              <p class="text-xs text-slate-400 mb-0.5">Kode Tiket</p>
              <div class="font-black text-white text-xl tracking-widest">{{ hasilKeluar.kode_tiket }}</div>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center flex-1">
              <p class="text-xs text-slate-400 mb-0.5">Kendaraan</p>
              <div class="font-bold text-slate-300 text-sm">
                {{ hasilKeluar.jenis_kendaraan === 'mobil' ? '🚗 Mobil' : '🏍️ Motor' }}
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-white/5 border border-white/10 divide-y divide-white/10 text-sm mb-5 overflow-hidden">
            <div class="flex justify-between px-5 py-2.5">
              <span class="text-slate-400">Waktu Masuk</span>
              <span class="font-bold">{{ formatWaktu(hasilKeluar.waktu_masuk) }}</span>
            </div>
            <div class="flex justify-between px-5 py-2.5">
              <span class="text-slate-400">Waktu Keluar</span>
              <span class="font-bold">{{ formatWaktu(hasilKeluar.waktu_keluar) }}</span>
            </div>
            <div class="flex justify-between px-5 py-2.5">
              <span class="text-slate-400">Durasi</span>
              <span class="font-bold">{{ hitungDurasi(hasilKeluar.waktu_masuk, hasilKeluar.waktu_keluar) }} jam</span>
            </div>
            <div class="flex justify-between px-5 py-2.5">
              <span class="text-slate-400">Tarif / Jam</span>
              <span class="font-bold text-cyan-400">{{ formatRupiah(hasilKeluar.tarif_per_jam) }}</span>
            </div>
            <div class="flex justify-between px-5 py-3.5 bg-emerald-500/10">
              <span class="font-black text-base">TOTAL BIAYA</span>
              <span class="font-black text-emerald-400 text-xl">{{ formatRupiah(hasilKeluar.total_bayar) }}</span>
            </div>
            <div class="flex justify-between px-5 py-2.5 bg-white/5">
              <span class="text-slate-300 font-semibold">Uang Diterima</span>
              <span class="font-bold text-white">{{ formatRupiah(hasilKeluar.jumlah_bayar || hasilKeluar.total_bayar) }}</span>
            </div>
            <div class="flex justify-between px-5 py-3 bg-emerald-950/40 text-emerald-300">
              <span class="font-black text-sm">KEMBALIAN</span>
              <span class="font-black text-lg">{{ formatRupiah(hasilKeluar.kembalian ?? (Number(hasilKeluar.jumlah_bayar || 0) - Number(hasilKeluar.total_bayar || 0))) }}</span>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="button" @click="downloadInvoice"
              class="flex-1 h-12 rounded-2xl bg-slate-700 hover:bg-slate-600 text-white font-bold text-sm transition cursor-pointer">
              🖨️ Cetak Ulang Invoice
            </button>
            <button type="button" @click="reset"
              class="flex-1 h-12 rounded-2xl bg-cyan-600 hover:bg-cyan-500 text-white font-black text-sm transition cursor-pointer">
              ✅ Scan Baru
            </button>
          </div>
        </div>

        <!-- ===== HASIL KELUAR MEMBER ===== -->
        <div v-if="hasilMember">
          <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-purple-500/20 border border-purple-500/40 text-4xl mb-3">✅</div>
            <h2 class="text-2xl font-black text-white">Member Keluar Berhasil!</h2>
            <p class="text-sm text-slate-400 mt-1">Invoice member sedang dicetak…</p>
          </div>

          <!-- Plat + Kode Member -->
          <div class="flex items-center justify-between mb-5 bg-black/20 p-3.5 rounded-2xl border border-white/5">
            <div class="text-center flex-1">
              <p class="text-xs text-slate-400 mb-0.5">No. Plat</p>
              <div class="font-black text-purple-300 text-xl tracking-widest">
                {{ hasilMember.no_plat || '-' }}
              </div>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center flex-1">
              <p class="text-xs text-slate-400 mb-0.5">Kode Member</p>
              <div class="font-black text-white text-xl tracking-widest">{{ hasilMember.kode_member }}</div>
            </div>
            <div class="w-px h-10 bg-white/10"></div>
            <div class="text-center flex-1">
              <p class="text-xs text-slate-400 mb-0.5">Nama</p>
              <div class="font-bold text-slate-300 text-sm truncate max-w-[120px]">
                {{ hasilMember.nama_member }}
              </div>
            </div>
          </div>

          <div class="rounded-2xl bg-white/5 border border-white/10 divide-y divide-white/10 text-sm mb-5 overflow-hidden">
            <div class="flex justify-between px-5 py-3">
              <span class="text-slate-400">Perusahaan</span>
              <span class="font-bold text-white">{{ hasilMember.nama_perusahaan }}</span>
            </div>
            <div class="flex justify-between px-5 py-3">
              <span class="text-slate-400">Waktu Masuk</span>
              <span class="font-bold">{{ formatWaktu(hasilMember.waktu_masuk) }}</span>
            </div>
            <div class="flex justify-between px-5 py-3">
              <span class="text-slate-400">Waktu Keluar</span>
              <span class="font-bold">{{ formatWaktu(hasilMember.waktu_keluar) }}</span>
            </div>
            <div class="flex justify-between px-5 py-3.5 bg-purple-500/10">
              <span class="font-black text-base text-purple-300">TOTAL BIAYA</span>
              <span class="font-black text-emerald-400 text-xl">Rp 0 (GRATIS)</span>
            </div>
            <div class="flex justify-between px-5 py-3 bg-purple-950/40 text-purple-200">
              <span class="font-semibold text-xs">Status Langganan</span>
              <span class="font-black text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">MEMBER AKTIF</span>
            </div>
          </div>

          <div class="flex gap-3">
            <button type="button" @click="downloadInvoiceMember"
              class="flex-1 h-12 rounded-2xl bg-purple-900/60 hover:bg-purple-800/80 border border-purple-500/40 text-purple-200 font-bold text-sm transition cursor-pointer">
              🖨️ Cetak Ulang Invoice
            </button>
            <button type="button" @click="reset"
              class="flex-1 h-12 rounded-2xl bg-cyan-600 hover:bg-cyan-500 text-white font-black text-sm transition cursor-pointer">
              ✅ Scan Baru
            </button>
          </div>
        </div>

      </div>

      <!-- Footer hint -->
      <p class="text-center text-xs text-slate-600 mt-4">
        Sistem otomatis menghitung durasi, biaya, uang diterima &amp; kembalian parkir
      </p>

    </div>
  </div>
</template>
