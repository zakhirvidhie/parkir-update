export default defineNuxtRouteMiddleware((to, from) => {
  // Skip di server-side (SSR off, tapi jaga-jaga)
  if (import.meta.server) return;

  const role = localStorage.getItem('role');

  // Halaman publik: /login, /lupa-password, dan /user/* (kiosk)
  const isPublic = to.path === '/login' || to.path === '/lupa-password' || to.path.startsWith('/user');

  // Belum login → redirect ke /login
  if (!role && !isPublic) {
    return navigateTo('/login');
  }

  // Sudah login → jangan ke /login lagi, redirect ke halaman utama sesuai role
  if (role && to.path === '/login') {
    return navigateTo(role === 'superadmin' ? '/superadmin' : '/');
  }

  // Petugas TIDAK boleh ke /superadmin/*
  if (role === 'petugas' && to.path.startsWith('/superadmin')) {
    return navigateTo('/');
  }
});
