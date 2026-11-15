<template>
  <div class="min-h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-slate-900 via-teal-900 to-cyan-950"></div>

    <!-- Efek cahaya -->
    <div class="absolute w-96 h-96 bg-cyan-400/20 rounded-full blur-3xl -top-20 -left-20"></div>
    <div class="absolute w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl -bottom-20 -right-20"></div>

    <!-- Card -->
    <div
      class="
        relative z-10
        w-[420px]
        min-h-[720px]
        bg-white/90
        backdrop-blur-xl
        rounded-[32px]
        shadow-2xl
        flex
        flex-col
        items-center
        pt-10
        px-8
        pb-8
      "
    >

      <!-- Logo -->
      <div
        class="
          w-16
          h-16
          rounded-2xl
          bg-gradient-to-br
          from-cyan-500
          to-teal-600
          flex
          items-center
          justify-center
          shadow-lg
          mb-5
        "
      >
        <span class="text-white text-2xl font-black">
          P
        </span>
      </div>

      <!-- Judul -->
      <h1
        class="
          text-slate-800
          text-3xl
          font-black
          text-center
          tracking-tight
        "
      >
        PARKIR
      </h1>

      <h2
        class="
          text-teal-700
          text-xl
          font-bold
          text-center
          mt-1
        "
      >
        PLAZA ANDALAS
      </h2>

      <!-- Garis -->
      <div class="w-20 h-1 bg-cyan-500 rounded-full mt-5"></div>

      <!-- Informasi tiket -->
      <div class="mt-6 text-center">
        <p class="text-slate-700 text-sm font-semibold">
          Ambil tiket parkir kendaraan
        </p>

        <p class="text-slate-400 text-xs mt-1">
          Pilih jenis kendaraan terlebih dahulu
        </p>
      </div>

      <!-- ================================= -->
      <!-- PILIH JENIS KENDARAAN -->
      <!-- ================================= -->
      <div class="w-full mt-5">

        <p class="text-slate-700 text-sm font-bold text-center mb-3">
          Jenis Kendaraan
        </p>

        <div class="grid grid-cols-2 gap-3">

          <!-- MOBIL -->
          <button
            type="button"
            @click="pilihKendaraan('mobil')"
            :class="[
              'h-28 rounded-2xl border-2 transition-all duration-200 flex flex-col items-center justify-center',
              jenisKendaraan === 'mobil'
                ? 'border-cyan-500 bg-cyan-50 shadow-lg scale-[1.02]'
                : 'border-slate-200 bg-slate-50 hover:border-cyan-300 hover:bg-cyan-50/50'
            ]"
          >
            <span class="text-3xl">
              🚗
            </span>

            <span
              :class="[
                'text-sm font-bold mt-1',
                jenisKendaraan === 'mobil'
                  ? 'text-cyan-700'
                  : 'text-slate-700'
              ]"
            >
              MOBIL
            </span>

            <span
              :class="[
                'text-xs mt-1',
                jenisKendaraan === 'mobil'
                  ? 'text-cyan-600'
                  : 'text-slate-400'
              ]"
            >
              Rp5.000 / jam
            </span>
          </button>

          <!-- MOTOR -->
          <button
            type="button"
            @click="pilihKendaraan('motor')"
            :class="[
              'h-28 rounded-2xl border-2 transition-all duration-200 flex flex-col items-center justify-center',
              jenisKendaraan === 'motor'
                ? 'border-teal-500 bg-teal-50 shadow-lg scale-[1.02]'
                : 'border-slate-200 bg-slate-50 hover:border-teal-300 hover:bg-teal-50/50'
            ]"
          >
            <span class="text-3xl">
              🏍️
            </span>

            <span
              :class="[
                'text-sm font-bold mt-1',
                jenisKendaraan === 'motor'
                  ? 'text-teal-700'
                  : 'text-slate-700'
              ]"
            >
              MOTOR
            </span>

            <span
              :class="[
                'text-xs mt-1',
                jenisKendaraan === 'motor'
                  ? 'text-teal-600'
                  : 'text-slate-400'
              ]"
            >
              Rp3.000 / jam
            </span>
          </button>

        </div>
      </div>

      <!-- Info pilihan -->
      <div
        v-if="jenisKendaraan"
        class="
          mt-4
          w-full
          rounded-xl
          bg-slate-100
          border
          border-slate-200
          px-4
          py-3
          text-center
        "
      >
        <p class="text-xs text-slate-400">
          Kendaraan dipilih
        </p>

        <p class="text-sm font-bold text-slate-700 mt-1">
          {{ jenisKendaraan === "mobil" ? "🚗 Mobil" : "🏍️ Motor" }}
        </p>

        <p class="text-xs font-semibold text-teal-600 mt-1">
          {{ formatRupiah(tarifKendaraan) }} / jam
        </p>
      </div>

      <!-- Tombol Print -->
      <button
        @click="printTicket"
        :disabled="loading || !jenisKendaraan"
        class="
          mt-5
          w-44
          h-12
          rounded-2xl
          bg-gradient-to-r
          from-cyan-600
          to-teal-600
          text-white
          font-bold
          shadow-lg
          hover:from-cyan-500
          hover:to-teal-500
          active:scale-95
          transition
          disabled:opacity-50
          disabled:cursor-not-allowed
        "
      >
        {{
          loading
            ? "MEMPROSES..."
            : jenisKendaraan
              ? "PRINT TIKET"
              : "PILIH KENDARAAN"
        }}
      </button>

      <!-- ================================= -->
      <!-- MEMBER -->
      <!-- ================================= -->

      <div class="flex items-center w-full mt-7 gap-4">
        <div class="flex-1 h-px bg-slate-300"></div>

        <span class="text-xs text-slate-400 font-semibold">
          MEMBER
        </span>

        <div class="flex-1 h-px bg-slate-300"></div>
      </div>

      <!-- Scan Member -->
      <div class="mt-5 text-center">
        <p class="text-slate-700 text-sm font-bold">
          Scan kartu member
        </p>

        <p class="text-slate-400 text-xs mt-1">
          Masukkan nomor kartu untuk membuka pintu
        </p>
      </div>

      <!-- Input -->
      <input
        v-model="scanCard"
        ref="scanInput"
        autofocus
        type="text"
        placeholder="Scan kartu..."
        :disabled="loading"
        class="
          mt-4
          w-full
          h-14
          rounded-2xl
          border
          border-slate-200
          bg-slate-50
          text-slate-800
          text-center
          text-lg
          font-semibold
          outline-none
          focus:ring-2
          focus:ring-cyan-500
          focus:border-cyan-500
          shadow-sm
          disabled:opacity-60
          disabled:cursor-not-allowed
        "
        @keyup.enter="openGate"
      />

      <!-- Status -->
      <div
        class="
          mt-4
          flex
          items-center
          gap-2
          text-xs
          text-slate-400
        "
      >
        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>

        {{
          loading
            ? "Sedang memproses..."
            : "Sistem parkir siap digunakan"
        }}
      </div>

    </div>
  </div>
