<script setup lang="ts">
import { ref, onMounted, computed } from "vue";

const sidebarOpen = ref(true);

const members = ref<any[]>([]);
const loading = ref(true);
const error = ref("");

/*
|--------------------------------------------------------------------------
| API
|--------------------------------------------------------------------------
*/

const config = useRuntimeConfig();

const API_URL =
  config.public.apiBase || "http://127.0.0.1:8000/api";

/*
|--------------------------------------------------------------------------
| MENU
|--------------------------------------------------------------------------
*/

const menuOperasional = [
  {
    label: "Scan Keluar",
    icon: "↙",
    description: "Kendaraan keluar",
    path: "/petugas/scan-keluar",
  },
  {
    label: "Transaksi",
    icon: "Rp",
    description: "Pembayaran parkir",
    path: "/petugas/transaksi/payment",
  },
];

const menuData = [
  {
    label: "Member",
    icon: "♙",
    description: "Kelola data member",
    path: "/petugas/member/select",
  },
  {
    label: "Tiket",
    icon: "▣",
    description: "Kelola tiket parkir",
    path: "/petugas/tiket",
  },
];

const menuLaporan = [
  {
    label: "Laporan",
    icon: "▤",
    description: "Laporan parkir",
    path: "/petugas/laporan",
  },
];

const menuAdmin = [
  {
    label: "Super Admin",
    icon: "⭐",
    description: "Kelola akun & sistem",
    path: "/superadmin",
  },
];

/*
|--------------------------------------------------------------------------
| NAVIGASI
|--------------------------------------------------------------------------
*/

const goTo = (path: string) => {
  navigateTo(path);
};

/*
|--------------------------------------------------------------------------
| FORMAT RUPIAH
|--------------------------------------------------------------------------
*/

const formatRupiah = (value: number | string) => {
  const angka = Number(value || 0);

  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(angka);
};

/*
|--------------------------------------------------------------------------
| AMBIL DATA MEMBER
|--------------------------------------------------------------------------
*/

const loadMembers = async () => {
  loading.value = true;
  error.value = "";

  try {
    const response: any = await $fetch(
      `${API_URL}/member`
    );

    if (response?.status === true) {
      members.value = response.data || [];
    } else {
      members.value = [];
    }
  } catch (err) {
    console.error("Gagal mengambil data member:", err);

    error.value = "Data member gagal dimuat.";
    members.value = [];
  } finally {
    loading.value = false;
  }
};

/*
|--------------------------------------------------------------------------
| TOTAL MEMBER
|--------------------------------------------------------------------------
*/

const totalMember = computed(() => {
  return members.value.length;
});

/*
|--------------------------------------------------------------------------
| MEMBER LUNAS
|--------------------------------------------------------------------------
*/

const memberLunas = computed(() => {
  return members.value.filter((member) => {
    const jumlahBayar = Number(
      member.jumlah_bayar || 0
    );

    const totalHarga = Number(
      member.total_harga || 0
    );

    return jumlahBayar >= totalHarga;
  }).length;
});

/*
|--------------------------------------------------------------------------
| TOTAL PEMBAYARAN MEMBER
|--------------------------------------------------------------------------
*/

const totalPembayaranMember = computed(() => {
  return members.value.reduce((total, member) => {
    return (
      total +
      Number(member.jumlah_bayar || 0)
    );
  }, 0);
});

/*
|--------------------------------------------------------------------------
| MEMBER TERBARU
|--------------------------------------------------------------------------
*/

const memberTerbaru = computed(() => {
  return members.value.slice(0, 4);
});

/*
|--------------------------------------------------------------------------
| STATUS MEMBER
|--------------------------------------------------------------------------
*/

const getStatus = (member: any) => {
  const jumlahBayar = Number(
    member.jumlah_bayar || 0
  );

  const totalHarga = Number(
    member.total_harga || 0
  );

  if (jumlahBayar >= totalHarga) {
    return "Lunas";
  }

  return "Belum Lunas";
};

const userRole = ref("Petugas");
const userEmail = ref("");

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

const handleLogout = () => {
  if (import.meta.client) {
    localStorage.removeItem("role");
    localStorage.removeItem("user_email");
  }
  navigateTo("/login");
};

/*
|--------------------------------------------------------------------------
| LOAD
|--------------------------------------------------------------------------
*/

