import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const initialized = ref(false);
    const loading = ref(false);
    const toasts = ref([]);

    const isAuthenticated = computed(() => !!user.value);
    const isAdmin = computed(() => user.value?.role === 'admin');

    function notify(message, type = 'success', duration = 3800) {
        const id = Date.now() + Math.random();
        toasts.value.push({ id, message, type });
        setTimeout(() => {
            removeToast(id);
        }, duration);
    }

    function removeToast(id) {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }

    async function fetchUser() {
        try {
            const { data } = await api.get('/auth/me');
            user.value = data.user;
        } catch {
            user.value = null;
        } finally {
            initialized.value = true;
        }
    }

    async function login(identity, password, remember = true) {
        loading.value = true;
        try {
            const { data } = await api.post('/auth/login', { identity, password, remember });
            user.value = data.user;
            initialized.value = true;
            notify(`Selamat datang kembali, ${data.user.name}!`, 'success');
            return data.user;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await api.post('/auth/logout');
        } finally {
            user.value = null;
            notify('Anda telah keluar dari sistem.', 'info');
        }
    }

    return {
        user,
        initialized,
        loading,
        toasts,
        isAuthenticated,
        isAdmin,
        notify,
        removeToast,
        fetchUser,
        login,
        logout,
    };
});
