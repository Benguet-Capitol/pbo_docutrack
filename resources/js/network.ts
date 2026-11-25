import axios from 'axios';
import { createApp, h, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Toast from '@/Components/Toast.vue';

/**
 * What the app does when the network fails, on every page (the login page too), as on pbo-registryV2:
 * - this computer goes offline / back online: a toast says so;
 * - a request gets no answer (the server is down or unreachable): "Connection lost" instead of a silent stop.
 * Its own toast layer, apart from the pages' toasts, so it shows whatever page is open.
 */
type Severity = 'success' | 'info' | 'error';
const toast = ref<InstanceType<typeof Toast> | null>(null);
const pending: Array<[Severity, string, string, number]> = [];

const host = document.createElement('div');
host.id = 'network-toasts';
document.body.appendChild(host);
createApp({ render: () => h(Toast, { ref: toast }) }).mount(host);

const show = (severity: Severity, summary: string, detail: string, life: number) => {
    if (toast.value) toast.value.add(severity, summary, detail, life);
    else pending.push([severity, summary, detail, life]);
};
queueMicrotask(() => pending.splice(0).forEach((args) => toast.value?.add(...args)));

// One "Connection lost" at a time: a page that loads several lists at once would otherwise stack them
let lastLost = 0;
const connectionLost = () => {
    if (Date.now() - lastLost < 10000) return;
    lastLost = Date.now();
    navigator.onLine === false
        ? show('error', 'You are offline', 'This computer has no network connection. Nothing can be loaded or saved until it is back.', 12000)
        : show('error', 'Connection lost', 'DocuTrack cannot be reached: the server may be down or the network interrupted. Your last action may not have been saved; reload the page to check before trying again.', 12000);
};

window.addEventListener('offline', () => {
    lastLost = Date.now();
    show('error', 'You are offline', 'Check your network connection. Saves made now will not reach the server.', 12000);
});
window.addEventListener('online', () => show('success', 'Back online', 'The connection is back. Reload the page if a save was interrupted.', 6000));

// Page visits and form saves (Inertia): no response at all
router.on('exception', (event) => {
    const error: any = event.detail.exception;
    if (error?.isAxiosError && !error.response) {
        connectionLost();
        event.preventDefault();
    }
});

// The modules' lists and actions (axios): no response, and not cancelled on purpose (e.g. a search typed over)
axios.interceptors.response.use(undefined, (error) => {
    if (error?.isAxiosError && !error.response && !axios.isCancel(error) && error.code !== 'ERR_CANCELED') connectionLost();
    return Promise.reject(error);
});
