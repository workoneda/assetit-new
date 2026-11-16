<template>
    <div class="space-y-6">
        <!-- Tabs Navigation -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 pb-3">
            <button
                @click="activeTab = 'profile'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                :class="activeTab === 'profile' ? 'bg-cyan-500 text-slate-950' : 'bg-navy-900 text-slate-300 hover:text-white border border-slate-800'"
            >
                <User class="w-4 h-4" />
                <span>Profil Pengguna</span>
            </button>
            <button
                v-if="auth.isAdmin"
                @click="activeTab = 'users'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                :class="activeTab === 'users' ? 'bg-cyan-500 text-slate-950' : 'bg-navy-900 text-slate-300 hover:text-white border border-slate-800'"
            >
                <Users class="w-4 h-4" />
                <span>Manajemen Pengguna (Role)</span>
            </button>
            <button
                @click="activeTab = 'database'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2"
                :class="activeTab === 'database' ? 'bg-cyan-500 text-slate-950' : 'bg-navy-900 text-slate-300 hover:text-white border border-slate-800'"
            >
                <Database class="w-4 h-4" />
                <span>Backup Database MySQL &amp; Sistem</span>
            </button>
        </div>

        <!-- TAB 1: PROFIL PENGGUNA -->
        <div v-if="activeTab === 'profile'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-5 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-6 space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-cyan-500/20 border border-cyan-500/40 flex items-center justify-center text-2xl font-extrabold text-cyan-300">
                        {{ auth.user?.name?.[0] || 'U' }}
                    </div>
                    <div>
                        <h3 class="text-lg font-extrabold text-white">{{ auth.user?.name }}</h3>
                        <p class="text-xs text-slate-400">{{ auth.user?.position || 'Petugas IT RSUD' }}</p>
                        <span
                            class="inline-block mt-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border"
                            :class="auth.isAdmin
                                ? 'bg-purple-500/20 text-purple-300 border-purple-500/40'
                                : 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40'"
                        >
                            Role: {{ auth.isAdmin ? 'Administrator' : 'Petugas IT' }}
                        </span>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-navy-950 border border-slate-800 space-y-2 text-xs">
                    <div class="font-bold text-slate-300">Hak Akses Role Anda:</div>
                    <ul v-if="auth.isAdmin" class="list-disc list-inside text-slate-400 space-y-1">
                        <li>Akses penuh ke seluruh fitur sistem</li>
                        <li>Tambah, edit, hapus, dan pulihkan data aset</li>
                        <li>Mengelola pengguna (Administrator &amp; Petugas IT)</li>
                        <li>Melihat seluruh laporan, export Excel/PDF &amp; backup database MySQL</li>
                    </ul>
                    <ul v-else class="list-disc list-inside text-slate-400 space-y-1">
                        <li>Melihat seluruh data inventaris aset IT</li>
                        <li>Menambahkan aset baru &amp; memperbarui data aset</li>
                        <li>Mencatat pemeliharaan &amp; perbaikan perangkat</li>
                        <li>Melihat laporan, cetak QR label &amp; export Excel/PDF</li>
                    </ul>
                </div>
            </div>

            <div class="lg:col-span-7 bg-navy-900/90 border border-slate-800/90 rounded-2xl p-6">
                <h3 class="text-base font-bold text-white mb-4">Perbarui Profil &amp; Password</h3>
                <form @submit.prevent="updateProfile" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Nama Lengkap</label>
                            <input v-model="profileForm.name" type="text" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Username</label>
                            <input v-model="profileForm.username" type="text" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-mono" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Alamat Email</label>
                            <input v-model="profileForm.email" type="email" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Jabatan / Unit</label>
                            <input v-model="profileForm.position" type="text" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white" />
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-800 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Password Saat Ini</label>
                            <input v-model="profileForm.current_password" type="password" placeholder="Kosongkan jika tidak diubah" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Password Baru</label>
                            <input v-model="profileForm.new_password" type="password" placeholder="Min. 6 karakter" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Konfirmasi Password Baru</label>
                            <input v-model="profileForm.new_password_confirmation" type="password" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold">
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB 2: MANAJEMEN PENGGUNA (Administrator Only - Section 11) -->
        <div v-if="activeTab === 'users' && auth.isAdmin" class="space-y-5">
            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl p-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-white">Manajemen Pengguna &amp; Hak Akses</h3>
                    <p class="text-xs text-slate-400">Kelola akun Administrator dan Petugas IT rumah sakit</p>
                </div>
                <button
                    @click="openCreateUser"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 text-slate-950 font-bold text-xs"
                >
                    <Plus class="w-4 h-4" />
                    <span>Tambah Pengguna</span>
                </button>
            </div>

            <div class="bg-navy-900/90 border border-slate-800/90 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-navy-950 border-b border-slate-800 text-[11px] font-bold uppercase text-slate-400">
                            <th class="py-3.5 px-4">Nama &amp; Jabatan</th>
                            <th class="py-3.5 px-4">Username / Email</th>
                            <th class="py-3.5 px-4">Role</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        <tr v-for="u in users" :key="u.id" class="hover:bg-slate-800/35">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-white">{{ u.name }}</div>
                                <div class="text-[11px] text-slate-400">{{ u.position || '-' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono">
                                <div class="text-cyan-300">{{ u.email }}</div>
                                <div class="text-[11px] text-slate-400">@{{ u.username }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase border"
                                    :class="u.role === 'admin'
                                        ? 'bg-purple-500/20 text-purple-300 border-purple-500/40'
                                        : 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40'"
                                >
                                    {{ u.role === 'admin' ? 'Administrator' : 'Petugas IT' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold"
                                    :class="u.is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'"
                                >
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex gap-1.5">
                                    <button @click="openEditUser(u)" class="p-1.5 rounded-lg bg-slate-800 hover:bg-amber-500/20 text-slate-300 hover:text-amber-300">
                                        <Edit3 class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        v-if="u.id !== auth.user?.id"
                                        @click="deleteUser(u)"
                                        class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-500/20 text-slate-300 hover:text-rose-300"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <!-- TAB 3: BACKUP DATABASE MYSQL & PENGATURAN PRODUKSI (Section 16) -->
        <div v-if="activeTab === 'database'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <div class="lg:col-span-6 bg-navy-900/90 border border-cyan-500/30 rounded-2xl p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400">
                        <Database class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Backup Database MySQL (.sql)</h3>
                        <p class="text-xs text-slate-400">Unduh salinan penuh struktur tabel &amp; seluruh data inventaris MySQL</p>
                    </div>
                </div>

                <div class="p-4 rounded-xl bg-navy-950 border border-slate-800 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Database Engine:</span>
                        <span class="font-mono font-bold text-emerald-400">{{ systemInfo.system?.database_driver }} ({{ systemInfo.system?.mysql_version }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Nama Database:</span>
                        <span class="font-mono font-bold text-cyan-300">{{ systemInfo.system?.database_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Backend Framework:</span>
                        <span class="font-mono text-white">Laravel {{ systemInfo.system?.laravel_version }} (PHP {{ systemInfo.system?.php_version }})</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Total Aset Aktif / Ruangan:</span>
                        <span class="font-mono text-white">{{ systemInfo.counts?.assets_active || 0 }} Aset / {{ systemInfo.counts?.rooms || 0 }} Ruangan</span>
                    </div>
                </div>

                <a
                    href="/api/system/backup"
                    target="_blank"
                    class="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-slate-950 font-extrabold text-xs shadow-lg shadow-cyan-500/20 flex items-center justify-center gap-2 transition"
                >
                    <Download class="w-4 h-4" />
                    <span>Download Full Backup Database MySQL (.sql)</span>
                </a>
            </div>

            <!-- Mode Produksi Bersih / Reset Data Sampel -->
            <div v-if="auth.isAdmin" class="lg:col-span-6 bg-navy-900/90 border border-amber-500/30 rounded-2xl p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
                        <ShieldAlert class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-white">Persiapan Mode Produksi (Bersihkan Data Awal)</h3>
                        <p class="text-xs text-slate-400">
                            Sesuai ketentuan produksi: bersihkan data sampel awal sebelum menginput data aktual RSUD
                        </p>
                    </div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed">
                    Gunakan tombol di bawah ini apabila Anda ingin mengosongkan seluruh data aset contoh (dummy awal) untuk mulai menginput inventaris riil rumah sakit dari nol atau melalui Import Excel. Master ruangan dan akun pengguna tetap dipertahankan.
                </p>

                <div class="flex flex-wrap gap-3 pt-2">
                    <button
                        @click="clearInventoryForProduction"
                        class="px-4 py-2.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 border border-rose-500/40 text-rose-300 font-bold text-xs transition"
                    >
                        Kosongkan Data Aset (Mode Produksi Bersih)
                    </button>
                    <button
                        @click="reseedHospitalData"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-semibold text-xs transition"
                    >
                        Muat Ulang Data Awal Rumah Sakit
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Tambah / Edit Pengguna -->
        <div v-if="showUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm">
            <div class="bg-navy-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">
                        {{ editingUser ? 'Edit Pengguna' : 'Tambah Pengguna Baru' }}
                    </h3>
                    <button @click="showUserModal = false" class="text-slate-400 hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitUser" class="space-y-3.5 text-xs">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Nama Lengkap</label>
                        <input v-model="userForm.name" type="text" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Username</label>
                            <input v-model="userForm.username" type="text" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white font-mono" />
                        </div>
                        <div>
                            <label class="block text-slate-300 font-semibold mb-1">Role Akses</label>
                            <select v-model="userForm.role" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white">
                                <option value="petugas_it">Petugas IT</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Email</label>
                        <input v-model="userForm.email" type="email" required class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Jabatan</label>
                        <input v-model="userForm.position" type="text" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">
                            Password {{ editingUser ? '(Kosongkan jika tidak diganti)' : '' }}
                        </label>
                        <input v-model="userForm.password" type="password" :required="!editingUser" class="w-full bg-navy-950 border border-slate-700 rounded-xl px-3 py-2 text-white" />
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 text-slate-300 cursor-pointer">
                            <input type="checkbox" v-model="userForm.is_active" class="rounded bg-navy-950 border-slate-700 text-cyan-500" />
                            <span>Akun Aktif</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                        <button type="button" @click="showUserModal = false" class="px-4 py-2 rounded-xl border border-slate-700 text-slate-300">Batal</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-cyan-500 text-slate-950 font-bold">Simpan Pengguna</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { User, Users, Database, Plus, Edit3, Trash2, Download, ShieldAlert, X } from 'lucide-vue-next';

const auth = useAuthStore();
const activeTab = ref('profile');

const profileForm = reactive({
    name: auth.user?.name || '',
    username: auth.user?.username || '',
    email: auth.user?.email || '',
    position: auth.user?.position || '',
    phone: auth.user?.phone || '',
    current_password: '',
    new_password: '',
    new_password_confirmation: '',
});

const users = ref([]);
const systemInfo = ref({});
const showUserModal = ref(false);
const editingUser = ref(null);
const userForm = reactive({
    name: '',
    username: '',
    email: '',
    role: 'petugas_it',
    position: '',
    password: '',
    is_active: true,
});

async function updateProfile() {
    try {
        const { data } = await api.put('/auth/profile', profileForm);
        auth.user = data.user;
        profileForm.current_password = '';
        profileForm.new_password = '';
        profileForm.new_password_confirmation = '';
        auth.notify(data.message, 'success');
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal memperbarui profil.', 'error');
    }
}

async function fetchUsers() {
    if (!auth.isAdmin) return;
    const { data } = await api.get('/users');
    users.value = data.data || [];
}

async function fetchSystemInfo() {
    const { data } = await api.get('/system/info');
    systemInfo.value = data;
}

function openCreateUser() {
    editingUser.value = null;
    userForm.name = '';
    userForm.username = '';
    userForm.email = '';
    userForm.role = 'petugas_it';
    userForm.position = 'Petugas IT Support';
    userForm.password = '';
    userForm.is_active = true;
    showUserModal.value = true;
}

function openEditUser(u) {
    editingUser.value = u;
    userForm.name = u.name;
    userForm.username = u.username;
    userForm.email = u.email;
    userForm.role = u.role;
    userForm.position = u.position || '';
    userForm.password = '';
    userForm.is_active = u.is_active;
    showUserModal.value = true;
}

async function submitUser() {
    try {
        if (editingUser.value) {
            const { data } = await api.put(`/users/${editingUser.value.id}`, userForm);
            auth.notify(data.message, 'success');
        } else {
            const { data } = await api.post('/users', userForm);
            auth.notify(data.message, 'success');
        }
        showUserModal.value = false;
        fetchUsers();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menyimpan pengguna.', 'error');
    }
}

async function deleteUser(u) {
    if (!confirm(`Hapus pengguna ${u.name}?`)) return;
    try {
        const { data } = await api.delete(`/users/${u.id}`);
        auth.notify(data.message, 'success');
        fetchUsers();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal menghapus pengguna.', 'error');
    }
}

async function clearInventoryForProduction() {
    if (!confirm('Kosongkan seluruh data sampel inventaris aset untuk memulai mode produksi bersih?')) return;
    try {
        const { data } = await api.post('/system/clear-inventory');
        auth.notify(data.message, 'success');
        fetchSystemInfo();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal mereset data.', 'error');
    }
}

async function reseedHospitalData() {
    try {
        const { data } = await api.post('/system/reseed');
        auth.notify(data.message, 'success');
        fetchSystemInfo();
    } catch (err) {
        auth.notify(err.response?.data?.message || 'Gagal memuat ulang data.', 'error');
    }
}

onMounted(() => {
    fetchUsers();
    fetchSystemInfo();
});
</script>

