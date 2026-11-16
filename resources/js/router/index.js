import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

import LoginView from '@/views/LoginView.vue';
import DashboardView from '@/views/DashboardView.vue';
import AssetDirectoryView from '@/views/AssetDirectoryView.vue';
import AssetDetailView from '@/views/AssetDetailView.vue';
import AssetTransferView from '@/views/AssetTransferView.vue';
import KsoReconciliationView from '@/views/KsoReconciliationView.vue';
import NetworkMapView from '@/views/NetworkMapView.vue';
import MaintenanceView from '@/views/MaintenanceView.vue';
import RoomsView from '@/views/RoomsView.vue';
import ReportsView from '@/views/ReportsView.vue';
import RecycleBinView from '@/views/RecycleBinView.vue';
import SettingsView from '@/views/SettingsView.vue';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guest: true, title: 'Login Sistem' },
    },
    {
        path: '/',
        redirect: '/dashboard',
    },
    {
        path: '/dashboard',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true, title: 'Dashboard Analytics', subtitle: 'Monitoring Real-Time Inventaris & Kondisi Perangkat IT RSUD' },
    },
    {
        path: '/assets',
        name: 'assets',
        component: AssetDirectoryView,
        meta: { requiresAuth: true, title: 'Asset Directory', subtitle: 'Manajemen Inventaris Seluruh Perangkat Teknologi Informasi' },
    },
    {
        path: '/assets/:id',
        name: 'asset-detail',
        component: AssetDetailView,
        meta: { requiresAuth: true, title: 'Detail Aset & QR Code', subtitle: 'Informasi Lengkap Perangkat, Riwayat Pemindahan & Pemeliharaan' },
    },
    {
        path: '/transfers',
        name: 'transfers',
        component: AssetTransferView,
        meta: { requiresAuth: true, title: 'Asset Transfer / Pemindahan Aset', subtitle: 'Manajemen Mutasi & Pemindahan Lokasi Perangkat IT Rumah Sakit' },
    },
    {
        path: '/kso-reconciliation',
        name: 'kso-reconciliation',
        component: KsoReconciliationView,
        meta: { requiresAuth: true, title: 'KSO Asset Reconciliation', subtitle: 'Pencocokan & Rekonsiliasi Inventaris Aset KSO Rumah Sakit terhadap Data Mitra KSO' },
    },
    {
        path: '/scan/:code',
        name: 'qr-scan',
        redirect: (to) => `/assets/${to.params.code}`,
        meta: { requiresAuth: true },
    },
    {
        path: '/network-map',
        name: 'network-map',
        component: NetworkMapView,
        meta: { requiresAuth: true, title: 'IP Network Map', subtitle: 'Visualisasi Topologi Jaringan, Alokasi IP Address & Status Koneksi' },
    },
    {
        path: '/maintenance',
        name: 'maintenance',
        component: MaintenanceView,
        meta: { requiresAuth: true, title: 'Maintenance Radar', subtitle: 'Pencatatan Perbaikan, Upgrade & Pemeliharaan Perangkat IT' },
    },
    {
        path: '/rooms',
        name: 'rooms',
        component: RoomsView,
        meta: { requiresAuth: true, title: 'Data Ruangan', subtitle: 'Master Lokasi Instalasi, Gedung & Distribusi Penempatan Aset' },
    },
    {
        path: '/reports',
        name: 'reports',
        component: ReportsView,
        meta: { requiresAuth: true, title: 'Laporan & Rekapitulasi', subtitle: 'Rekap Inventaris Berdasarkan Kategori, Kondisi, Kepemilikan & Ruangan' },
    },
    {
        path: '/recycle-bin',
        name: 'recycle-bin',
        component: RecycleBinView,
        meta: { requiresAuth: true, title: 'Recycle Bin', subtitle: 'Penyimpanan Sementara Aset Terhapus (Pulihkan atau Hapus Permanen)' },
    },
    {
        path: '/settings',
        name: 'settings',
        component: SettingsView,
        meta: { requiresAuth: true, title: 'Pengaturan Sistem', subtitle: 'Manajemen Pengguna, Profil Akun, Kepemilikan KSO & Backup Database MySQL' },
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/dashboard',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to, from, next) => {
    const auth = useAuthStore();
    if (!auth.initialized) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return next({
            name: 'login',
            query: to.fullPath !== '/dashboard' ? { redirect: to.fullPath } : {},
        });
    }

    if (to.meta.guest && auth.isAuthenticated) {
        const redirectPath = to.query.redirect || '/dashboard';
        return next(redirectPath);
    }

    next();
});

export default router;