onMounted(() => {
  const savedRole = localStorage.getItem("role");

  if (!savedRole) {
    navigateTo("/login");
    return;
  }

  userRole.value = savedRole === "superadmin" ? "Super Admin" : "Petugas";
  userEmail.value = localStorage.getItem("user_email") || "";

  loadMembers();
});
</script>

<template>
  <div class="dashboard">

    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside
      class="sidebar"
      :class="{ collapsed: !sidebarOpen }"
    >

      <!-- LOGO -->
      <div class="logo-area">

        <div class="logo-icon">
          P
        </div>

        <div
          v-if="sidebarOpen"
          class="logo-text"
        >
          <h1>PARKZONE</h1>
          <span>SMART PARKING</span>
        </div>

      </div>

      <!-- PROFILE -->
      <div
        v-if="sidebarOpen"
        class="profile"
      >

        <div class="profile-avatar">
          {{ userRole === 'Super Admin' ? '👑' : '🛡️' }}
        </div>

        <div>
          <strong>{{ userRole }}</strong>

          <small>
            <span class="online-dot"></span>
            {{ userEmail || 'Online' }}
          </small>
        </div>

      </div>

      <!-- NAVIGATION -->
      <nav class="navigation">

        <!-- MENU UTAMA -->
        <div
          v-if="sidebarOpen"
          class="menu-title"
        >
          MENU UTAMA
        </div>

        <button
          class="nav-item active"
          @click="goTo('/petugas')"
        >

          <span class="nav-icon">
            ⌂
          </span>

          <span v-if="sidebarOpen">
            Dashboard
          </span>

        </button>

        <!-- OPERASIONAL -->
        <div
          v-if="sidebarOpen"
          class="menu-title"
        >
          OPERASIONAL
        </div>

        <button
          v-for="item in menuOperasional"
          :key="item.label"
          class="nav-item"
          @click="goTo(item.path)"
        >

          <span class="nav-icon">
            {{ item.icon }}
          </span>

          <div
            v-if="sidebarOpen"
            class="nav-content"
          >

            <span>
              {{ item.label }}
            </span>

            <small>
              {{ item.description }}
            </small>

          </div>

        </button>

        <!-- DATA -->
        <div
          v-if="sidebarOpen"
          class="menu-title"
        >
          DATA
        </div>

        <button
          v-for="item in menuData"
          :key="item.label"
          class="nav-item"
          @click="goTo(item.path)"
        >

          <span class="nav-icon">
            {{ item.icon }}
          </span>

          <div
            v-if="sidebarOpen"
            class="nav-content"
          >

            <span>
              {{ item.label }}
            </span>

            <small>
              {{ item.description }}
            </small>

          </div>

        </button>

        <!-- LAPORAN -->
        <div
          v-if="sidebarOpen"
          class="menu-title"
        >
          LAPORAN
        </div>

        <button
          v-for="item in menuLaporan"
          :key="item.label"
          class="nav-item"
          @click="goTo(item.path)"
        >

          <span class="nav-icon">
            {{ item.icon }}
          </span>

          <div
            v-if="sidebarOpen"
            class="nav-content"
          >

            <span>
              {{ item.label }}
            </span>

            <small>
              {{ item.description }}
            </small>

          </div>

        </button>

      </nav>

      <!-- BOTTOM -->
      <div class="sidebar-bottom">

        <button
          class="nav-item"
          @click="goTo('/petugas/profil')"
        >

          <span class="nav-icon">
            ⚙
          </span>

          <span v-if="sidebarOpen">
            Profil Saya
          </span>

        </button>

        <button
          class="logout-button"
          @click="handleLogout"
        >

          <span>
            ↪
          </span>

          <span v-if="sidebarOpen">
            Logout
          </span>

        </button>

      </div>

    </aside>

    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">

      <!-- TOPBAR -->
      <header class="topbar">

        <button
          class="toggle-button"
          @click="sidebarOpen = !sidebarOpen"
        >
          ☰
        </button>

        <div class="breadcrumb">

          <span>
            PARKZONE
          </span>

          <b>
            /
          </b>

          <strong>
            Dashboard
          </strong>

        </div>

        <div class="top-right">

          <div class="date">

            <span>
              ●
            </span>

            Sistem Online

          </div>

          <div class="top-avatar">
            P
          </div>

        </div>

      </header>

      <!-- =====================================================
           CONTENT
      ====================================================== -->

      <section class="content">

        <!-- WELCOME -->
        <div class="welcome">

          <div>

            <p class="eyebrow">
              CONTROL CENTER
            </p>

            <h2>
              Selamat datang,
              <span>Petugas</span> 👋
            </h2>

            <p class="welcome-text">
              Pantau aktivitas parkir dan kelola
              data member dari satu tempat.
            </p>

          </div>

          <div class="system-status">

            <span class="status-circle"></span>

            <div>

              <strong>
                System Online
              </strong>

              <small>
                Laravel API terhubung
              </small>

            </div>

          </div>

        </div>

        <!-- =================================================
             STATISTICS
        ================================================== -->

        <div class="stats">

          <!-- SCAN KELUAR -->
          <button
            class="stat-card green clickable"
            @click="goTo('/petugas/scan-keluar')"
          >

            <div class="stat-top">

              <span class="stat-label">
                KENDARAAN KELUAR
              </span>

              <div class="stat-icon">
                ↙
              </div>

            </div>

            <strong class="stat-number">
              -
            </strong>

            <p>
              Klik untuk Scan Keluar
            </p>

          </button>

          <!-- TRANSAKSI -->
          <button
            class="stat-card blue clickable"
            @click="
              goTo('/petugas/transaksi/payment')
            "
          >

            <div class="stat-top">

              <span class="stat-label">
                TRANSAKSI
              </span>

              <div class="stat-icon">
                Rp
              </div>

            </div>

            <strong class="stat-number">
              -
            </strong>

            <p>
              Pembayaran parkir
            </p>

          </button>

          <!-- PENDAPATAN MEMBER -->
          <button
            class="stat-card orange clickable"
            @click="
              goTo('/petugas/member/select')
            "
          >

            <div class="stat-top">

              <span class="stat-label">
                PENDAPATAN MEMBER
              </span>

              <div class="stat-icon">
                Rp
              </div>

            </div>

            <strong class="stat-number money">
              {{ formatRupiah(
                totalPembayaranMember
              ) }}
            </strong>

            <p>
              Total pembayaran member
            </p>

          </button>

          <!-- MEMBER -->
          <button
            class="stat-card purple clickable"
            @click="
              goTo('/petugas/member/select')
            "
          >

            <div class="stat-top">

              <span class="stat-label">
                MEMBER TERDAFTAR
              </span>

              <div class="stat-icon">
                ♙
              </div>

            </div>

            <strong class="stat-number">
              {{ totalMember }}
            </strong>

            <p>
              {{ memberLunas }} member lunas
            </p>

          </button>

        </div>

        <!-- =================================================
             DASHBOARD GRID
        ================================================== -->

        <div class="dashboard-grid">

          <!-- QUICK ACTION -->
          <section class="panel">

            <div class="panel-header">

              <div>

                <p class="panel-label">
                  QUICK ACTION
                </p>

                <h3>
                  Aksi Operasional
                </h3>

              </div>

            </div>

            <div class="quick-actions">

              <!-- SCAN KELUAR -->
              <button
                class="quick-card keluar"
                @click="
                  goTo('/petugas/scan-keluar')
                "
              >

                <div class="quick-icon">
                  ↙
                </div>

                <div>

                  <strong>
                    Scan Keluar
                  </strong>

                  <span>
                    Proses kendaraan keluar
                  </span>

                </div>

                <b>
                  →
                </b>

              </button>

              <!-- TRANSAKSI -->
              <button
                class="quick-card transaksi"
                @click="
                  goTo('/petugas/transaksi/payment')
                "
              >

                <div class="quick-icon">
                  Rp
                </div>

                <div>

                  <strong>
                    Transaksi
                  </strong>

                  <span>
                    Kelola pembayaran parkir
                  </span>

                </div>

                <b>
                  →
                </b>

              </button>

              <!-- MEMBER -->
              <button
                class="quick-card member"
                @click="
                  goTo('/petugas/member/select')
                "
              >

                <div class="quick-icon">
                  ♙
                </div>

                <div>

                  <strong>
                    Kelola Member
                  </strong>

                  <span>
                    Data dan pembayaran member
                  </span>

                </div>

                <b>
                  →
                </b>

              </button>

            </div>

          </section>

          <!-- =================================================
               MEMBER TERBARU
          ================================================== -->

          <section class="panel">

            <div class="panel-header activity-header">

              <div>

                <p class="panel-label">
                  DATA MEMBER
                </p>

                <h3>
                  Member Terbaru
                </h3>

              </div>

              <button
                class="lihat-semua"
                @click="
                  goTo('/petugas/member/select')
                "
              >
                Lihat semua
              </button>

            </div>

            <!-- LOADING -->
            <div
              v-if="loading"
              class="member-loading"
            >
              Memuat data member...
            </div>

            <!-- ERROR -->
            <div
              v-else-if="error"
              class="member-error"
            >
              {{ error }}
            </div>

            <!-- DATA -->
            <div
              v-else-if="memberTerbaru.length > 0"
              class="member-list"
            >

              <button
                v-for="member in memberTerbaru"
                :key="member.id"
                class="member-row"
                @click="
                  goTo(
                    `/petugas/member/${member.id}`
                  )
                "
              >

                <div class="member-icon">
                  ♙
                </div>

                <div class="member-info">

                  <strong>
                    {{ member.nama_member }}
                  </strong>

                  <span>
                    {{ member.nama_perusahaan }}
                  </span>

                </div>

                <span
                  class="member-status"
                  :class="{
                    lunas:
                      getStatus(member) === 'Lunas',
                    belum:
                      getStatus(member) !== 'Lunas',
                  }"
                >
                  {{ getStatus(member) }}
                </span>

              </button>

            </div>

            <!-- KOSONG -->
            <div
              v-else
              class="member-empty"
            >

              <div>
                ♙
              </div>

              <p>
                Belum ada data member
              </p>

              <button
                @click="
                  goTo('/petugas/member/select')
                "
              >
                Tambah Member
              </button>

            </div>

          </section>

        </div>

        <!-- =================================================
             INFORMATION
        ================================================== -->

        <section class="info-panel">

          <div class="info-left">

            <div class="info-icon">
              !
            </div>

            <div>

              <strong>
                Informasi Member
              </strong>

              <p>
                Total {{ totalMember }} member terdaftar
                dan {{ memberLunas }} member sudah lunas.
                Data pembayaran diambil langsung dari
                database member.
              </p>

            </div>

          </div>

          <button
            @click="
              goTo('/petugas/member/select')
            "
          >
            Cek Member
            <span>→</span>
          </button>

        </section>

      </section>

    </main>

  </div>
