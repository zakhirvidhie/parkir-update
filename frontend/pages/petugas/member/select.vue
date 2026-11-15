<template>
  <div class="min-h-screen bg-slate-100 p-8">

    <!-- ================================================= -->
    <!-- HEADER -->
    <!-- ================================================= -->

    <div class="flex items-center justify-between mb-6 print:hidden">

      <div>
        <h1 class="text-3xl font-black text-blue-700">
          Data Member
        </h1>

        <p class="text-sm text-slate-500 mt-1">
          Kelola data member parkir Plaza Andalas (Tarif: Rp 150.000 / Bulan)
        </p>
      </div>

      <div class="flex items-center gap-3">
        <button
          type="button"
          @click="navigateTo('/')"
          class="
            bg-slate-200
            hover:bg-slate-300
            text-slate-700
            font-bold
            px-4
            py-3
            rounded-xl
            transition
            cursor-pointer
          "
        >
          ← Dashboard
        </button>

        <button
          type="button"
          @click="tambahMember"
          class="
            bg-blue-600
            hover:bg-blue-700
            text-white
            font-bold
            px-5
            py-3
            rounded-xl
            shadow
            transition
            cursor-pointer
          "
        >
          + Tambah Member
        </button>
      </div>

    </div>


    <!-- ================================================= -->
    <!-- LOADING -->
    <!-- ================================================= -->

    <div
      v-if="loading"
      class="
        mb-5
        rounded-xl
        bg-blue-50
        border
        border-blue-200
        px-5
        py-4
        text-blue-700
        font-semibold
      "
    >
      Mengambil data member...
    </div>


    <!-- ================================================= -->
    <!-- ERROR -->
    <!-- ================================================= -->

    <div
      v-if="errorMessage"
      class="
        mb-5
        rounded-xl
        bg-red-50
        border
        border-red-300
        px-5
        py-4
        text-red-600
      "
    >

      <div class="flex items-center justify-between gap-4">

        <span>
          {{ errorMessage }}
        </span>

        <button
          type="button"
          @click="ambilMember"
          class="
            bg-red-600
            hover:bg-red-700
            text-white
            px-4
            py-2
            rounded-lg
            text-sm
            font-bold
          "
        >
          Coba Lagi
        </button>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- TABEL MEMBER -->
    <!-- ================================================= -->

    <div
      class="
        bg-white
        rounded-2xl
        shadow
        overflow-hidden
      "
    >

      <div class="overflow-x-auto">

        <table class="w-full">

          <thead>

            <tr class="border-b border-slate-300">

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Kode
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Nama Member
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Perusahaan
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Tagihan
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Dibayar
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Status
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-left
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Expired
              </th>

              <th
                class="
                  px-5
                  py-5
                  text-center
                  text-sm
                  font-bold
                  text-slate-800
                "
              >
                Aksi
              </th>

            </tr>

          </thead>


          <tbody>

            <!-- ================================================= -->
            <!-- DATA MEMBER -->
            <!-- ================================================= -->

            <tr
              v-for="member in members"
              :key="member.id"
              class="
                border-b
                border-slate-100
                hover:bg-slate-50
                transition
              "
            >

              <!-- KODE -->
              <td class="px-5 py-5">

                <span
                  class="
                    inline-flex
                    items-center
                    px-3
                    py-1
                    rounded-lg
                    bg-blue-50
                    text-blue-700
                    font-bold
                    text-sm
                  "
                >
                  {{ member.kode_member || "-" }}
                </span>

              </td>


              <!-- NAMA -->
              <td class="px-5 py-5">

                <div class="font-bold text-slate-800">
                  {{ member.nama_member || "-" }}
                </div>

              </td>


              <!-- PERUSAHAAN -->
              <td class="px-5 py-5">

                <span class="text-slate-600">
                  {{ member.nama_perusahaan || "-" }}
                </span>

              </td>


              <!-- TAGIHAN -->
              <td class="px-5 py-5">

                <span class="font-semibold text-slate-700">
                  {{ formatRupiah(member.total_harga) }}
                </span>

              </td>


              <!-- DIBAYAR -->
              <td class="px-5 py-5">

                <span class="font-semibold text-slate-700">
                  {{ formatRupiah(member.jumlah_bayar) }}
                </span>

              </td>


              <!-- STATUS -->
              <td class="px-5 py-5">

                <span
                  v-if="isLunas(member)"
                  class="
                    inline-flex
                    px-3
                    py-1
                    rounded-full
                    bg-green-100
                    text-green-700
                    text-xs
                    font-bold
                  "
                >
                  Lunas
                </span>

                <span
                  v-else
                  class="
                    inline-flex
                    px-3
                    py-1
                    rounded-full
                    bg-yellow-100
                    text-yellow-700
                    text-xs
                    font-bold
                  "
                >
                  Belum Lunas
                </span>

              </td>


              <!-- EXPIRED -->
              <td class="px-5 py-5">

                <span
                  :class="expiredClass(member.tanggal_expired)"
                >
                  {{ formatTanggal(member.tanggal_expired) }}
                </span>

              </td>


              <!-- AKSI -->
              <td class="px-5 py-5">

                <div
                  class="
                    flex
                    items-center
                    justify-center
                    gap-2
                  "
                >

                  <!-- INVOICE -->
                  <button
                    type="button"
                    @click="lihatInvoice(member)"
                    class="
                      px-3
                      py-2
                      rounded-lg
                      text-xs
                      font-bold
                      bg-purple-100
                      text-purple-700
                      hover:bg-purple-200
                      cursor-pointer
                    "
                  >
                    Invoice
                  </button>

                  <!-- EDIT -->
                  <button
                    type="button"
                    @click="editMember(member)"
                    :disabled="isLunas(member)"
                    class="
                      px-3
                      py-2
                      rounded-lg
                      text-xs
                      font-bold
                      bg-blue-100
                      text-blue-700
                      hover:bg-blue-200
                      disabled:opacity-40
                      disabled:cursor-not-allowed
                      cursor-pointer
                    "
                  >
                    Edit
                  </button>

                  <!-- LIHAT -->
                  <button
                    type="button"
                    @click="lihatMember(member)"
                    class="
                      px-3
                      py-2
                      rounded-lg
                      text-xs
                      font-bold
                      bg-slate-100
                      text-slate-700
                      hover:bg-slate-200
                      cursor-pointer
                    "
                  >
                    Lihat
                  </button>

                  <!-- BAYAR -->
                  <button
                    type="button"
                    @click="bayarMember(member)"
                    :disabled="isLunas(member)"
                    class="
                      px-3
                      py-2
                      rounded-lg
                      text-xs
                      font-bold
                      bg-green-100
                      text-green-700
                      hover:bg-green-200
                      disabled:opacity-40
                      disabled:cursor-not-allowed
                      cursor-pointer
                    "
                  >
                    Bayar
                  </button>

                </div>

              </td>

            </tr>


            <!-- ================================================= -->
            <!-- TIDAK ADA DATA -->
            <!-- ================================================= -->

            <tr
              v-if="!loading && members.length === 0"
            >

              <td
                colspan="8"
                class="
                  px-5
                  py-12
                  text-center
                  text-slate-400
                "
              >

                <div class="text-4xl mb-3">
                  👥
                </div>

                <p class="font-semibold">
                  Belum ada data member
                </p>

                <p class="text-sm mt-1">
                  Klik tombol "+ Tambah Member"
                  untuk menambahkan member.
                </p>

              </td>

            </tr>

          </tbody>

        </table>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- MODAL DETAIL MEMBER -->
    <!-- ================================================= -->

    <div
      v-if="showDetail"
      class="
        fixed
        inset-0
        bg-black/50
        flex
        items-center
        justify-center
        z-50
        p-5
      "
    >

      <div
        class="
          bg-white
          w-full
          max-w-md
          max-h-[90vh]
          overflow-y-auto
          rounded-2xl
          shadow-2xl
          p-6
        "
      >

        <!-- HEADER MODAL -->

        <div
          class="
            flex
            justify-between
            items-center
            mb-5
          "
        >

          <h2
            class="
              text-xl
              font-black
              text-slate-800
            "
          >
            Detail Member
          </h2>

          <button
            type="button"
            @click="tutupDetail"
            class="
              text-slate-400
              hover:text-slate-700
              text-xl
            "
          >
            ✕
          </button>

        </div>


        <div
          v-if="selectedMember"
          class="space-y-4"
        >

          <!-- ================================================= -->
          <!-- QR CODE -->
          <!-- ================================================= -->

          <div
            class="
              flex
              flex-col
              items-center
              justify-center
              pb-5
              mb-2
              border-b
              border-slate-200
            "
          >

            <p
              class="
                text-xs
                text-slate-400
                mb-3
              "
            >
              QR Code Member
            </p>


            <div
              class="
                w-48
                h-48
                bg-white
                border
                border-slate-200
                rounded-xl
                p-3
                shadow-sm
                flex
                items-center
                justify-center
              "
            >

              <img
                v-if="qrMember"
                :src="qrMember"
                alt="QR Code Member"
                class="
                  w-full
                  h-full
                  object-contain
                "
              />

              <span
                v-else
                class="
                  text-xs
                  text-slate-400
                  text-center
                "
              >
                Membuat QR...
              </span>

            </div>


            <p
              class="
                mt-3
                text-sm
                font-black
                text-blue-700
              "
            >
              {{ selectedMember.kode_member }}
            </p>


            <p
              class="
                text-xs
                text-slate-400
                mt-1
                text-center
              "
            >
              Scan QR ini untuk akses parkir member
            </p>

          </div>


          <!-- KODE MEMBER -->

          <div>

            <p class="text-xs text-slate-400">
              Kode Member
            </p>

            <p
              class="
                font-bold
                text-blue-700
              "
            >
              {{ selectedMember.kode_member }}
            </p>

          </div>


          <!-- NAMA -->

          <div>

            <p class="text-xs text-slate-400">
              Nama
            </p>

            <p
              class="
                font-bold
                text-slate-800
              "
            >
              {{ selectedMember.nama_member }}
            </p>

          </div>


          <!-- PERUSAHAAN -->

          <div>

            <p class="text-xs text-slate-400">
              Perusahaan
            </p>

            <p
              class="
                font-semibold
                text-slate-700
              "
            >
              {{ selectedMember.nama_perusahaan || "-" }}
            </p>

          </div>


          <!-- TAGIHAN -->

          <div>

            <p class="text-xs text-slate-400">
              Tagihan
            </p>

            <p
              class="
                font-bold
                text-slate-800
              "
            >
              {{ formatRupiah(selectedMember.total_harga) }}
            </p>

          </div>


          <!-- DIBAYAR -->

          <div>

            <p class="text-xs text-slate-400">
              Dibayar
            </p>

            <p
              class="
                font-bold
                text-green-600
              "
            >
              {{ formatRupiah(selectedMember.jumlah_bayar) }}
            </p>

          </div>


          <!-- STATUS -->

          <div>

            <p class="text-xs text-slate-400">
              Status
            </p>

            <p
              class="font-bold"
              :class="
                isLunas(selectedMember)
                  ? 'text-green-600'
                  : 'text-yellow-600'
              "
            >
              {{ selectedMember.status || "-" }}
            </p>

          </div>


          <!-- EXPIRED -->

          <div>

            <p class="text-xs text-slate-400">
              Tanggal Expired
            </p>

            <p class="font-semibold text-slate-700">
              {{ formatTanggal(selectedMember.tanggal_expired) }}
            </p>

          </div>


          <!-- STATUS PARKIR -->

          <div>

            <p class="text-xs text-slate-400">
              Status Parkir
            </p>


            <span
              v-if="selectedMember.sedang_parkir"
              class="
                inline-flex
                mt-1
                px-3
                py-1
                rounded-full
                bg-orange-100
                text-orange-700
                text-xs
                font-bold
              "
            >
              Sedang Parkir
            </span>


            <span
              v-else
              class="
                inline-flex
                mt-1
                px-3
                py-1
                rounded-full
                bg-slate-100
                text-slate-600
                text-xs
                font-bold
              "
            >
              Tidak Parkir
            </span>

          </div>

        </div>


        <!-- TUTUP -->

        <button
          type="button"
          @click="tutupDetail"
          class="
            mt-6
            w-full
            bg-blue-600
            hover:bg-blue-700
            text-white
            py-3
            rounded-xl
            font-bold
          "
        >
          Tutup
        </button>

      </div>

    </div>


    <!-- ================================================= -->
    <!-- MODAL PEMBAYARAN -->
    <!-- ================================================= -->

    <div
      v-if="showPayment"
      class="
        fixed
        inset-0
        bg-black/50
        flex
        items-center
        justify-center
        z-50
        p-5
      "
    >

      <div
        class="
          bg-white
          w-full
          max-w-md
          rounded-2xl
          shadow-2xl
          p-6
        "
      >

        <div
          class="
            flex
            justify-between
            items-center
            mb-5
          "
        >

          <h2
            class="
              text-xl
              font-black
              text-slate-800
            "
          >
            Pembayaran Member
          </h2>


          <button
            type="button"
            @click="showPayment = false"
            class="
              text-slate-400
              hover:text-slate-700
              text-xl
            "
          >
            ✕
          </button>

        </div>


        <div v-if="selectedMember">

          <!-- MEMBER -->

          <div class="mb-4">

            <p class="text-xs text-slate-400">
              Member
            </p>

            <p class="font-bold text-slate-800">
              {{ selectedMember.nama_member }}
            </p>

            <p
              class="
                text-sm
                text-blue-600
                font-semibold
              "
            >
              {{ selectedMember.kode_member }}
            </p>

          </div>


          <!-- TAGIHAN -->

          <div
            class="
              bg-slate-50
              rounded-xl
              p-4
              mb-4
            "
          >

            <div
              class="
                flex
                justify-between
              "
            >

              <span class="text-slate-500">
                Tagihan
              </span>

              <span class="font-bold">
                {{
                  formatRupiah(
                    selectedMember.total_harga
                  )
                }}
              </span>

            </div>

          </div>


          <!-- INPUT BAYAR -->

          <label class="block">

            <span
              class="
                text-sm
                font-bold
                text-slate-700
              "
            >
              Jumlah Bayar
            </span>


            <input
              v-model="jumlahBayar"
              type="number"
              min="0"
              placeholder="Masukkan jumlah pembayaran"
              class="
                mt-2
                w-full
                h-12
                border
                border-slate-300
                rounded-xl
                px-4
                outline-none
                focus:ring-2
                focus:ring-blue-500
              "
            />

          </label>


          <!-- SIMPAN -->

          <button
            type="button"
            @click="prosesPembayaran"
            :disabled="paymentLoading"
            class="
              mt-5
              w-full
              bg-green-600
              hover:bg-green-700
              text-white
              py-3
              rounded-xl
              font-bold
              disabled:opacity-50
            "
          >

            {{
              paymentLoading
                ? "Memproses..."
                : "Simpan Pembayaran"
            }}

          </button>

        </div>

      </div>

    </div>

    <!-- ===================================================== -->
    <!-- MODAL INVOICE PEMBAYARAN MEMBER -->
    <!-- ===================================================== -->
    <div
      v-if="showInvoice && invoiceMember"
      class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-100 print:shadow-none print:border-none print:m-0 print:w-full">

        <!-- INVOICE PRINTABLE -->
        <div id="invoice-member-printable" class="p-8">

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
              <span
                :class="isLunas(invoiceMember) ? 'bg-emerald-100 text-emerald-700' : 'bg-yellow-100 text-yellow-700'"
                class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider mb-1"
              >
                {{ invoiceMember.status || 'LUNAS' }}
              </span>
              <p class="text-xs font-mono font-bold text-slate-700">
                INV-{{ invoiceMember.kode_member || invoiceMember.id }}
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
                <span class="font-bold text-slate-800 text-sm">{{ invoiceMember.nama_member }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Perusahaan</span>
                <span class="font-bold text-slate-800">{{ invoiceMember.nama_perusahaan }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Kode Member</span>
                <span class="font-mono font-bold text-blue-600">{{ invoiceMember.kode_member }}</span>
              </div>
              <div>
                <span class="text-slate-400 block text-[10px] font-bold uppercase">Periode Aktif</span>
                <span class="font-bold text-slate-800">{{ formatTanggal(invoiceMember.tanggal_mulai) }} - {{ formatTanggal(invoiceMember.tanggal_expired) }}</span>
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
                <td class="py-3 text-right font-bold">{{ formatRupiah(invoiceMember.total_harga || 150000) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="border-t-2 border-slate-200">
                <td colspan="2" class="pt-3 font-bold text-slate-700">Total Tagihan:</td>
                <td class="pt-3 text-right font-black text-sm text-slate-900">{{ formatRupiah(invoiceMember.total_harga || 150000) }}</td>
              </tr>
              <tr>
                <td colspan="2" class="pt-1.5 text-slate-500">Jumlah Dibayar:</td>
                <td class="pt-1.5 text-right font-bold text-slate-800">{{ formatRupiah(invoiceMember.jumlah_bayar || 150000) }}</td>
              </tr>
              <tr>
                <td colspan="2" class="pt-1.5 text-slate-500">Kembalian:</td>
                <td class="pt-1.5 text-right font-bold text-emerald-600">{{ formatRupiah(invoiceMember.kembalian || 0) }}</td>
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
                Token: {{ invoiceMember.token ? invoiceMember.token.slice(0, 16) + '...' : '-' }}
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

        <!-- MODAL ACTION BUTTONS -->
        <div class="bg-slate-50 px-8 py-4 border-t border-slate-100 flex items-center justify-end gap-3 print:hidden">
          <button
            type="button"
            @click="cetakInvoice"
            class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs flex items-center gap-2 shadow-sm transition cursor-pointer"
          >
            🖨️ Cetak Invoice
          </button>
          <button
            type="button"
            @click="tutupInvoice"
            class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/25 transition cursor-pointer"
          >
            Tutup
          </button>
        </div>

      </div>
    </div>

  </div>
</template>


<script setup lang="ts">

import { ref, onMounted } from "vue";
import QRCode from "qrcode";


// =========================================================
// CONFIG
// =========================================================

const config = useRuntimeConfig();


// =========================================================
// API BASE
// =========================================================

const apiBase = String(
  config.public.apiBase ||
  "http://127.0.0.1:8000/api"
).replace(/\/+$/, "");


// =========================================================
// STATE
// =========================================================

const members = ref<any[]>([]);

const loading = ref(false);

const errorMessage = ref("");

const showDetail = ref(false);

const showPayment = ref(false);

const showInvoice = ref(false);
const invoiceMember = ref<any>(null);
const invoiceQr = ref("");
const invoiceDate = ref("");

const selectedMember = ref<any>(null);

const qrMember = ref("");

const jumlahBayar = ref("");

const paymentLoading = ref(false);


// =========================================================
// AMBIL DATA MEMBER
// =========================================================

const ambilMember = async () => {

  loading.value = true;

  errorMessage.value = "";

  try {

    const response: any = await $fetch(
      `${apiBase}/member`,
      {
        method: "GET",
      }
    );


    console.log(
      "RESPON MEMBER:",
      response
    );


    if (
      response &&
      Array.isArray(response.data)
    ) {

      members.value = response.data;

    } else if (
      Array.isArray(response)
    ) {

      members.value = response;

    } else {

      members.value = [];

      errorMessage.value =
        "Format data member dari server tidak sesuai.";

    }


  } catch (error: any) {

    console.error(
      "ERROR MEMBER:",
      error
    );


    let pesan =
      "Gagal mengambil data member dari server.";


    if (error?.data?.message) {

      pesan =
        error.data.message;

    } else if (
      error?.response?._data?.message
    ) {

      pesan =
        error.response._data.message;

    } else if (
      error?.message
    ) {

      pesan =
        error.message;

    }


    errorMessage.value = pesan;

    members.value = [];


  } finally {

    loading.value = false;

  }

};


// =========================================================
// TAMBAH MEMBER
// =========================================================

const tambahMember = () => {

  navigateTo(
    "/petugas/member/tambah"
  );

};


// =========================================================
// EDIT MEMBER
// =========================================================

const editMember = (
  member: any
) => {

  if (isLunas(member)) {
    return;
  }


  navigateTo(
    `/petugas/member/edit/${member.id}`
  );

};


// =========================================================
// LIHAT MEMBER + BUAT QR
// =========================================================

const lihatMember = async (
  member: any
) => {

  selectedMember.value = member;

  qrMember.value = "";


  try {

    /*
     * DATA YANG DIMASUKKAN KE QR
     *
     * Scanner nanti bisa membaca:
     * id
     * kode_member
     * nama_member
     * token
     */

    const dataQr = JSON.stringify({
      id: member.id,
      kode_member: member.kode_member,
      nama_member: member.nama_member,
      token: member.token,
    });


    qrMember.value =
      await QRCode.toDataURL(
        dataQr,
        {
          width: 300,
          margin: 2,
          errorCorrectionLevel: "M",
        }
      );


  } catch (error) {

    console.error(
      "Gagal membuat QR member:",
      error
    );

    alert(
      "Gagal membuat QR Code member."
    );

  }


  showDetail.value = true;

};


// =========================================================
// TUTUP DETAIL
// =========================================================

const tutupDetail = () => {

  showDetail.value = false;

  qrMember.value = "";

};


// =========================================================
// LIHAT INVOICE
// =========================================================

const lihatInvoice = async (member: any) => {
  invoiceMember.value = member;
  invoiceQr.value = "";
  invoiceDate.value = new Date().toLocaleString("id-ID", {
    dateStyle: "full",
    timeStyle: "short",
  });

  try {
    const dataQr = JSON.stringify({
      id: member.id,
      kode_member: member.kode_member,
      nama_member: member.nama_member,
      token: member.token,
    });

    invoiceQr.value = await QRCode.toDataURL(dataQr, {
      width: 250,
      margin: 2,
      errorCorrectionLevel: "M",
    });
  } catch (error) {
    console.error("Gagal membuat QR invoice:", error);
  }

  showInvoice.value = true;
};

const tutupInvoice = () => {
  showInvoice.value = false;
  invoiceMember.value = null;
  invoiceQr.value = "";
};

const cetakInvoice = () => {
  window.print();
};


// =========================================================
// BAYAR MEMBER
// =========================================================

const bayarMember = (
  member: any
) => {

  if (isLunas(member)) {
    return;
  }


  selectedMember.value = member;

  jumlahBayar.value = "";

  showPayment.value = true;

};


// =========================================================
// PROSES PEMBAYARAN
// =========================================================

const prosesPembayaran = async () => {

  if (!selectedMember.value) {
    return;
  }


  if (
    !jumlahBayar.value ||
    Number(jumlahBayar.value) < 0
  ) {

    alert(
      "Masukkan jumlah pembayaran yang benar."
    );

    return;

  }


  paymentLoading.value = true;


  try {

    const response: any =
      await $fetch(
        `${apiBase}/member/${selectedMember.value.id}/pembayaran`,
        {
          method: "PUT",

          body: {
            jumlah_bayar:
              Number(jumlahBayar.value),
          },
        }
      );


    console.log(
      "RESPON PEMBAYARAN:",
      response
    );


    alert(
      response?.message ||
      "Pembayaran berhasil disimpan."
    );


    showPayment.value = false;

    selectedMember.value = null;

    jumlahBayar.value = "";


    await ambilMember();


  } catch (error: any) {

    console.error(
      "ERROR PEMBAYARAN:",
      error
    );


    alert(
      error?.data?.message ||
      error?.response?._data?.message ||
      "Gagal menyimpan pembayaran."
    );


  } finally {

    paymentLoading.value = false;

  }

};


// =========================================================
// CEK LUNAS
// =========================================================

const isLunas = (
  member: any
) => {

  return String(
    member?.status || ""
  ).toLowerCase() === "lunas";

};


// =========================================================
// FORMAT RUPIAH
// =========================================================

const formatRupiah = (
  angka: any
) => {

  const nilai =
    Number(angka || 0);


  return new Intl.NumberFormat(
    "id-ID",
    {
      style: "currency",
      currency: "IDR",
      minimumFractionDigits: 0,
    }
  ).format(nilai);

};


// =========================================================
// FORMAT TANGGAL
// =========================================================

const formatTanggal = (
  tanggal: any
) => {

  if (!tanggal) {
    return "-";
  }


  const date =
    new Date(tanggal);


  if (
    Number.isNaN(
      date.getTime()
    )
  ) {

    return tanggal;

  }


  return date.toLocaleDateString(
    "id-ID",
    {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    }
  );

};


// =========================================================
// WARNA EXPIRED
// =========================================================

const expiredClass = (
  tanggal: any
) => {

  if (!tanggal) {

    return "text-slate-500";

  }


  const expired =
    new Date(tanggal);

  const sekarang =
    new Date();


  if (
    expired <
    sekarang
  ) {

    return "text-red-600 font-bold";

  }


  return "text-slate-600";

};


// =========================================================
// LOAD DATA
// =========================================================

onMounted(() => {

  ambilMember();

});

</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #invoice-member-printable,
  #invoice-member-printable * {
    visibility: visible;
  }
  #invoice-member-printable {
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