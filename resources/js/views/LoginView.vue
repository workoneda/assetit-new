<template>
    <div class="min-h-screen bg-navy-950 flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Decorative SaaS Glows -->
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-cyan-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="max-w-md w-full relative z-10">
            <!-- Header Card -->
            <div class="text-center mb-7">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-cyan-500 via-blue-600 to-indigo-600 shadow-2xl shadow-cyan-500/30 mb-4">
                    <Server class="w-8 h-8 text-white" />
                </div>
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/10 border border-cyan-500/30 text-cyan-300 text-xs font-semibold mb-2">
                    <ShieldCheck class="w-3.5 h-3.5" />
                    <span>IT Asset Management System RSUD</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                    SIM-ASET IT Rumah Sakit
                </h1>
                <p class="text-xs sm:text-sm text-slate-400 mt-1.5">
                    Sistem Inventarisasi dan Monitoring Perangkat Teknologi Informasi
                </p>
            </div>

            <!-- Login Box -->
            <div class="bg-navy-900/90 backdrop-blur-xl border border-slate-800/90 rounded-2xl p-6 sm:p-8 shadow-2xl">
                <div v-if="errorMsg" class="mb-5 p-3.5 rounded-xl bg-rose-500/15 border border-rose-500/40 text-xs text-rose-200 flex items-center gap-2.5">
                    <AlertTriangle class="w-4 h-4 text-rose-400 shrink-0" />
                    <span>{{ errorMsg }}</span>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Email atau Username
                        </label>
                        <div class="relative">
                            <User class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="identity"
                                type="text"
                                required
                                autofocus
                                placeholder="admin@rsud.go.id atau admin"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl pl-10 pr-4 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <Lock class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="Masukkan password"
                                class="w-full bg-navy-950 border border-slate-700/90 rounded-xl pl-10 pr-10 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                            >
                                <EyeOff v-if="showPassword" class="w-4 h-4" />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="inline-flex items-center gap-2 text-slate-400 cursor-pointer">
                            <input type="checkbox" v-model="remember" class="rounded bg-navy-950 border-slate-700 text-cyan-500" />
                            <span>Ingat sesi login</span>
                        </label>
                        <span class="text-cyan-400 font-mono text-[11px]">Proteksi Hash Bcrypt</span>
                    </div>

                    <button
                        type="submit"
                        :disabled="auth.loading"
                        class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 disabled:opacity-50 text-slate-950 font-extrabold text-sm shadow-lg shadow-cyan-500/25 transition flex items-center justify-center gap-2"
                    >
                        <LogIn class="w-4 h-4" />
                        <span>{{ auth.loading ? 'Memverifikasi Akses...' : 'Masuk ke Dashboard' }}</span>
                    </button>
                </form>

                <!-- Quick Role Selection for Hospital IT Team -->
                <div class="mt-6 pt-5 border-t border-slate-800/80">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 mb-2.5 text-center">
                        Akses Cepat Akun Role Pengguna
                    </p>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button
                            type="button"
                            @click="fillCredentials('admin@rsud.go.id', 'password123')"
                            class="p-2.5 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 border border-purple-500/30 text-left transition group"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-purple-300">Administrator</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-200">Full Access</span>
                            </div>
                            <p class="text-[11px] font-mono text-slate-400 mt-1 truncate">admin@rsud.go.id</p>
                        </button>

                        <button
                            type="button"
                            @click="fillCredentials('petugas@rsud.go.id', 'password123')"
                            class="p-2.5 rounded-xl bg-cyan-500/10 hover:bg-cyan-500/20 border border-cyan-500/30 text-left transition group"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-cyan-300">Petugas IT</span>
                                <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-cyan-500/20 text-cyan-200">Operasional</span>
                            </div>
                            <p class="text-[11px] font-mono text-slate-400 mt-1 truncate">petugas@rsud.go.id</p>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { Server, ShieldCheck, User, Lock, Eye, EyeOff, LogIn, AlertTriangle } from 'lucide-vue-next';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const identity = ref('admin@rsud.go.id');
const password = ref('password123');
const remember = ref(true);
const showPassword = ref(false);
const errorMsg = ref('');

function fillCredentials(userEmail, userPass) {
    identity.value = userEmail;
    password.value = userPass;
    handleSubmit();
}

async function handleSubmit() {
    errorMsg.value = '';
    try {
        await auth.login(identity.value, password.value, remember.value);
        const target = route.query.redirect || '/dashboard';
        router.push(target);
    } catch (err) {
        errorMsg.value = err.response?.data?.message || 'Gagal melakukan autentikasi. Periksa koneksi server.';
    }
}
</script>