</template>

<style scoped>
* {
  box-sizing: border-box;
}

.dashboard {
  min-height: 100vh;
  display: flex;
  background: #f4f6f9;
  color: #172033;
  font-family:
    Inter,
    ui-sans-serif,
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    "Segoe UI",
    sans-serif;
}

/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {
  width: 285px;
  min-height: 100vh;
  background: #111827;
  color: #fff;
  display: flex;
  flex-direction: column;
  padding: 24px 16px;
  transition: 0.25s ease;
  flex-shrink: 0;
}

.sidebar.collapsed {
  width: 82px;
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 4px 10px 26px;
}

.logo-icon {
  width: 42px;
  height: 42px;
  border-radius: 11px;
  background: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  font-weight: 900;
  flex-shrink: 0;
}

.logo-text h1 {
  margin: 0;
  font-size: 17px;
  letter-spacing: 1px;
}

.logo-text span {
  display: block;
  margin-top: 3px;
  color: #94a3b8;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1.5px;
}

.profile {
  background: #1a2333;
  border: 1px solid #263247;
  border-radius: 14px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 11px;
  margin-bottom: 22px;
}

.profile-avatar,
.top-avatar {
  background: #2563eb;
  color: white;
  font-weight: 800;
  display: flex;
  justify-content: center;
  align-items: center;
}

