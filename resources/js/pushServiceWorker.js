export const PUSH_SERVICE_WORKER_URL = '/service-worker.js';
export const PUSH_SERVICE_WORKER_SCOPE = '/';

const ACTIVATION_TIMEOUT_MS = 15000;
let registrationPromise = null;

function waitForActivation(registration) {
    if (registration.active) {
        return Promise.resolve(registration);
    }

    const worker = registration.installing || registration.waiting;
    if (!worker) {
        return Promise.reject(new Error('Pelayar tidak menyediakan service worker untuk diaktifkan.'));
    }

    return new Promise((resolve, reject) => {
        const timeout = window.setTimeout(() => {
            cleanup();
            reject(new Error('Service worker mengambil masa terlalu lama untuk diaktifkan.'));
        }, ACTIVATION_TIMEOUT_MS);

        const cleanup = () => {
            window.clearTimeout(timeout);
            worker.removeEventListener('statechange', handleStateChange);
        };

        const handleStateChange = () => {
            if (worker.state === 'activated' || registration.active) {
                cleanup();
                resolve(registration);
            } else if (worker.state === 'redundant') {
                cleanup();
                reject(new Error('Service worker gagal dipasang oleh pelayar.'));
            }
        };

        worker.addEventListener('statechange', handleStateChange);
        handleStateChange();
    });
}

async function registerAndActivate() {
    if (!('serviceWorker' in navigator)) {
        throw new Error('Pelayar ini tidak menyokong service worker.');
    }

    const registration = await navigator.serviceWorker.register(
        PUSH_SERVICE_WORKER_URL,
        { scope: PUSH_SERVICE_WORKER_SCOPE },
    );

    return waitForActivation(registration);
}

export function getPushServiceWorkerRegistration() {
    if (!registrationPromise) {
        registrationPromise = registerAndActivate().catch((error) => {
            registrationPromise = null;
            throw error;
        });
    }

    return registrationPromise;
}
