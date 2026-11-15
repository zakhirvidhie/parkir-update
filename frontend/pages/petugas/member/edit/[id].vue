<template>
    <div
        class="
            min-h-screen
            bg-slate-100
            flex
            items-center
            justify-center
            p-4
        "
    >

        <div
            class="
                bg-white
                w-full
                max-w-[380px]
                rounded-2xl
                shadow-xl
                p-5
            "
        >

            <!-- ========================================= -->
            <!-- JUDUL -->
            <!-- ========================================= -->

            <div class="text-center mb-5">

                <h1
                    class="
                        text-xl
                        font-bold
                        text-gray-800
                    "
                >
                    Pembayaran Member
                </h1>

                <p
                    class="
                        text-xs
                        text-gray-500
                        mt-1
                    "
                >
                    Perbarui pembayaran member
                </p>

            </div>


            <!-- ========================================= -->
            <!-- LOADING -->
            <!-- ========================================= -->

            <div
                v-if="loadingData"
                class="
                    py-10
                    text-center
                    text-gray-500
                    text-sm
                "
            >
                Memuat data member...
            </div>


            <!-- ========================================= -->
            <!-- FORM -->
            <!-- ========================================= -->

            <div v-else>

                <!-- ===================================== -->
                <!-- INFORMASI MEMBER -->
                <!-- ===================================== -->

                <div
                    class="
                        bg-slate-50
                        rounded-xl
                        p-4
                        mb-5
                        space-y-2
                    "
                >

                    <!-- KODE -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Kode Member
                        </span>

                        <span
                            class="
                                font-semibold
                                text-gray-800
                                break-all
                            "
                        >
                            :
                            {{ form.kode_member || "-" }}
                        </span>

                    </div>


                    <!-- NAMA -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Nama Member
                        </span>

                        <span
                            class="
                                font-semibold
                                text-gray-800
                                break-words
                            "
                        >
                            :
                            {{ form.nama_member || "-" }}
                        </span>

                    </div>


                    <!-- PERUSAHAAN -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Perusahaan
                        </span>

                        <span
                            class="
                                font-semibold
                                text-gray-800
                                break-words
                            "
                        >
                            :
                            {{ form.nama_perusahaan || "-" }}
                        </span>

                    </div>


                    <!-- TAGIHAN -->

                    <div class="flex text-sm">

                        <span
                            class="
                                w-[105px]
                                shrink-0
                                text-gray-500
                            "
                        >
                            Tagihan
                        </span>

                        <span
                            class="
                                font-bold
                                text-blue-600
                            "
                        >
                            :
                            Rp
                            {{ formatRupiah(form.total_harga) }}
                        </span>

                    </div>

                </div>


                <!-- ===================================== -->
                <!-- INPUT PEMBAYARAN -->
                <!-- ===================================== -->

                <label
                    class="
                        block
                        text-sm
                        font-semibold
                        text-gray-700
                        mb-2
                    "
                >
                    Masukkan Pembayaran
                </label>


                <div class="relative">

                    <span
                        class="
                            absolute
                            left-3
                            top-1/2
                            -translate-y-1/2
                            text-gray-500
                            font-semibold
                        "
                    >
                        Rp
                    </span>


                    <input
                        v-model.number="form.jumlah_bayar"
                        type="number"
                        min="0"
                        class="
                            w-full
                            border
                            border-gray-300
                            rounded-xl
                            py-3
                            pl-10
                            pr-3
                            focus:outline-none
                            focus:ring-2
                            focus:ring-green-500
                            focus:border-green-500
                        "
                        placeholder="Masukkan jumlah pembayaran"
                    />

                </div>


                <!-- ===================================== -->
                <!-- PREVIEW -->
                <!-- ===================================== -->

                <div
                    class="
                        mt-4
                        bg-gray-50
                        rounded-xl
                        p-4
                        space-y-2
                    "
                >

                    <!-- DIBAYAR -->

                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span class="text-gray-600">
                            Dibayar
                        </span>

                        <span class="font-semibold">
                            Rp
                            {{ formatRupiah(jumlahBayar) }}
                        </span>

                    </div>


                    <!-- TAGIHAN -->

                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span class="text-gray-600">
                            Tagihan
                        </span>

                        <span class="font-semibold">
                            Rp
                            {{ formatRupiah(totalHarga) }}
                        </span>

                    </div>


                    <!-- KEMBALIAN -->

                    <div
                        class="
                            flex
                            justify-between
                            text-sm
                        "
                    >

                        <span class="text-gray-600">
                            Kembalian
                        </span>

                        <span
                            class="
                                font-bold
                                text-blue-600
                            "
                        >
                            Rp
                            {{ formatRupiah(kembalian) }}
                        </span>

                    </div>


                    <!-- STATUS -->

                    <div
                        class="
                            flex
                            justify-between
                            items-center
                            pt-2
                            border-t
                            border-gray-200
                        "
                    >

                        <span class="text-gray-600">
                            Status
                        </span>


                        <span
                            class="
                                px-3
                                py-1
                                rounded-full
                                text-xs
                                font-bold
                            "
                            :class="
                                statusPembayaran === 'lunas'
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            "
                        >
                            {{
                                statusPembayaran === 'lunas'
                                    ? 'LUNAS'
                                    : 'BELUM LUNAS'
                            }}
                        </span>

                    </div>

                </div>


                <!-- ===================================== -->
                <!-- SIMPAN -->
                <!-- ===================================== -->

                <button
                    @click="updatePembayaran"
                    :disabled="loading"
                    class="
                        mt-5
                        w-full
                        bg-green-600
                        hover:bg-green-700
                        disabled:bg-gray-400
                        text-white
                        py-3
                        rounded-xl
                        font-bold
                        flex
                        items-center
                        justify-center
                        gap-2
                    "
                >

                    <span
                        v-if="loading"
                        class="
                            w-4
                            h-4
                            border-2
                            border-white
                            border-t-transparent
                            rounded-full
                            animate-spin
                        "
                    ></span>

                    {{
                        loading
                            ? 'Menyimpan...'
                            : 'Simpan Pembayaran'
                    }}

                </button>


                <!-- ===================================== -->
                <!-- KEMBALI -->
                <!-- ===================================== -->

                <button
                    @click="kembali"
                    :disabled="loading"
                    class="
                        mt-2
                        w-full
                        bg-gray-500
                        hover:bg-gray-600
                        disabled:bg-gray-400
                        text-white
                        py-3
                        rounded-xl
                        font-bold
                    "
                >
                    Kembali
                </button>

            </div>

        </div>

    </div>