</template>


<script setup lang="ts">

import { ref } from "vue";
import QRCode from "qrcode";

// =========================================================
// API
// =========================================================

const config = useRuntimeConfig();
const apiBase = String(config.public.apiBase || "http://127.0.0.1:8000/api").replace(/\/+$/, "");


// =========================================================
// STATE
// =========================================================

const scanCard = ref("");
const tiket = ref<any>(null);
const loading = ref(false);

// Jenis kendaraan
const jenisKendaraan = ref<"mobil" | "motor" | "">("");

// Tarif kendaraan
const tarifKendaraan = ref(0);


// =========================================================
// PILIH KENDARAAN
// =========================================================

const pilihKendaraan = (jenis: "mobil" | "motor") => {

  jenisKendaraan.value = jenis;

  if (jenis === "mobil") {

    tarifKendaraan.value = 5000;

  } else {

    tarifKendaraan.value = 3000;

  }

};


// =========================================================
// FORMAT RUPIAH
// =========================================================

const formatRupiah = (angka: number) => {

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(angka);

};


// =========================================================
// BUAT TIKET
// =========================================================

const buatTiket = async () => {

  try {

    const response: any = await $fetch(`${apiBase}/tiket`, {
      method: "POST",
      body: {
        jenis_kendaraan: jenisKendaraan.value,
        tarif_per_jam: tarifKendaraan.value,
      },
      headers: { Accept: "application/json" },
    });

    tiket.value = response?.data || response;

    return tiket.value;

  } catch (error) {

    console.error("Gagal membuat tiket:", error);

    alert("Gagal membuat tiket. Pastikan server Laravel berjalan.");

    return null;

  }

};


