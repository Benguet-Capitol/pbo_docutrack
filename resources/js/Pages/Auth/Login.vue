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
        <div class="absolute top-4 right-4 z-50">
            <button
                @click="toggleDarkMode"
                type="button"
                class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition duration-150"
                :title="isDarkMode ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
                <!-- Sun Icon (Light Mode) -->
                <i v-if="isDarkMode" class="fas fa-sun text-lg"></i>
                <!-- Moon Icon (Dark Mode) -->
                <i v-else class="fas fa-moon text-lg"></i>
            </button>
        </div>

        <div class="flex items-center justify-center min-h-screen px-4 py-12 bg-gradient-to-br from-gray-50 via-emerald-50 to-gray-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
            <div class="w-full max-w-6xl">
                <!-- Main Container with Two Columns -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 md:gap-12 items-center">

                    <!-- Left Column: Login Form -->
                    <div class="animate-slide-in-left">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8 md:p-12 border border-gray-200 dark:border-gray-700">
                            <!-- Session Status -->
                            <div v-if="status" class="mb-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 text-sm font-medium animate-fade-in-up border border-blue-200 dark:border-blue-800">
                                {{ status }}
                            </div>

                            <!-- Form Header -->
                            <div class="mb-8 animate-fade-in-up">
                                <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">
                                    Sign In
                                </h1>
                                <p class="text-gray-600 dark:text-gray-400 text-sm">
                                    Enter your credentials to continue
                                </p>
                            </div>

                            <form @submit.prevent="submit">
                                <div class="space-y-6">
                                    <!-- Username -->
                                    <div class="space-y-2 animate-fade-in-up" style="animation-delay: 0.3s;">
                                        <InputLabel for="username" value="Username" />

                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="fas fa-user text-emerald-500 dark:text-emerald-400"></i>
                                            </div>
                                            <TextInput
                                                id="username"
                                                type="text"
                                                class="login-input pl-10 block w-full transition duration-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-600"
                                                v-model="form.username"
                                                autofocus
                                                autocomplete="username"
                                                placeholder="Enter your username"
                                            />
                                        </div>

                                        <InputError :message="form.errors.username" />
                                    </div>

                                    <!-- Password -->
                                    <div class="space-y-2 animate-fade-in-up" style="animation-delay: 0.4s;">
                                        <InputLabel for="password" value="Password" />

                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                                <i class="fas fa-lock text-emerald-500 dark:text-emerald-400"></i>
                                            </div>
                                            <TextInput
                                                id="password"
                                                :type="showPassword ? 'text' : 'password'"
                                                class="login-input pl-10 pr-10 block w-full transition duration-300 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 dark:focus:ring-emerald-600"
                                                v-model="form.password"
                                                autocomplete="current-password"
                                                placeholder="Enter your password"
                                            />
                                            <button
                                                type="button"
                                                @click="togglePassword"
                                                tabindex="-1"
                                                class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition duration-200"
                                            >
                                                <i v-if="!showPassword" class="fas fa-eye"></i>
                                                <i v-else class="fas fa-eye-slash"></i>
                                            </button>
                                        </div>

                                        <InputError :message="form.errors.password" />
                                    </div>

                                    <!-- Login Button -->
                                    <div class="animate-fade-in-up" style="animation-delay: 0.5s;">
                                        <PrimaryButton
                                            class="justify-center gap-2 w-full py-3 text-base font-semibold transition duration-300 transform hover:scale-105 active:scale-95"
                                            :class="{ 'opacity-25': form.processing }"
                                            :disabled="form.processing"
                                        >
                                            <i class="fas fa-sign-in-alt"></i>
                                            <span>{{ form.processing ? 'Signing in...' : 'Sign In' }}</span>
                                        </PrimaryButton>
                                    </div>

                                    <!-- Register Info -->
                                    <p class="text-sm text-gray-600 dark:text-gray-400 text-center animate-fade-in-up" style="animation-delay: 0.6s;">
                                        For your account assistance,<br>
                                        <span class="text-gray-800 dark:text-gray-200 font-medium">
                                            Contact your administrator
                                        </span>
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Logo and System Name -->
                    <div class="hidden md:flex flex-col items-center justify-center animate-slide-in-right">
                        <!-- Logo Container -->
                        <div class="mb-8 animate-scale-in">
                            <div class="relative w-40 h-40 animate-float">
                                <img
                                    src="/benguetlogo.ico"
                                    alt="Benguet Logo"
                                    class="w-full h-full object-contain drop-shadow-lg"
                                />
                            </div>
                        </div>

                        <!-- System Name -->
                        <div class="text-center animate-fade-in-up">
                            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                                PBO | DocuTrack
                            </h2>
                            <p class="text-lg text-gray-600 dark:text-gray-400 font-medium">
                                Provincial Budget Office
                            </p>
                            <p class="text-sm text-gray-500 dark:text-gray-500 mt-4">
                                Document Tracking System
                            </p>
                        </div>

                        <!-- Decorative Elements -->
                        <div class="mt-12 flex gap-4 justify-center">
                            <div class="w-1 h-16 bg-gradient-to-b from-emerald-500 to-transparent rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes floatAnimation {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

.animate-slide-in-left {
    animation: slideInLeft 0.6s ease-out;
}

.animate-slide-in-right {
    animation: slideInRight 0.6s ease-out;
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

.animate-scale-in {
    animation: scaleIn 0.5s ease-out;
}

.animate-float {
    animation: floatAnimation 3s ease-in-out infinite;
}

.animate-fade-out {
    animation: fadeOut 0.6s ease-in forwards;
}

.login-input:focus {
    animation: none;
}
</style>
