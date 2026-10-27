<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHead from '@/Components/PageHead.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import MyHRSummary from '@/Pages/Dashboard/Partials/MyHRSummary.vue';
import { useHRData } from '@/Pages/Dashboard/Composables/useHRData';

defineProps<{
    status?: string;
}>();

// My Summary, as on the Dashboard: the signed-in user's own leaves, travel orders, pass slips and
// tardiness/undertime, for every role (like the leave summary on pbo-registryV2's profile)
const page = usePage();
const ownEmployeeId = computed<number | null>(() => Number((page.props.auth as any)?.user?.employee_id) || null);
const canViewHRSummary = computed(() => ((page.props.auth as any)?.permissions || []).includes('hr_summary.view'));

const { leaves, allLeaves, travelOrders, passSlips, tardiness, hrLoading, selectedHRYear, availableHRYears,
    fetchLeaves, fetchTravelOrders, fetchPassSlips, fetchTardiness } = useHRData();

onMounted(async () => {
    if (!canViewHRSummary.value || !ownEmployeeId.value) return;
    hrLoading.value = true;
    try {
        await Promise.all([fetchLeaves(), fetchTravelOrders(), fetchPassSlips(), fetchTardiness()]);
    } finally {
        hrLoading.value = false;
    }
});
</script>

<template>
    <PageHead />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
            >
                Profile
            </h2>
        </template>

        <!-- Full width, like the Dashboard and the other pages -->
        <div class="py-6 px-4 sm:px-6 lg:px-8">
            <div class="space-y-6">
                <MyHRSummary
                    v-if="canViewHRSummary"
                    :employee-id="ownEmployeeId"
                    :year="selectedHRYear"
                    :years="availableHRYears"
                    :leaves="leaves"
                    :all-leaves="allLeaves"
                    :travel-orders="travelOrders"
                    :pass-slips="passSlips"
                    :tardiness="tardiness"
                    :loading="hrLoading"
                    @update:year="selectedHRYear = $event"
                />

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdateProfileInformationForm
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="bg-white p-4 shadow sm:rounded-lg sm:p-8 dark:bg-gray-800"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
