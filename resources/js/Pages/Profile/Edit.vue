<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import Toast from '@/Components/Toast.vue';
import { Deferred, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import MyLeavesAndFilings from './Partials/MyLeavesAndFilings.vue';
import { PROFILE_CARD } from './Partials/profileStyles';

// Laid out as pbo-registryV2's profile: the user's leaves and filings (PBO staff), then their profile and password side by side
const props = defineProps<{
    status?: string | null;
    leaves?: { summary: any } | null; // deferred; null unless the user is PBO staff linked to an employee
    filings?: { records: any } | null; // deferred, as leaves
    employee?: { employee_id: string; name: string; position_title: string | null; parenthetical_title: string | null } | null;
}>();

// A toast after filing, editing or saving the profile, as on the module pages
const toastRef = ref<InstanceType<typeof Toast> | null>(null);
const showStatus = (status?: string | null) => {
    if (!status) return;
    const title = /filed\.$/.test(status) ? 'Filed' : /updated\.$/.test(status) ? 'Updated' : 'Success';
    toastRef.value?.add('success', title, status, 4000);
};
onMounted(() => showStatus(props.status));
watch(() => props.status, (status) => showStatus(status));
</script>

<template>
    <PageHead />
    <Toast ref="toastRef" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:justify-between sm:items-end">
                <div>
                    <p class="text-[10.5px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400 mb-0.5">Provincial Budget Office</p>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Profile</h2>
                </div>
                <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <Link :href="route('dashboard')" class="hover:text-emerald-600 dark:hover:text-emerald-400"><i class="fas fa-house mr-1"></i>Dashboard</Link>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">Profile</span>
                </nav>
            </div>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- PBO staff only: their leaves and filings in one panel; nothing shows for anyone else -->
            <Deferred :data="['leaves', 'filings']">
                <template #fallback>
                    <div v-if="employee" :class="PROFILE_CARD" class="text-center text-xs text-gray-500 dark:text-gray-400">
                        <i class="fas fa-spinner fa-spin mr-1"></i>Loading your leaves and filings…
                    </div>
                </template>
                <div v-if="leaves || filings" :class="PROFILE_CARD">
                    <MyLeavesAndFilings :leaves="leaves ?? null" :filings="filings ?? null" :employee="employee ?? null" />
                </div>
            </Deferred>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div :class="PROFILE_CARD">
                    <UpdateProfileInformationForm />
                </div>
                <div :class="PROFILE_CARD">
                    <UpdatePasswordForm />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
