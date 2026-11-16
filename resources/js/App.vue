<template>
    <div class="min-h-screen bg-navy-950 text-slate-100 flex flex-col">
        <!-- Global Toast Notifications -->
        <div class="fixed top-4 right-4 z-[100] flex flex-col gap-2.5 max-w-sm w-full pointer-events-none no-print">
            <transition-group
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-x-6 opacity-0"
                enter-to-class="translate-x-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-6 opacity-0"
            >
                <div
                    v-for="toast in auth.toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-3 px-4 py-3.5 rounded-xl border shadow-2xl backdrop-blur-xl"
                    :class="{
                        'bg-emerald-950/90 border-emerald-500/40 text-emerald-100': toast.type === 'success',
                        'bg-rose-950/90 border-rose-500/40 text-rose-100': toast.type === 'error',
                        'bg-cyan-950/90 border-cyan-500/40 text-cyan-100': toast.type === 'info',
                        'bg-amber-950/90 border-amber-500/40 text-amber-100': toast.type === 'warning',
                    }"
                >
                    <CheckCircle2 v-if="toast.type === 'success'" class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                    <AlertTriangle v-else-if="toast.type === 'error' || toast.type === 'warning'" class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
                    <Info v-else class="w-5 h-5 text-cyan-400 shrink-0 mt-0.5" />
                    <div class="text-sm font-medium leading-snug flex-1">{{ toast.message }}</div>
                    <button @click="auth.removeToast(toast.id)" class="text-slate-400 hover:text-white">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </transition-group>
        </div>

        <!-- Guest View (Login) -->
        <template v-if="!auth.isAuthenticated || route.meta.guest">
            <router-view />
        </template>

        <!-- Authenticated SaaS Layout -->
        <div v-else class="flex min-h-screen relative">
            <!-- Mobile Sidebar Backdrop -->
            <div
                v-if="mobileMenuOpen"
                @click="mobileMenuOpen = false"
                class="fixed inset-0 bg-black/70 backdrop-blur-sm z-40 lg:hidden no-print"
            ></div>

            <!-- Sidebar Navigation -->
            <aside
                class="fixed lg:sticky top-0 left-0 z-50 h-screen w-72 bg-navy-900/95 border-r border-slate-800/80 flex flex-col transition-transform duration-300 ease-in-out no-print"
                :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <!-- Brand Header -->
                <div class="px-6 py-5 border-b border-slate-800/80 flex items-center justify-between">
                    <router-link to="/dashboard" class="flex items-center gap-3.5 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-cyan-500/25 group-hover:scale-105 transition">
                            <Server class="w-5 h-5 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-extrabold text-base tracking-tight text-white">SIM-ASET</span>
                                <span class="text-[10px] font-mono font-bold uppercase px-1.5 py-0.5 rounded bg-cyan-500/15 text-cyan-400 border border-cyan-500/30">IT RSUD</span>
                            </div>
                            <p class="text-[11px] text-slate-400 font-medium">IT Asset Management System</p>
                        </div>
                    </router-link>
                    <button @click="mobileMenuOpen = false" class="lg:hidden text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Navigation Menu (Exact 8 Menu Structure from Spec Section 15) -->
                <div class="flex-1 overflow-y-auto px-3.5 py-5 space-y-1.5">
                    <div class="px-3 pb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Menu Utama Inventaris
                    </div>

                    <router-link
                        v-for="item in navItems"
                        :key="item.to"
                        :to="item.to"
                        @click="mobileMenuOpen = false"
                        class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 group"
                        :class="isActiveRoute(item.to)
                            ? 'bg-gradient-to-r from-cyan-500/20 to-blue-600/10 text-cyan-300 border border-cyan-500/30 shadow-lg shadow-cyan-950/50'
                            : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60 border border-transparent'"
                    >
                        <div class="flex items-center gap-3">
                            <component
                                :is="item.icon"
                                class="w-4 h-4 transition-colors"
                                :class="isActiveRoute(item.to) ? 'text-cyan-400' : item.colorClass"
                            />
                            <span>{{ item.label }}</span>
                        </div>
                        <span
                            v-if="item.badge"
                            class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded-full"
                            :class="item.badgeClass"
                        >
                            {{ item.badge }}
                        </span>
                    </router-link>
                </div>

                <!-- Quick QR Code Lookup Box -->
                <div class="px-4 py-3 mx-3 mb-3 rounded-xl bg-navy-950/90 border border-slate-800/90">
                    <label class="block text-[11px] font-semibold text-slate-400 mb-1.5 flex items-center gap-1.5">
                        <QrCode class="w-3.5 h-3.5 text-cyan-400" />
                        Scan / Cari Kode QR Aset
                    </label>
                    <form @submit.prevent="handleQuickCodeLookup" class="flex gap-1.5">
                        <input
                            v-model="quickCode"
                            type="text"
                            placeholder="Contoh: AST-0001"
                            class="w-full bg-navy-900 border border-slate-700/80 rounded-lg px-2.5 py-1.5 text-xs font-mono text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
                        />
                        <button
                            type="submit"
                            class="px-2.5 py-1.5 rounded-lg bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-300 border border-cyan-500/30 text-xs font-semibold transition shrink-0"
                        >
                            Buka
                        </button>
                    </form>
                </div>

                <!-- User Profile Footer -->
                <div class="p-4 border-t border-slate-800/80 bg-navy-950/50">
                    <div class="flex items-center justify-between gap-2">
                        <router-link to="/settings" class="flex items-center gap-3 min-w-0 group">
                            <div
                                class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm shrink-0 border"
                                :class="auth.isAdmin
                                    ? 'bg-purple-500/20 text-purple-300 border-purple-500/40'
                                    : 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40'"
                            >
                                {{ userInitials }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-white truncate group-hover:text-cyan-300 transition">
                                    {{ auth.user?.name }}
                                </p>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span
                                        class="inline-block w-1.5 h-1.5 rounded-full"
                                        :class="auth.isAdmin ? 'bg-purple-400' : 'bg-emerald-400'"
                                    ></span>
                                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                        {{ auth.isAdmin ? 'Administrator' : 'Petugas IT' }}
                                    </span>
                                </div>
                            </div>
                        </router-link>
                        <button
                            @click="handleLogout"
                            title="Logout Sistem"
                            class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 border border-transparent hover:border-rose-500/20 transition"
                        >
                            <LogOut class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Top Header Bar -->
                <header class="sticky top-0 z-30 bg-navy-950/90 backdrop-blur-xl border-b border-slate-800/80 px-3.5 sm:px-6 lg:px-8 py-3 no-print">
                    <div class="flex items-center justify-between gap-2.5 sm:gap-4">
                        <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                            <button
                                @click="mobileMenuOpen = true"
                                aria-label="Buka Menu Navigasi"
                                class="lg:hidden p-2 rounded-xl bg-navy-900 border border-slate-800 text-slate-300 hover:text-white active:scale-95 transition shrink-0"
                            >
                                <Menu class="w-5 h-5" />
                            </button>
                            <div class="min-w-0">
                                <h1 class="text-sm sm:text-xl font-extrabold text-white tracking-tight truncate">
                                    {{ route.meta.title || 'Dashboard Analytics' }}
                                </h1>
                                <p class="text-[11px] sm:text-xs text-slate-400 hidden sm:block truncate">
                                    {{ route.meta.subtitle || 'Sistem Inventarisasi dan Monitoring Aset Teknologi Informasi Rumah Sakit' }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                            <!-- Database Live Indicator -->
                            <div class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-xs text-emerald-300 font-medium">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span>MySQL Connected</span>
                            </div>

                            <!-- Quick Action: Mutasi Aset -->
                            <router-link
                                to="/transfers"
                                class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl bg-teal-500/15 hover:bg-teal-500/25 border border-teal-500/30 text-teal-300 font-bold text-xs transition"
                                title="Pemindahan Aset"
                            >
                                <ArrowLeftRight class="w-4 h-4" />
                                <span class="hidden md:inline">Mutasi Aset</span>
                            </router-link>

                            <!-- Quick Action: Tambah Aset -->
                            <router-link
                                to="/assets?action=create"
                                class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-bold text-xs shadow-lg shadow-cyan-500/20 transition"
                            >
                                <Plus class="w-4 h-4 stroke-[2.5]" />
                                <span class="hidden xs:inline sm:inline">Tambah Aset</span>
                            </router-link>
                        </div>
                    </div>
                </header>

                <!-- Page Router View -->
                <main class="flex-1 p-3.5 sm:p-6 lg:p-8 pb-24 lg:pb-8 max-w-[1600px] w-full mx-auto overflow-x-hidden">
                    <router-view />
                </main>

                <!-- Footer -->
                <footer class="px-4 sm:px-6 py-4 pb-24 lg:pb-4 border-t border-slate-800/60 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-2 text-center sm:text-left no-print">
                    <div>
                        <span class="font-semibold text-slate-400">SIM-ASET IT RSUD</span> &mdash; Sistem Inventarisasi &amp; Monitoring Aset Teknologi Informasi
                    </div>
                    <div class="flex items-center gap-4 font-mono text-[11px]">
                        <span>Laravel 11 + Vue 3 + MySQL</span>
                        <span class="text-cyan-400">v2.0 Production</span>
                    </div>
                </footer>

                <!-- Mobile Bottom Navigation Bar (Smartphone & Tablet Ergonomic Access) -->
                <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-navy-900/95 backdrop-blur-xl border-t border-slate-800/90 px-2 py-1.5 pb-safe no-print shadow-2xl">
                    <div class="grid grid-cols-5 gap-1 max-w-md mx-auto">
                        <router-link
                            to="/dashboard"
                            class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-bold transition"
                            :class="isActiveRoute('/dashboard') ? 'text-cyan-400 bg-cyan-500/10' : 'text-slate-400 hover:text-slate-200'"
                        >
                            <LayoutDashboard class="w-4 h-4 mb-0.5" />
                            <span>Dashboard</span>
                        </router-link>

                        <router-link
                            to="/assets"
                            class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-bold transition"
                            :class="isActiveRoute('/assets') ? 'text-emerald-400 bg-emerald-500/10' : 'text-slate-400 hover:text-slate-200'"
                        >
                            <Boxes class="w-4 h-4 mb-0.5" />
                            <span>Data Aset</span>
                        </router-link>

                        <router-link
                            to="/transfers"
                            class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-bold transition"
                            :class="isActiveRoute('/transfers') ? 'text-teal-400 bg-teal-500/10' : 'text-slate-400 hover:text-slate-200'"
                        >
                            <ArrowLeftRight class="w-4 h-4 mb-0.5" />
                            <span>Transfer</span>
                        </router-link>

                        <router-link
                            to="/maintenance"
                            class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-bold transition"
                            :class="isActiveRoute('/maintenance') ? 'text-amber-400 bg-amber-500/10' : 'text-slate-400 hover:text-slate-200'"
                        >
                            <Wrench class="w-4 h-4 mb-0.5" />
                            <span>Servis</span>
                        </router-link>

                        <button
                            type="button"
                            @click="mobileMenuOpen = true"
                            class="flex flex-col items-center justify-center py-1.5 rounded-xl text-[10px] font-bold text-slate-400 hover:text-white transition"
                        >
                            <Menu class="w-4 h-4 mb-0.5" />
                            <span>Menu Lain</span>
                        </button>
                    </div>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import {
    LayoutDashboard,
    Boxes,
    ArrowLeftRight,
    GitCompare,
    Network,
    Wrench,
    Building2,
    FileSpreadsheet,
    Trash2,
    Settings,
    Server,
    QrCode,
    LogOut,
    Menu,
    X,
    Plus,
    CheckCircle2,
    AlertTriangle,
    Info,
} from 'lucide-vue-next';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const mobileMenuOpen = ref(false);
const quickCode = ref('');

// Navigation Menu Items including Asset Transfer & KSO Reconciliation
const navItems = [
    {
        label: '1. Dashboard Analytics',
        to: '/dashboard',
        icon: LayoutDashboard,
        colorClass: 'text-cyan-400/80 group-hover:text-cyan-400',
    },
    {
        label: '2. Asset Directory',
        to: '/assets',
        icon: Boxes,
        colorClass: 'text-emerald-400/80 group-hover:text-emerald-400',
    },
    {
        label: '3. Pemindahan Aset',
        to: '/transfers',
        icon: ArrowLeftRight,
        colorClass: 'text-teal-400/80 group-hover:text-teal-400',
    },
    {
        label: '4. KSO Reconciliation',
        to: '/kso-reconciliation',
        icon: GitCompare,
        colorClass: 'text-purple-400/80 group-hover:text-purple-400',
    },
    {
        label: '5. IP Network Map',
        to: '/network-map',
        icon: Network,
        colorClass: 'text-indigo-400/80 group-hover:text-indigo-400',
    },
    {
        label: '6. Maintenance Radar',
        to: '/maintenance',
        icon: Wrench,
        colorClass: 'text-amber-400/80 group-hover:text-amber-400',
    },
    {
        label: '7. Data Ruangan',
        to: '/rooms',
        icon: Building2,
        colorClass: 'text-sky-400/80 group-hover:text-sky-400',
    },
    {
        label: '8. Laporan',
        to: '/reports',
        icon: FileSpreadsheet,
        colorClass: 'text-blue-400/80 group-hover:text-blue-400',
    },
    {
        label: '9. Recycle Bin',
        to: '/recycle-bin',
        icon: Trash2,
        colorClass: 'text-rose-400/80 group-hover:text-rose-400',
    },
    {
        label: '10. Pengaturan',
        to: '/settings',
        icon: Settings,
        colorClass: 'text-slate-400 group-hover:text-slate-200',
    },
];

const userInitials = computed(() => {
    const name = auth.user?.name || 'IT';
    return name
        .split(' ')
        .slice(0, 2)
        .map((n) => n[0])
        .join('')
        .toUpperCase();
});

function isActiveRoute(target) {
    if (target === '/assets' && route.path.startsWith('/assets')) {
        return true;
    }
    return route.path === target;
}

function handleQuickCodeLookup() {
    const code = quickCode.value.trim().toUpperCase();
    if (!code) return;
    quickCode.value = '';
    mobileMenuOpen.value = false;
    router.push(`/assets/${encodeURIComponent(code)}`);
}

async function handleLogout() {
    await auth.logout();
    router.push('/login');
}
</script>