.profile-avatar {
  width: 38px;
  height: 38px;
  border-radius: 10px;
}

.profile strong {
  display: block;
  font-size: 13px;
}

.profile small {
  color: #94a3b8;
  font-size: 11px;
  display: flex;
  gap: 5px;
  align-items: center;
  margin-top: 3px;
}

.online-dot {
  width: 6px;
  height: 6px;
  background: #22c55e;
  border-radius: 50%;
}

.navigation {
  flex: 1;
}

.menu-title {
  color: #64748b;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 1.6px;
  margin: 18px 10px 8px;
}

.nav-item {
  width: 100%;
  border: 0;
  background: transparent;
  color: #aab6c8;
  border-radius: 10px;
  padding: 10px;
  display: flex;
  align-items: center;
  gap: 11px;
  cursor: pointer;
  text-align: left;
  margin-bottom: 3px;
  transition: 0.2s;
}

.nav-item:hover {
  background: #1a2333;
  color: white;
}

.nav-item.active {
  background: #2563eb;
  color: white;
}

.nav-icon {
  width: 26px;
  text-align: center;
  font-size: 16px;
  flex-shrink: 0;
}

.nav-content span {
  display: block;
  font-size: 12px;
  font-weight: 700;
}

.nav-content small {
  display: block;
  font-size: 9px;
  color: #64748b;
  margin-top: 2px;
}