</template>


<script setup lang="ts">

// =========================================================
// API & ROUTER
// =========================================================

const { $api } = useNuxtApp();

const router = useRouter();

const route = useRoute();


// =========================================================
// STATE
// =========================================================

const loading = ref(false);

const loadingData = ref(true);


// =========================================================
// FORM
// =========================================================

const form = reactive<any>({

    id: null,

    kode_member: "",

    nama_member: "",

    nama_perusahaan: "",

    total_harga: 150000,

    jumlah_bayar: 0,

    kembalian: 0,

    status: "belum lunas",

    tanggal_mulai: null,

    tanggal_expired: null

});


// =========================================================
// TOTAL HARGA
// =========================================================

const totalHarga = computed(() => {

    return Number(
        form.total_harga || 0
    );

});


// =========================================================
// JUMLAH BAYAR
// =========================================================

const jumlahBayar = computed(() => {

    const nilai =
        Number(
            form.jumlah_bayar || 0
        );

    if (nilai < 0) {
        return 0;
    }

    return nilai;

});


// =========================================================
// KEMBALIAN
// =========================================================

const kembalian = computed(() => {

    const hasil =
        jumlahBayar.value -
        totalHarga.value;


    if (hasil > 0) {

        return hasil;

    }

    return 0;

});


// =========================================================
// STATUS
// =========================================================

const statusPembayaran = computed(() => {

    if (
        jumlahBayar.value >=
        totalHarga.value
    ) {

        return "lunas";

    }

    return "belum lunas";

});


// =========================================================
// FORMAT RUPIAH
// =========================================================

const formatRupiah = (
    value: any
) => {

    return new Intl.NumberFormat(
        "id-ID"
    ).format(
        Number(value || 0)
    );

};


// =========================================================
// AMBIL DATA MEMBER
// =========================================================

const getData = async () => {

    try {

        loadingData.value = true;


        const response =
            await $api.get(
                `/member/${route.params.id}`
            );


        if (
            !response.data?.status
        ) {

            alert(
                response.data?.message ||
                "Data member tidak ditemukan"
            );

            router.push(
                "/petugas/member/select"
            );

            return;
        }


        const data =
            response.data.data;


        form.id =
            data.id;

        form.kode_member =
            data.kode_member || "";

        form.nama_member =
            data.nama_member || "";

        form.nama_perusahaan =
            data.nama_perusahaan || "";

        form.total_harga =
            Number(
                data.total_harga || 150000
            );

        form.jumlah_bayar =
            Number(
                data.jumlah_bayar || 0
            );

        form.kembalian =
            Number(
                data.kembalian || 0
            );

        form.status =
            data.status || "belum lunas";

        form.tanggal_mulai =
            data.tanggal_mulai || null;

        form.tanggal_expired =
            data.tanggal_expired || null;

    }
    catch (error: any) {

        console.error(
            "Gagal mengambil data member:",
            error
        );


        alert(
            error?.response?.data?.message ||
            "Gagal mengambil data member"
        );


        router.push(
            "/petugas/member/select"
        );

    }
    finally {

        loadingData.value = false;

    }

};


// =========================================================
// SIMPAN PEMBAYARAN
// =========================================================

const updatePembayaran = async () => {

    const bayar =
        Number(
            form.jumlah_bayar || 0
        );

    const harga =
        Number(
            form.total_harga || 0
        );


    // -----------------------------------------------
    // VALIDASI
    // -----------------------------------------------

    if (bayar < 0) {

        alert(
            "Pembayaran tidak boleh kurang dari Rp 0"
        );

        return;
    }


    // -----------------------------------------------
    // KONFIRMASI
    // -----------------------------------------------

    const yakin =
        confirm(
            `Simpan pembayaran Rp ${formatRupiah(bayar)}?`
        );


    if (!yakin) {
        return;
    }


    try {

        loading.value = true;


        // -------------------------------------------
        // KIRIM KE BACKEND
        // -------------------------------------------

        const response =
            await $api.put(

                `/member/${route.params.id}/pembayaran`,

                {
                    jumlah_bayar: bayar
                }

            );


        if (
            !response.data?.status
        ) {

            alert(
                response.data?.message ||
                "Pembayaran gagal"
            );

            return;
        }


        // -------------------------------------------
        // BERHASIL
        // -------------------------------------------

        alert(
            response.data?.message ||
            "Pembayaran berhasil disimpan"
        );


        router.push(
            "/petugas/member/select"
        );

    }
    catch (error: any) {

        console.error(
            "Gagal menyimpan pembayaran:",
            error
        );


        alert(
            error?.response?.data?.message ||
            "Gagal menyimpan pembayaran"
        );

    }
    finally {

        loading.value = false;

    }

};


// =========================================================
// KEMBALI
// =========================================================

const kembali = () => {

    router.push(
        "/petugas/member/select"
    );

};


// =========================================================
// LOAD
// =========================================================

onMounted(() => {

    getData();

});

</script>