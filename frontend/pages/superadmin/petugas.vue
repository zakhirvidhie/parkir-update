<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-800 text-white">
    <div class="flex min-h-screen">

      <!-- SIDEBAR -->
      <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col fixed inset-y-0 left-0 z-30">
        <div class="px-6 py-5 border-b border-slate-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-purple-700 flex items-center justify-center text-white font-black text-lg">S</div>
            <div>
              <div class="font-black text-white text-sm">PARKIR</div>
              <div class="text-xs text-violet-400 font-semibold">Super Admin</div>
            </div>
          </div>
        </div>
        <nav class="flex-1 px-3 py-4 space-y-1">
          <button @click="navigateTo('/superadmin')"
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors cursor-pointer text-slate-400 hover:bg-slate-800 hover:text-white">
            🏠 Beranda
          </button>
          <button @click="navigateTo('/superadmin/petugas')"
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-semibold transition-colors cursor-pointer bg-violet-600/20 text-violet-300 border border-violet-600/30">
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

      <!-- MAIN -->
      <main class="ml-64 flex-1 p-6 space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-black text-white">👮 Kelola Petugas</h1>
            <p class="text-slate-400 text-sm mt-1">Tambah, edit, dan kelola akun petugas parkir</p>
          </div>
          <button @click="openTambah"
            class="flex items-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-500 rounded-xl text-sm font-bold transition-colors cursor-pointer">
            ➕ Tambah Petugas
          </button>
        </div>

        <!-- Error -->
        <div v-if="errorMsg" class="bg-red-900/30 border border-red-700/50 text-red-300 rounded-xl px-4 py-3 text-sm">
          ⚠️ {{ errorMsg }}
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-slate-900 border border-slate-700 rounded-2xl p-4 text-center">
            <p class="text-3xl font-black text-violet-400">{{ userList.length }}</p>
            <p class="text-xs text-slate-400 mt-1">Total User</p>
          </div>
          <div class="bg-slate-900 border border-slate-700 rounded-2xl p-4 text-center">
            <p class="text-3xl font-black text-cyan-400">{{ jumlahPetugas }}</p>
            <p class="text-xs text-slate-400 mt-1">Petugas</p>
          </div>
          <div class="bg-slate-900 border border-slate-700 rounded-2xl p-4 text-center">
            <p class="text-3xl font-black text-yellow-400">{{ jumlahSuperadmin }}</p>
            <p class="text-xs text-slate-400 mt-1">Super Admin</p>
          </div>
        </div>

        <!-- Search & Filter -->
        <div class="flex flex-wrap gap-3 items-center">
          <div class="relative flex-1 min-w-48">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm">🔍</span>
            <input v-model="search" type="text" placeholder="Cari nama atau email..."
              class="w-full pl-9 pr-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-violet-500" />
          </div>
          <div class="flex gap-1 bg-slate-900 border border-slate-700 rounded-xl p-1">
            <button v-for="f in roleFilters" :key="f.key" @click="filterRole = f.key"
              :class="['px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors cursor-pointer',
                filterRole === f.key ? 'bg-violet-600 text-white' : 'text-slate-400 hover:text-white']">
              {{ f.label }}
            </button>
          </div>
          <button @click="fetchUsers" :disabled="loading"
            class="px-4 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm transition-colors cursor-pointer">
            <span :class="loading ? 'animate-spin' : ''">🔄</span>
          </button>
        </div>

        <!-- Table -->
        <div class="bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">
          <!-- Loading skeleton -->
          <div v-if="loading && userList.length === 0" class="p-8 space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 bg-slate-800 rounded-xl animate-pulse"></div>
          </div>
          <!-- Empty -->
          <div v-else-if="filteredUsers.length === 0" class="text-center py-16 text-slate-500">
            <div class="text-4xl mb-2">👮</div>
            <p>Tidak ada petugas ditemukan</p>
          </div>
          <!-- Data -->
          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-slate-800/80 text-slate-400 text-xs uppercase tracking-wider">
                <tr>
                  <th class="text-left px-5 py-3">No</th>
                  <th class="text-left px-5 py-3">Nama</th>
                  <th class="text-left px-5 py-3">Email</th>
                  <th class="text-left px-5 py-3">Username</th>
                  <th class="text-left px-5 py-3">No. Telp</th>
                  <th class="text-left px-5 py-3">Role</th>
                  <th class="text-left px-5 py-3">Bergabung</th>
                  <th class="text-center px-5 py-3">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800">
                <tr v-for="(u, i) in filteredUsers" :key="u.id" class="hover:bg-slate-800/40 transition-colors">
                  <td class="px-5 py-3 text-slate-400">{{ i + 1 }}</td>
                  <td class="px-5 py-3 font-semibold text-white">{{ u.name }}</td>
                  <td class="px-5 py-3 text-slate-300 text-xs">{{ u.email }}</td>
                  <td class="px-5 py-3 text-slate-400 text-xs font-mono">{{ u.username || '-' }}</td>
                  <td class="px-5 py-3 text-slate-400 text-xs">{{ u.no_telp || '-' }}</td>
                  <td class="px-5 py-3">
                    <span :class="[
                      'inline-block px-2.5 py-0.5 rounded-full text-xs font-bold',
                      u.role === 'superadmin'
                        ? 'bg-yellow-900/50 text-yellow-300 border border-yellow-700/50'
                        : 'bg-violet-900/50 text-violet-300 border border-violet-700/50'
                    ]">
                      {{ u.role === 'superadmin' ? '⭐ Superadmin' : '👮 Petugas' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-slate-400 text-xs">{{ formatDate(u.created_at) }}</td>
                  <td class="px-5 py-3 text-center">
                    <div class="flex items-center justify-center gap-2">
                      <button @click="openEdit(u)"
                        class="p-1.5 bg-cyan-900/40 hover:bg-cyan-700/60 border border-cyan-700/40 text-cyan-400 rounded-lg transition-colors cursor-pointer text-xs"
                        title="Edit">
                        ✏️
                      </button>
                      <button @click="openHapus(u)"
                        :disabled="u.role === 'superadmin'"
                        class="p-1.5 bg-red-900/40 hover:bg-red-700/60 border border-red-700/40 text-red-400 rounded-lg transition-colors cursor-pointer text-xs disabled:opacity-30 disabled:cursor-not-allowed"
                        title="Hapus">
                        🗑️
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <p class="text-xs text-slate-500 px-5 py-2">Menampilkan {{ filteredUsers.length }} dari {{ userList.length }} user</p>
          </div>
        </div>

      </main>
    </div>

    <!-- ===== MODAL TAMBAH / EDIT ===== -->
    <Transition name="fade">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal"></div>
        <div class="relative bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md shadow-2xl">

          <!-- Modal Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
            <h3 class="text-base font-bold text-white">
              {{ modeEdit ? '✏️ Edit Petugas' : '➕ Tambah Petugas Baru' }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-white transition-colors cursor-pointer text-lg">✕</button>
          </div>

          <!-- Modal Body -->
          <form @submit.prevent="submitForm" class="px-6 py-5 space-y-4">

            <!-- Error form -->
            <div v-if="formError" class="bg-red-900/30 border border-red-700/50 text-red-300 rounded-xl px-4 py-3 text-sm">
              ⚠️ {{ formError }}
            </div>

            <!-- Nama -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">Nama Lengkap *</label>
              <input v-model="form.name" type="text" placeholder="Nama lengkap petugas" required
                class="w-full px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:border-violet-500 placeholder:text-slate-500" />
            </div>

            <!-- Email -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">Email *</label>
              <input v-model="form.email" type="email" placeholder="email@contoh.com" required
                class="w-full px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:border-violet-500 placeholder:text-slate-500" />
            </div>

            <!-- Username -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">Username</label>
              <input v-model="form.username" type="text" placeholder="username (opsional)"
                class="w-full px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:border-violet-500 placeholder:text-slate-500" />
            </div>

            <!-- No Telp -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">No. Telepon</label>
              <input v-model="form.no_telp" type="text" placeholder="08xxxxxxxxxx (opsional)"
                class="w-full px-4 py-2.5 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:border-violet-500 placeholder:text-slate-500" />
            </div>

            <!-- Password -->
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1.5">
                Password {{ modeEdit ? '(kosongkan jika tidak diubah)' : '*' }}
              </label>
              <div class="relative">
                <input v-model="form.password" :type="showPass ? 'text' : 'password'"
                  :placeholder="modeEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter'"
                  :required="!modeEdit"
                  class="w-full px-4 py-2.5 pr-10 bg-slate-800 border border-slate-600 rounded-xl text-white text-sm focus:outline-none focus:border-violet-500 placeholder:text-slate-500" />
                <button type="button" @click="showPass = !showPass"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors cursor-pointer text-sm">
                  {{ showPass ? '🙈' : '👁️' }}
                </button>
              </div>
            </div>



            <!-- Actions -->
            <div class="flex gap-3 pt-2">
              <button type="button" @click="closeModal"
                class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-medium transition-colors cursor-pointer">
                Batal
              </button>
              <button type="submit" :disabled="formLoading"
                class="flex-1 py-2.5 bg-violet-600 hover:bg-violet-500 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl text-sm font-bold transition-colors cursor-pointer">
                {{ formLoading ? '⏳ Menyimpan...' : modeEdit ? '✅ Simpan Perubahan' : '✅ Tambah Petugas' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ===== MODAL HAPUS ===== -->
    <Transition name="fade">
      <div v-if="showHapusModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showHapusModal = false"></div>
        <div class="relative bg-slate-900 border border-red-800/50 rounded-2xl w-full max-w-sm shadow-2xl p-6 text-center">
          <div class="text-5xl mb-3">🗑️</div>
          <h3 class="text-lg font-bold text-white mb-2">Hapus Petugas?</h3>
          <p class="text-slate-400 text-sm mb-1">Anda akan menghapus akun:</p>
          <p class="text-white font-semibold mb-1">{{ selectedUser?.name }}</p>
          <p class="text-slate-500 text-xs mb-5">{{ selectedUser?.email }}</p>
          <p class="text-red-300 text-xs mb-5 bg-red-900/20 border border-red-800/40 rounded-lg px-3 py-2">
            ⚠️ Tindakan ini tidak bisa dibatalkan!
          </p>
          <div class="flex gap-3">
            <button @click="showHapusModal = false"
              class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 border border-slate-700 rounded-xl text-sm font-medium transition-colors cursor-pointer">
              Batal
            </button>
            <button @click="hapusUser" :disabled="formLoading"
              class="flex-1 py-2.5 bg-red-600 hover:bg-red-500 disabled:opacity-50 rounded-xl text-sm font-bold transition-colors cursor-pointer">
              {{ formLoading ? '⏳...' : '🗑️ Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

const config  = useRuntimeConfig()
const apiBase = String(config.public.apiBase || 'http://127.0.0.1:8000/api').replace(/\/+$/, '')

// ── Auth ───────────────────────────────────────────────────
const userRaw  = process.client ? localStorage.getItem('user') : null
const userData = userRaw ? JSON.parse(userRaw) : {}
const userName    = computed(() => userData.name || userData.email || 'Admin')
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
interface UserItem {
  id: number
  name: string
  email: string
  username: string
  no_telp: string
  role: 'superadmin' | 'petugas'
  created_at: string
}

const userList      = ref<UserItem[]>([])
const loading       = ref(false)
const errorMsg      = ref('')
const search        = ref('')
const filterRole    = ref<'semua' | 'petugas' | 'superadmin'>('semua')
const showModal     = ref(false)
const showHapusModal= ref(false)
const modeEdit      = ref(false)
const formLoading   = ref(false)
const formError     = ref('')
const showPass      = ref(false)
const selectedUser  = ref<UserItem | null>(null)

const form = ref({
  name: '', email: '', username: '', no_telp: '', password: '', role: 'petugas' as 'petugas' | 'superadmin',
})

const roleFilters = [
  { key: 'semua',      label: 'Semua' },
  { key: 'petugas',    label: '👮 Petugas' },
  { key: 'superadmin', label: '⭐ Superadmin' },
]

// ── Computed ───────────────────────────────────────────────
const jumlahPetugas    = computed(() => userList.value.filter(u => u.role === 'petugas').length)
const jumlahSuperadmin = computed(() => userList.value.filter(u => u.role === 'superadmin').length)

const filteredUsers = computed(() => {
  let list = userList.value
  if (filterRole.value !== 'semua') list = list.filter(u => u.role === filterRole.value)
  const q = search.value.trim().toLowerCase()
  if (q) list = list.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q))
  return list
})

// ── Helpers ────────────────────────────────────────────────
const formatDate = (dt: string) => {
  if (!dt) return '-'
  return new Date(dt).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

// ── Fetch ──────────────────────────────────────────────────
const fetchUsers = async () => {
  loading.value  = true
  errorMsg.value = ''
  try {
    const res: any = await $fetch(`${apiBase}/users`, { headers: { Accept: 'application/json' } })
    userList.value = res?.data ?? res ?? []
  } catch (e: any) {
    errorMsg.value = e?.data?.message ?? 'Gagal memuat data petugas'
  } finally {
    loading.value = false
  }
}

onMounted(fetchUsers)

// ── Modal Tambah ───────────────────────────────────────────
const openTambah = () => {
  modeEdit.value = false
  formError.value = ''
  showPass.value  = false
  form.value = { name: '', email: '', username: '', no_telp: '', password: '', role: 'petugas' }
  showModal.value = true
}

// ── Modal Edit ─────────────────────────────────────────────
const openEdit = (u: UserItem) => {
  modeEdit.value  = true
  formError.value = ''
  showPass.value  = false
  selectedUser.value = u
  form.value = {
    name: u.name, email: u.email, username: u.username || '',
    no_telp: u.no_telp || '', password: '', role: u.role,
  }
  showModal.value = true
}

const closeModal = () => { showModal.value = false; formError.value = '' }

// ── Submit Form ────────────────────────────────────────────
const submitForm = async () => {
  formLoading.value = true
  formError.value   = ''
  try {
    if (modeEdit.value && selectedUser.value) {
      // Update
      const body: any = {
        name: form.value.name,
        email: form.value.email,
        username: form.value.username,
        no_telp: form.value.no_telp,
        role: form.value.role,
      }
      if (form.value.password) body.password = form.value.password
      await $fetch(`${apiBase}/users/${selectedUser.value.id}`, {
        method: 'PUT',
        body,
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })
    } else {
      // Tambah
      await $fetch(`${apiBase}/users`, {
        method: 'POST',
        body: {
          name: form.value.name, email: form.value.email,
          username: form.value.username, no_telp: form.value.no_telp,
          password: form.value.password, role: form.value.role,
        },
        headers: { Accept: 'application/json' },
      })
    }
    showModal.value = false
    await fetchUsers()
  } catch (e: any) {
    const msg = e?.data?.message
    const errs = e?.data?.errors
    if (errs) {
      formError.value = Object.values(errs).flat().join(' | ')
    } else {
      formError.value = msg ?? 'Gagal menyimpan data'
    }
  } finally {
    formLoading.value = false
  }
}

// ── Hapus ──────────────────────────────────────────────────
const openHapus = (u: UserItem) => {
  selectedUser.value = u
  showHapusModal.value = true
}

const hapusUser = async () => {
  if (!selectedUser.value) return
  formLoading.value = true
  try {
    await $fetch(`${apiBase}/users/${selectedUser.value.id}`, {
      method: 'DELETE', headers: { Accept: 'application/json' },
    })
    showHapusModal.value = false
    await fetchUsers()
  } catch (e: any) {
    errorMsg.value = e?.data?.message ?? 'Gagal menghapus petugas'
    showHapusModal.value = false
  } finally {
    formLoading.value = false
  }
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>