.nav-item:hover .nav-content small,
.nav-item.active .nav-content small {
  color: #bfdbfe;
}

.sidebar-bottom {
  border-top: 1px solid #263247;
  padding-top: 14px;
}

.logout-button {
  width: 100%;
  border: 0;
  background: transparent;
  color: #f87171;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px;
  border-radius: 10px;
  cursor: pointer;
  font-weight: 700;
  font-size: 12px;
}

.logout-button:hover {
  background: #2a1d25;
}

/* =========================================================
   MAIN
========================================================= */

.main {
  flex: 1;
  min-width: 0;
}

.topbar {
  height: 72px;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  padding: 0 34px;
  gap: 20px;
}

.toggle-button {
  width: 38px;
  height: 38px;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 9px;
  cursor: pointer;
  font-size: 18px;
  color: #475569;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}

.breadcrumb span {
  color: #94a3b8;
  font-weight: 700;
}

.breadcrumb b {
  color: #cbd5e1;
}

.breadcrumb strong {
  color: #1e293b;
}

.top-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 18px;
}

.date {
  color: #64748b;
  font-size: 11px;
  font-weight: 700;
}

.date span {
  color: #22c55e;
  margin-right: 5px;
}

.top-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  font-size: 12px;
}

/* =========================================================
   CONTENT
========================================================= */

.content {
  max-width: 1450px;
  margin: 0 auto;
  padding: 34px;
}

.welcome {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
}

.eyebrow {
  color: #2563eb;
  font-size: 10px;
  font-weight: 900;
  letter-spacing: 1.7px;
  margin: 0 0 8px;
}

.welcome h2 {
  margin: 0;
  font-size: 31px;
  letter-spacing: -1px;
}

.welcome h2 span {
  color: #2563eb;
}

.welcome-text {
  color: #64748b;
  margin: 8px 0 0;
  font-size: 13px;
}

.system-status {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 13px 17px;
  border: 1px solid #dbe4ef;
  background: white;
  border-radius: 12px;
}

.status-circle {
  width: 9px;
  height: 9px;
  background: #22c55e;
  border-radius: 50%;
}

.system-status strong {
  display: block;
  font-size: 11px;
}

.system-status small {
  display: block;
  color: #94a3b8;
  font-size: 9px;
  margin-top: 2px;
}

/* =========================================================
   STATS
========================================================= */

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 22px;
}

.stat-card {
  width: 100%;
  text-align: left;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 15px;
  padding: 18px;
  box-shadow: 0 2px 5px rgba(15, 23, 42, 0.03);
  font-family: inherit;
}

button.stat-card {
  cursor: pointer;
}

.stat-card.clickable:hover {
  transform: translateY(-2px);
  border-color: #bfdbfe;
  box-shadow: 0 5px 15px rgba(15, 23, 42, 0.08);
}

.stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.stat-label {
  color: #94a3b8;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: 1px;
}

.stat-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 900;
}

.blue .stat-icon {
  background: #eff6ff;
  color: #2563eb;
}

.green .stat-icon {
  background: #ecfdf5;
  color: #059669;
}

.orange .stat-icon {
  background: #fff7ed;
  color: #ea580c;
}

.purple .stat-icon {
  background: #f5f3ff;
  color: #7c3aed;
}

.stat-number {
  display: block;
  margin-top: 16px;
  font-size: 27px;
  letter-spacing: -1px;
  color: #172033;
}

.money {
  font-size: 21px;
}

.stat-card p {
  color: #94a3b8;
  font-size: 10px;
  margin: 4px 0 0;
}

/* =========================================================
   GRID
========================================================= */

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.panel {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 16px;
  padding: 22px;
}

