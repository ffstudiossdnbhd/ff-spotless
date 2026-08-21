<template>
    <div v-if="isSupported" class="relative inline-flex items-center">
        <!-- Main Toggle Button -->
        <button
            type="button"
            :disabled="isProcessing || permissionState === 'denied'"
            @click="toggleSubscription"
            class="group relative inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full border transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
            :class="[
                isSubscribed
                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 hover:bg-emerald-500/20 focus:ring-emerald-500'
                    : permissionState === 'denied'
                        ? 'bg-stone-100 border-stone-200 text-stone-400 cursor-not-allowed'
                        : 'bg-white border-stone-300 text-stone-700 hover:bg-stone-50 hover:border-stone-400 focus:ring-stone-400 shadow-xs'
            ]"
            :title="buttonTitle"
        >
            <!-- Loading Spinner -->
            <svg
                v-if="isProcessing"
                class="animate-spin h-3.5 w-3.5 text-current"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <!-- Bell Active Icon -->
            <svg
                v-else-if="isSubscribed"
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5 text-emerald-600 animate-pulse"
                viewBox="0 0 20 20"
                fill="currentColor"
            >
                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
            </svg>

            <!-- Bell Inactive Icon -->
            <svg
                v-else
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5 text-stone-500 group-hover:text-stone-700"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>

            <!-- Status Text -->
            <span class="hidden sm:inline">
                {{ isSubscribed ? 'Notifikasi Aktif' : permissionState === 'denied' ? 'Notifikasi Disekat' : 'Aktifkan Notifikasi' }}
            </span>
            <span class="sm:hidden">
                {{ isSubscribed ? 'Aktif' : 'Notifikasi' }}
            </span>
        </button>

        <!-- Toast Feedback -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-1"
        >
            <div
                v-if="toastMessage"
                class="absolute top-full mt-1.5 right-0 z-50 px-3 py-1.5 text-xs rounded-lg shadow-lg font-medium whitespace-nowrap"
                :class="toastType === 'error' ? 'bg-rose-600 text-white' : 'bg-stone-900 text-white'"
            >
                {{ toastMessage }}
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    role: {
        type: String,
        default: 'cleaner', // 'admin' or 'cleaner'
    },
});

const isSupported = ref(false);
const isSubscribed = ref(false);
const isProcessing = ref(false);
const permissionState = ref('default');
const toastMessage = ref('');
const toastType = ref('info');
let toastTimer = null;

function showToast(message, type = 'info') {
    toastMessage.value = message;
    toastType.value = type;
    if (toastTimer) clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
        toastMessage.value = '';
    }, 4000);
}

const buttonTitle = computed(() => {
    if (permissionState.value === 'denied') {
        return 'Notifikasi dihalang oleh tetapan pelayar anda.';
    }
    if (isSubscribed.value) {
        return 'Klik untuk hentikan langganan notifikasi push pada peranti ini.';
    }
    return 'Klik untuk menerima notifikasi push untuk kemas kini senarai semak.';
});

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

async function getServiceWorkerRegistration() {
    if (!('serviceWorker' in navigator)) {
        return null;
    }
    return await navigator.serviceWorker.ready;
}

async function syncSubscription() {
    try {
        if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            isSupported.value = false;
            return;
        }

        isSupported.value = true;
        permissionState.value = Notification.permission;

        if (Notification.permission === 'denied') {
            isSubscribed.value = false;
            return;
        }

        const registration = await getServiceWorkerRegistration();
        if (!registration) {
            return;
        }

        const subscription = await registration.pushManager.getSubscription();
        isSubscribed.value = !!subscription;

        // If currently subscribed, keep the server updated with current role
        if (subscription && Notification.permission === 'granted') {
            await saveSubscriptionToServer(subscription);
        }
    } catch (e) {
        console.warn('Gagal menyemak status langganan notifikasi push:', e);
    }
}

async function saveSubscriptionToServer(subscription) {
    const rawSub = subscription.toJSON();
    await fetch('/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            endpoint: subscription.endpoint,
            keys: rawSub.keys,
            content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
            role: props.role,
        }),
    });
}

async function toggleSubscription() {
    if (isProcessing.value) return;
    isProcessing.value = true;

    try {
        if (isSubscribed.value) {
            await unsubscribe();
        } else {
            await subscribe();
        }
    } catch (err) {
        console.error('Ralat mengemas kini notifikasi:', err);
        showToast('Ralat mengemas kini notifikasi push.', 'error');
    } finally {
        isProcessing.value = false;
    }
}

async function subscribe() {
    const permission = await Notification.requestPermission();
    permissionState.value = permission;

    if (permission !== 'granted') {
        if (permission === 'denied') {
            showToast('Kebenaran notifikasi disekat dalam pelayar anda.', 'error');
        }
        return;
    }

    const keyRes = await fetch('/push/public-key', {
        headers: { 'Accept': 'application/json' },
    });
    const keyData = await keyRes.json();

    if (!keyData.publicKey) {
        showToast('Kunci VAPID tidak dikonfigurasi pada pelayan.', 'error');
        return;
    }

    const registration = await getServiceWorkerRegistration();
    if (!registration) {
        showToast('Service worker belum bersedia.', 'error');
        return;
    }

    const convertedVapidKey = urlBase64ToUint8Array(keyData.publicKey);

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: convertedVapidKey,
    });

    await saveSubscriptionToServer(subscription);
    isSubscribed.value = true;
    showToast('Notifikasi push berjaya diaktifkan! 🎉', 'info');
}

async function unsubscribe() {
    const registration = await getServiceWorkerRegistration();
    if (!registration) return;

    const subscription = await registration.pushManager.getSubscription();
    if (subscription) {
        try {
            await fetch('/push/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                }),
            });
        } catch (e) {
            console.warn('Gagal memadam langganan dari pelayan:', e);
        }

        await subscription.unsubscribe();
    }

    isSubscribed.value = false;
    showToast('Notifikasi push telah dinyahaktifkan.', 'info');
}

onMounted(() => {
    syncSubscription();
});
</script>
