<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import SectionHeader from './SectionHeader.vue';
import { PROFILE_INPUT, PROFILE_LABEL, PROFILE_SAVE } from './profileStyles';

const user = (usePage().props.auth as any).user;

const form = useForm({
    name: user.name as string,
    username: user.username as string,
});
</script>

<template>
    <section>
        <SectionHeader icon="fa-user-pen" title="Profile Information" description="Your name as it shows in the system, and the username you sign in with." />

        <form class="space-y-5" @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
            <div>
                <label for="profile_name" :class="PROFILE_LABEL">Name</label>
                <input id="profile_name" v-model="form.name" type="text" required autocomplete="name" maxlength="255" :class="PROFILE_INPUT" />
                <p v-if="form.errors.name" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="profile_username" :class="PROFILE_LABEL">Username</label>
                <input id="profile_username" v-model="form.username" type="text" required autocomplete="username" maxlength="255" :class="PROFILE_INPUT" />
                <p v-if="form.errors.username" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ form.errors.username }}</p>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <button type="submit" :class="PROFILE_SAVE" :disabled="form.processing || !form.isDirty">
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