.panel-label {
  color: #94a3b8;
  font-size: 9px;
  font-weight: 900;
  letter-spacing: 1.4px;
  margin: 0 0 5px;
}

.panel-header h3 {
  margin: 0;
  font-size: 18px;
}

.activity-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.lihat-semua {
  border: 0;
  background: transparent;
  color: #2563eb;
  font-size: 10px;
  font-weight: 800;
  cursor: pointer;
}

/* =========================================================
   QUICK ACTION
========================================================= */

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 18px;
}

.quick-card {
  width: 100%;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  cursor: pointer;
  text-align: left;
  transition: 0.2s;
}

.quick-card:hover {
  transform: translateX(3px);
  border-color: #bfdbfe;
  background: #f8fbff;
}

.quick-icon {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
}

.keluar .quick-icon {
  background: #ecfdf5;
  color: #059669;
}

.transaksi .quick-icon {
  background: #eff6ff;
  color: #2563eb;
}

.member .quick-icon {
  background: #f5f3ff;
  color: #7c3aed;
}

.quick-card strong {
  display: block;
  color: #172033;
  font-size: 12px;
}

.quick-card span {
  display: block;
  color: #94a3b8;
  font-size: 10px;
  margin-top: 3px;
}

.quick-card b {
  margin-left: auto;
  color: #94a3b8;
}

/* =========================================================
   MEMBER
========================================================= */

.member-list {
  margin-top: 15px;
}

.member-row {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 11px 0;
  border: 0;
  border-bottom: 1px solid #f1f5f9;
  background: transparent;
  cursor: pointer;
  text-align: left;
}

.member-row:last-child {
  border-bottom: 0;
}

.member-row:hover {
  background: #f8fafc;
}

.member-icon {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  background: #f5f3ff;
  color: #7c3aed;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.member-info {
  flex: 1;
  min-width: 0;
}

.member-info strong {
  display: block;
  color: #172033;
  font-size: 11px;
}

.member-info span {
  display: block;
  color: #94a3b8;
  font-size: 9px;
  margin-top: 2px;
}

.member-status {
  padding: 5px 9px;
  border-radius: 20px;
  font-size: 8px;
  font-weight: 800;
}

.member-status.lunas {
  background: #dcfce7;
  color: #059669;
}

.member-status.belum {
  background: #fef3c7;
  color: #d97706;
}

.member-loading,
.member-error,
.member-empty {
  min-height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  text-align: center;
  color: #94a3b8;
  font-size: 11px;
}

.member-error {
  color: #ef4444;
}

.member-empty > div {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  background: #f5f3ff;
  color: #7c3aed;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.member-empty p {
  margin: 10px 0;
}

.member-empty button {
  border: 0;
  background: #2563eb;
  color: white;
  border-radius: 8px;
  padding: 8px 12px;
  font-size: 9px;
  font-weight: 700;
  cursor: pointer;
}

/* =========================================================
   INFORMATION
========================================================= */

.info-panel {
  margin-top: 20px;
  background: #172033;
  color: white;
  border-radius: 15px;
  padding: 18px 21px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.info-left {
  display: flex;
  align-items: center;
  gap: 13px;
}

.info-icon {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: #263247;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fbbf24;
  font-weight: 900;
}

.info-left strong {
  display: block;
  font-size: 12px;
}

.info-left p {
  color: #94a3b8;
  font-size: 10px;
  margin: 4px 0 0;
}

.info-panel button {
  border: 1px solid #334155;
  background: #263247;
  color: white;
  border-radius: 9px;
  padding: 9px 13px;
  cursor: pointer;
  font-size: 10px;
  font-weight: 800;
}

.info-panel button span {
  margin-left: 7px;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {
  .stats {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 750px) {
  .sidebar {
    width: 82px;
  }

  .sidebar .logo-text,
  .sidebar .profile,
  .sidebar .menu-title,
  .sidebar .nav-content,
  .sidebar .logout-button span:last-child {
    display: none;
  }

  .content {
    padding: 20px;
  }

  .welcome {
    align-items: flex-start;
    flex-direction: column;
    gap: 15px;
  }

  .topbar {
    padding: 0 18px;
  }

  .breadcrumb {
    display: none;
  }

  .stats {
    grid-template-columns: 1fr;
  }

  .info-panel {
    align-items: flex-start;
    flex-direction: column;
    gap: 15px;
  }
}
</style>