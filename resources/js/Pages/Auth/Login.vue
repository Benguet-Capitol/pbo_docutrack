<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import PageHead from '@/Components/PageHead.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const isSubmitting = ref(false);
const isDarkMode = ref(false);

onMounted(() => {
    // Check if dark mode was previously saved
    const savedMode = localStorage.getItem('darkMode');
    if (savedMode === 'true') {
        isDarkMode.value = true;
        document.documentElement.classList.add('dark');
    }
});

const toggleDarkMode = () => {
    isDarkMode.value = !isDarkMode.value;
    if (isDarkMode.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('darkMode', 'true');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('darkMode', 'false');
    }
};

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    isSubmitting.value = true;
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <GuestLayout>
        <PageHead />

        <!-- Dark Mode Toggle Button -->
        <div class="absolute top-2 right-2 sm:top-4 sm:right-4 z-50">
            <button
                @click="toggleDarkMode"
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400 transition duration-150"
                :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
                <i v-if="isDarkMode" class="fas fa-sun text-lg"></i>
                <i v-else class="fas fa-moon text-lg"></i>
            </button>
        </div>

        <div class="flex items-center justify-center min-h-full h-full px-4 py-8 sm:py-0 bg-gradient-to-br from-emerald-50 via-white to-emerald-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <div class="w-full max-w-md">

                <!-- Header Section -->
                <div class="text-center mb-6 sm:mb-8">
                    <div class="flex justify-center items-center gap-3 sm:gap-6 mb-3">
                        <!-- Benguet Logo -->
                        <div class="animate-bounce-slow" style="animation-delay: 0s;">
                            <img
                                src="/benguetlogo.ico"
                                alt="Benguet Logo"
                                class="w-20 h-20 sm:w-32 sm:h-32 object-contain drop-shadow-lg hover:scale-110 transition-transform duration-300"
                            />
                        </div>
                        <!-- Bagong Pilipinas Logo -->
                        <div class="animate-bounce-slow" style="animation-delay: 0.2s;">
                            <img
                                src="/bagongpilipinaslogo.png"
                                alt="Bagong Pilipinas Logo"
                                class="w-20 h-20 sm:w-32 sm:h-32 object-contain drop-shadow-lg hover:scale-110 transition-transform duration-300"
                            />
                        </div>
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-bold text-gray-900 dark:text-white mb-1">
                        PBO | DocuTrack
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                        Provincial Budget Office
                    </p>
                </div>

                <!-- Login Form Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-5 sm:p-8 border border-emerald-100 dark:border-gray-700">

                    <!-- Session Status -->
                    <div v-if="status" class="mb-6 p-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-sm font-medium border border-emerald-200 dark:border-emerald-800">
                        {{ status }}
                    </div>

                    <!-- Form Header -->
                    <div class="mb-6">
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900 dark:text-white mb-1">
                            Sign In
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Enter credentials
                        </p>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="space-y-5">

                            <!-- Username -->
                            <div class="space-y-2">
                                <InputLabel for="username" value="Username" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-user text-emerald-500 dark:text-emerald-400"></i>
                                    </div>
                                    <TextInput
                                        id="username"
                                        type="text"
                                        class="pl-10 block w-full border-gray-300 dark:border-gray-600 focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-500 transition duration-200"
                                        v-model="form.username"
                                        autofocus
                                        autocomplete="username"
                                        placeholder="Enter username"
                                    />
                                </div>
                                <InputError :message="form.errors.username" />
                            </div>

                            <!-- Password -->
                            <div class="space-y-2">
                                <InputLabel for="password" value="Password" />
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                        <i class="fas fa-lock text-emerald-500 dark:text-emerald-400"></i>
                                    </div>
                                    <TextInput
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="pl-10 pr-10 block w-full border-gray-300 dark:border-gray-600 focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-500 transition duration-200"
                                        v-model="form.password"
                                        autocomplete="current-password"
                                        placeholder="Enter password"
                                    />
                                    <button
                                        type="button"
                                        @click="togglePassword"
                                        tabindex="-1"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-emerald-600 dark:text-gray-400 dark:hover:text-emerald-400 transition duration-200"
                                    >
                                        <i v-if="!showPassword" class="fas fa-eye text-sm"></i>
                                        <i v-else class="fas fa-eye-slash text-sm"></i>
                                    </button>
                                </div>
                                <InputError :message="form.errors.password" />
                            </div>

                            <!-- Login Button -->
                            <div class="pt-2">
                                <PrimaryButton
                                    class="justify-center w-full py-2.5 text-base font-medium bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 focus:ring-emerald-500 dark:bg-emerald-600 dark:hover:bg-emerald-700"
                                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                                    :disabled="form.processing"
                                >
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    {{ form.processing ? 'Signing in...' : 'Sign In' }}
                                </PrimaryButton>
                            </div>

                            <!-- Footer Text -->
                            <p class="text-xs text-center text-gray-600 dark:text-gray-400 pt-2">
                                For account assistance,<br>
                                <span class="text-gray-700 dark:text-gray-300 font-medium">contact the System Administrator</span>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
@keyframes bounceSlow {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-12px);
    }
}

.animate-bounce-slow {
    animation: bounceSlow 2s ease-in-out infinite;
}
</style>