// =========================================================
// PRINT TIKET
// =========================================================

const printTicket = async () => {

  // Pastikan kendaraan sudah dipilih
  if (!jenisKendaraan.value) {
    alert("Silakan pilih jenis kendaraan terlebih dahulu.");
    return;
  }

  loading.value = true;

  try {

    // Buat tiket dari backend
    const data = await buatTiket();

    if (!data) return;

    const nomorTiket = data.kode_tiket;

    const namaKendaraan =
      jenisKendaraan.value === "mobil" ? "Mobil" : "Motor";

    const tarif = tarifKendaraan.value;

    // =====================================================
    // QR CODE
    // =====================================================

    // QR hanya berisi kode tiket agar tidak ter-split saat scan hardware
    const qrData = nomorTiket;

    const qrImage = await QRCode.toDataURL(qrData, { width: 300, margin: 2 });

    // =====================================================
    // HTML TIKET
    // =====================================================

    const html = `<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Tiket Parkir - ${nomorTiket}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; background: white; }
    .ticket { width: 80mm; min-height: 140mm; margin: 0 auto; padding: 8mm; text-align: center; color: #111827; }
    .logo { width: 18mm; height: 18mm; margin: 0 auto 4mm; border-radius: 5mm; background: #0f766e; color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: bold; }
    .title { font-size: 17px; font-weight: 800; margin-top: 2mm; }
    .subtitle { font-size: 12px; color: #0f766e; font-weight: bold; margin-top: 1mm; }
    .line { width: 35mm; height: 1px; background: #0f766e; margin: 5mm auto; }
    .label { font-size: 9px; color: #64748b; margin-bottom: 2mm; }
    .qr { width: 42mm; height: 42mm; margin: 4mm auto; display: block; }
    .kode { font-size: 18px; font-weight: 800; letter-spacing: 1px; margin-top: 3mm; }
    .jenis { margin-top: 3mm; padding: 3mm; border-radius: 3mm; background: #f0fdfa; border: 1px solid #99f6e4; font-size: 10px; color: #0f766e; font-weight: bold; }
    .tarif { margin-top: 2mm; font-size: 12px; font-weight: bold; color: #0f766e; }
    .info { margin-top: 5mm; padding-top: 4mm; border-top: 1px dashed #94a3b8; text-align: left; font-size: 9px; line-height: 1.7; }
    .thanks { margin-top: 7mm; font-size: 9px; color: #64748b; }
    @media print {
      @page { size: 80mm 150mm; margin: 0; }
      body { width: 80mm; margin: 0; }
      .ticket { margin: 0; }
    }
  </style>
</head>
<body>
  <div class="ticket">
    <div class="logo">P</div>
    <div class="title">PARKIR</div>
    <div class="subtitle">PLAZA ANDALAS</div>
    <div class="line"></div>
    <div class="label">TIKET PARKIR KENDARAAN</div>
    <img class="qr" src="${qrImage}" />
    <div class="kode">${nomorTiket}</div>
    <div class="jenis">${namaKendaraan.toUpperCase()}</div>
    <div class="tarif">${formatRupiah(tarif)} / JAM</div>
    <div class="info">
      <b>Waktu Masuk:</b><br>${data.waktu_masuk}<br><br>
      <b>Status:</b> ${data.status}<br><br>
      <b>Tarif Parkir</b><br>
      Mobil: Rp5.000 / jam<br>
      Motor: Rp3.000 / jam<br>
      Member: Rp150.000 / bulan
    </div>
    <div class="thanks">Simpan tiket ini sampai kendaraan keluar.<br>Terima kasih.</div>
  </div>
  <script>window.onload = function() { setTimeout(function(){ window.print(); }, 400); }<\/script>
</body>
</html>`;

    // =====================================================
    // DOWNLOAD SEBAGAI FILE HTML (langsung, tanpa popup)
    // =====================================================

    const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `tiket-parkir-${nomorTiket}.html`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

  } catch (error) {

    console.error("Error saat print tiket:", error);
    alert("Terjadi kesalahan saat mencetak tiket.");

  } finally {

    loading.value = false;

  }

};


