<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import SectionHeader from './SectionHeader.vue';
import { PROFILE_INPUT, PROFILE_LABEL, PROFILE_SAVE } from './profileStyles';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
// Lets users check what they typed before saving
const showPasswords = ref(false);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <SectionHeader icon="fa-key" title="Update Password" description="Use a long password that you don't use anywhere else." />

        <form class="space-y-5" @submit.prevent="updatePassword">
            <div>
                <label for="current_password" :class="PROFILE_LABEL">Current Password</label>
                <input id="current_password" ref="currentPasswordInput" v-model="form.current_password" :type="showPasswords ? 'text' : 'password'" autocomplete="current-password" :class="PROFILE_INPUT" />
                <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.current_password }}</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="password" :class="PROFILE_LABEL">New Password</label>
                    <input id="password" ref="passwordInput" v-model="form.password" :type="showPasswords ? 'text' : 'password'" autocomplete="new-password" :class="PROFILE_INPUT" />
                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
                </div>
                <div>
                    <label for="password_confirmation" :class="PROFILE_LABEL">Confirm Password</label>
                    <input id="password_confirmation" v-model="form.password_confirmation" :type="showPasswords ? 'text' : 'password'" autocomplete="new-password" :class="PROFILE_INPUT" />
                    <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.password_confirmation }}</p>
                </div>
            </div>

            <label class="inline-flex items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                <input v-model="showPasswords" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500" />
                Show passwords
            </label>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" :class="PROFILE_SAVE" :disabled="form.processing || !form.current_password || !form.password">
                    <i class="fas" :class="form.processing ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    {{ form.processing ? 'Saving...' : 'Save' }}
                </button>

                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-600 dark:text-green-400 font-medium flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