// =========================================================
// AMBIL KODE MEMBER DARI HASIL SCAN
// =========================================================

const ambilKodeMember = (input: string) => {

  const teks = String(input || "").trim();

  if (!teks) {

    return "";

  }


  // =======================================================
  // KALAU SCANNER MENGIRIM JSON
  //
  // Contoh:
  // {"id":4,"kode_member":"MBR-20260916-NIWZ","nama_member":"LUNA","token":"..."}
  // =======================================================

  try {

    const data = JSON.parse(teks);


    if (data?.kode_member) {

      return String(
        data.kode_member
      ).trim();

    }


    if (data?.token) {

      return String(
        data.token
      ).trim();

    }

  } catch {

    // Bukan JSON.
    // Lanjut ke pengecekan kode biasa.

  }


  // =======================================================
  // KALAU LANGSUNG KODE MEMBER
  //
  // Contoh:
  // MBR-20260916-NIWZ
  // =======================================================

  const cocok =
    teks.match(
      /MBR-\d{8}-[A-Z0-9]+/i
    );


  if (cocok) {

    return cocok[0].toUpperCase();

  }


  // Kalau tidak cocok pola MBR,
  // kirim teks asli ke backend.
  return teks;

};


// =========================================================
// SCAN MEMBER → MASUK PARKIR
// =========================================================

const openGate = async () => {

  // Jangan proses scan kedua
  // saat request pertama masih berjalan
  if (loading.value) {

    return;

  }


  const inputScan =
    scanCard.value.trim();


  if (!inputScan) {

    return;

  }


  // Ambil kode member dari hasil scanner
  const kodeMember =
    ambilKodeMember(inputScan);


  if (!kodeMember) {

    alert(
      "Kode member tidak ditemukan."
    );

    scanCard.value = "";

    return;

  }


  loading.value = true;


  try {

    // =====================================================
    // KIRIM KE BACKEND
    // =====================================================

    const response: any = await $fetch(`${apiBase}/member/masuk`, {
      method: "POST",
      body: { kode_member: kodeMember },
      headers: { Accept: "application/json" },
    });

    const member = response?.data;


    // =====================================================
    // BERHASIL MASUK
    // =====================================================

    alert(
      "Kartu Member : " +
      (
        member?.kode_member ||
        kodeMember
      ) +
      "\n\n" +
      "Nama : " +
      (
        member?.nama_member ||
        "-"
      ) +
      "\n\n" +
      "Pintu terbuka."
    );


    // Kosongkan input
    scanCard.value = "";


  } catch (error: any) {

    console.error(
      "Gagal memproses kartu member:",
      error
    );


    const status =
      error?.response?.status;


    const data =
      error?.response?.data;


    // =====================================================
    // MEMBER SUDAH PARKIR
    // =====================================================

    if (status === 409) {

      alert(
        "AKSES DITOLAK\n\n" +
        (
          data?.message ||
          "Member sedang parkir. Tidak bisa masuk lagi."
        )
      );


    // =====================================================
    // MEMBER TIDAK DITEMUKAN
    // =====================================================

    } else if (status === 404) {

      alert(
        data?.message ||
        "Kartu member tidak ditemukan."
      );


    // =====================================================
    // ERROR LAIN
    // =====================================================

    } else {

      alert(
        data?.message ||
        "Gagal memproses kartu member."
      );

    }


    // Kosongkan input
    scanCard.value = "";


  } finally {

    loading.value = false;

  }

};

</script>