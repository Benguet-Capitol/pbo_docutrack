<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

// Navbar search (as in pbo-registryV2) across Documents, Records, Leaves, Travel Orders, Pass Slips, Time Slips, Tardiness
// and Certificates of Appearance. Press "/" (or Ctrl+K) to jump here; arrows pick a result, Enter opens it.
// The server only returns what the user may open.

interface SearchItem {
    title: string;
    subtitle: string;
    url: string;
}

interface SearchGroup {
    label: string;
    icon: string;
    items: SearchItem[];
}

const query = ref('');
const groups = ref<SearchGroup[]>([]);
const loading = ref(false);
const open = ref(false);
const focused = ref(false);
const mobileOpen = ref(false);
const active = ref(0);
const input = ref<HTMLInputElement | null>(null);
const list = ref<HTMLElement | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;
let latest = 0;

// What can be searched, shown while the box is empty so users know what to type
const SEARCHABLE = [
    { label: 'Tracking / control no.', icon: 'fa-hashtag' },
    { label: 'Employee', icon: 'fa-user' },
    { label: 'Destination / location', icon: 'fa-map-marker-alt' },
    { label: 'Particulars / title', icon: 'fa-file-alt' },
];

const isMac = typeof navigator !== 'undefined' && /Mac/i.test(navigator.platform);
const flat = computed(() => groups.value.flatMap((group) => group.items));
const ready = computed(() => query.value.trim().length >= 2);
const total = computed(() => flat.value.length);

watch(query, (value) => {
    clearTimeout(timer);
    if (value.trim().length < 2) {
        groups.value = [];
        loading.value = false;
        return;
    }
    loading.value = true;
    timer = setTimeout(async () => {
        const ticket = ++latest;
        try {
            const { data } = await axios.get(route('search'), { params: { q: value } });
            // An older, slower answer never replaces a newer one
            if (ticket === latest) {
                groups.value = data.groups;
                active.value = 0;
            }
        } catch {
            if (ticket === latest) groups.value = [];
        } finally {
            if (ticket === latest) loading.value = false;
        }
    }, 250);
});

const go = (item: SearchItem | undefined) => {
    if (!item) return;
    open.value = false;
    mobileOpen.value = false;
    query.value = '';
    router.visit(item.url);
};

const scrollActiveIntoView = () => {
    nextTick(() => list.value?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
};

const onKeydown = (event: KeyboardEvent) => {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        active.value = Math.min(active.value + 1, total.value - 1);
        scrollActiveIntoView();
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value = Math.max(active.value - 1, 0);
        scrollActiveIntoView();
    } else if (event.key === 'Enter') {
        event.preventDefault();
        go(flat.value[active.value]);
    } else if (event.key === 'Escape') {
        open.value = false;
        input.value?.blur();
    }
};

// An item's place across all groups: the highlighted row and the staggered fade-in
const indexOf = (item: SearchItem) => flat.value.indexOf(item);

// The typed text marked inside a result, without v-html
const highlight = (text: string) => {
    const q = query.value.trim();
    if (!q || !text) return [{ text: text ?? '', match: false }];
    const parts: { text: string; match: boolean }[] = [];
    const lower = text.toLowerCase();
    const needle = q.toLowerCase();
    let from = 0;
    let at = lower.indexOf(needle);
    while (at !== -1) {
        if (at > from) parts.push({ text: text.slice(from, at), match: false });
        parts.push({ text: text.slice(at, at + q.length), match: true });
        from = at + q.length;
        at = lower.indexOf(needle, from);
    }
    if (from < text.length) parts.push({ text: text.slice(from), match: false });
    return parts;
};

const onFocus = () => {
    focused.value = true;
    open.value = true;
};

// Closes after a click on a result has registered
const onBlur = () => {
    setTimeout(() => {
        focused.value = false;
        open.value = false;
        if (!query.value) mobileOpen.value = false;
    }, 150);
};

const clear = () => {
    query.value = '';
    input.value?.focus();
};

const focusSearch = () => {
    mobileOpen.value = true;
    nextTick(() => input.value?.focus());
};

const onGlobalKey = (event: KeyboardEvent) => {
    const element = document.activeElement as HTMLElement | null;
    const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(element?.tagName ?? '') || !!element?.isContentEditable;
    if ((event.key === '/' && !typing) || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k')) {
        event.preventDefault();
        focusSearch();
    }
};

onMounted(() => window.addEventListener('keydown', onGlobalKey));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onGlobalKey);
    clearTimeout(timer);
});
</script>

<template>
    <div class="relative">
        <!-- Phones: a labelled button that opens the box below the bar -->
        <button
            type="button"
            class="sm:hidden inline-flex items-center gap-2 h-9 px-3 rounded-md border border-gray-300 dark:border-gray-600 text-xs text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:border-emerald-500 transition-colors"
            aria-label="Search records"
            @click="focusSearch"
        >
            <i class="fas fa-search text-emerald-600 dark:text-emerald-400"></i> Search
        </button>

        <!-- Phones: slides down under the bar when opened; wider screens: always in the bar -->
        <div
            class="sm:block sm:static"
            :class="mobileOpen ? 'search-panel-in fixed inset-x-0 top-16 z-[15] bg-white dark:bg-gray-800 p-3 shadow-md border-b border-gray-200 dark:border-gray-700 sm:p-0 sm:shadow-none sm:border-0 sm:bg-transparent' : 'hidden'"
        >
            <!-- The box: widens and takes an emerald outline while in use -->
            <div
                class="search-field relative flex items-center rounded-md border bg-white dark:bg-gray-800 w-full"
                :class="focused
                    ? 'sm:w-80 md:w-96 xl:w-[34rem] border-emerald-500 ring-4 ring-emerald-500/15 shadow-sm'
                    : 'sm:w-64 md:w-80 xl:w-[26rem] border-gray-300 dark:border-gray-600 hover:border-emerald-500/60'"
            >
                <i class="fas fa-search ml-3 mr-1.5 text-sm text-emerald-600 dark:text-emerald-400 transition-transform duration-300" :class="focused ? 'scale-110' : ''"></i>
                <input
                    ref="input"
                    v-model="query"
                    type="text"
                    autocomplete="off"
                    spellcheck="false"
                    aria-label="Search records"
                    placeholder="Search — control no., employee, place, particulars…"
                    class="flex-1 min-w-0 bg-transparent border-0 focus:ring-0 focus:outline-none py-2 pl-2.5 pr-2 text-xs text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400"
                    @focus="onFocus"
                    @blur="onBlur"
                    @keydown="onKeydown"
                />
                <!-- Shortcut hints at rest; a clear button once something is typed -->
                <Transition name="fade" mode="out-in">
                    <button
                        v-if="query"
                        key="clear"
                        type="button"
                        class="mr-2 w-6 h-6 inline-flex items-center justify-center rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-700"
                        aria-label="Clear search"
                        @mousedown.prevent="clear"
                    >
                        <i class="fas fa-xmark text-xs"></i>
                    </button>
                    <span v-else key="keys" class="hidden sm:flex items-center gap-1 mr-2.5 text-[10px] text-gray-400 dark:text-gray-500">
                        <kbd class="kbd">/</kbd>
                        <span>or</span>
                        <kbd class="kbd">{{ isMac ? '⌘' : 'Ctrl' }} K</kbd>
                    </span>
                </Transition>
                <!-- A thin line runs along the bottom while results load -->
                <span v-if="loading" class="search-progress absolute inset-x-0 bottom-0 h-0.5 overflow-hidden rounded-b-md">
                    <span class="block h-full w-1/3 bg-emerald-500"></span>
                </span>
            </div>

            <!-- Results -->
            <Transition name="dropdown">
                <div
                    v-if="open"
                    class="absolute left-0 right-0 sm:right-auto mt-2 sm:w-[min(34rem,calc(100vw-2rem))] rounded-md border border-gray-200 border-t-2 border-t-emerald-500 bg-white shadow-xl dark:border-gray-700 dark:border-t-emerald-500 dark:bg-gray-800 z-[15] origin-top"
                >
                    <!-- Empty box: what can be searched -->
                    <div v-if="!ready" class="px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Search by</p>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="(type, i) in SEARCHABLE"
                                :key="type.label"
                                class="result-row inline-flex items-center gap-1.5 px-2 py-1 rounded border border-gray-200 dark:border-gray-600 text-[11px] text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-700/50"
                                :style="{ '--i': i }"
                            >
                                <i class="fas text-[10px] text-emerald-600 dark:text-emerald-400" :class="type.icon"></i>{{ type.label }}
                            </span>
                        </div>
                        <p class="mt-2.5 text-[11px] text-gray-500 dark:text-gray-400">Type at least 2 characters. Only the modules you can open are searched.</p>
                    </div>

                    <template v-else>
                        <div ref="list" class="max-h-[65vh] overflow-y-auto py-1">
                            <p v-if="!loading && !groups.length" class="px-4 py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-magnifying-glass text-lg text-gray-300 dark:text-gray-600 block mb-2"></i>
                                No records match “{{ query.trim() }}”.
                            </p>
                            <p v-else-if="loading && !groups.length" class="px-4 py-8 text-center text-xs text-gray-400 dark:text-gray-500">Searching…</p>

                            <div v-for="group in groups" :key="group.label" class="pb-1">
                                <p class="flex items-center gap-1.5 px-4 pt-2.5 pb-1 text-[10px] font-bold uppercase tracking-widest text-gray-500 dark:text-gray-400">
                                    <i class="fas text-emerald-600 dark:text-emerald-400" :class="group.icon"></i>{{ group.label }}
                                    <span class="ml-auto font-semibold normal-case tracking-normal text-gray-400">{{ group.items.length }}</span>
                                </p>
                                <button
                                    v-for="item in group.items"
                                    :key="item.url"
                                    type="button"
                                    class="result-row w-full flex items-center gap-3 text-left pl-3.5 pr-4 py-2 text-xs border-l-[3px] transition-colors duration-150"
                                    :class="indexOf(item) === active
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-gray-700'
                                        : 'border-transparent hover:bg-gray-50 dark:hover:bg-gray-700'"
                                    :style="{ '--i': indexOf(item) }"
                                    :data-active="indexOf(item) === active"
                                    @mousedown.prevent="go(item)"
                                    @mouseenter="active = indexOf(item)"
                                >
                                    <span class="min-w-0 flex-1">
                                        <span class="block font-semibold text-gray-800 dark:text-gray-100 truncate">
                                            <template v-for="(part, p) in highlight(item.title)" :key="p">
                                                <mark v-if="part.match" class="bg-emerald-100 dark:bg-emerald-900/60 text-inherit rounded-sm px-px">{{ part.text }}</mark>
                                                <template v-else>{{ part.text }}</template>
                                            </template>
                                        </span>
                                        <span class="block text-[11px] text-gray-500 dark:text-gray-400 truncate">{{ item.subtitle }}</span>
                                    </span>
                                    <i
                                        class="fas fa-arrow-right text-[10px] text-emerald-600 dark:text-emerald-400 transition-all duration-200"
                                        :class="indexOf(item) === active ? 'opacity-100 translate-x-0' : 'opacity-0 -translate-x-1'"
                                    ></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 px-4 py-2 border-t border-gray-100 dark:border-gray-700 text-[10px] text-gray-400 dark:text-gray-500">
                            <span class="hidden sm:inline-flex items-center gap-2">
                                <span><kbd class="kbd">↑</kbd> <kbd class="kbd">↓</kbd> to move</span>
                                <span><kbd class="kbd">Enter</kbd> to open</span>
                                <span><kbd class="kbd">Esc</kbd> to close</span>
                            </span>
                            <span v-if="total" class="ml-auto">{{ total }} result(s)</span>
                        </div>
                    </template>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.kbd {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.3rem;
    min-width: 1.1rem;
    height: 1.1rem;
    border: 1px solid rgb(209 213 219);
    border-bottom-width: 2px;
    border-radius: 0.25rem;
    font-family: inherit;
    font-size: 10px;
    line-height: 1;
    color: rgb(107 114 128);
    background: rgb(249 250 251);
}
:global(.dark) .kbd {
    border-color: rgb(75 85 99);
    color: rgb(156 163 175);
    background: rgb(55 65 81);
}

/* The box widens smoothly when focused */
.search-field {
    transition: width 300ms cubic-bezier(0.2, 0, 0, 1), border-color 200ms ease, box-shadow 200ms ease;
}

/* Results drop in: a short fade and slide */
.dropdown-enter-active {
    transition: opacity 160ms ease-out, transform 160ms ease-out;
}
.dropdown-leave-active {
    transition: opacity 100ms ease-in, transform 100ms ease-in;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.99);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 120ms ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

/* Phones: the search panel slides down from the bar */
.search-panel-in {
    animation: panel-in 160ms ease-out both;
}
@keyframes panel-in {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: none; }
}

/* Rows appear one after another (capped, so long lists don't lag) */
.result-row {
    animation: result-in 220ms cubic-bezier(0.2, 0, 0, 1) both;
    animation-delay: calc(min(var(--i, 0), 10) * 22ms);
}
@keyframes result-in {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: none; }
}

/* Loading: a segment sweeps along the bottom of the box */
.search-progress > span {
    animation: search-sweep 1s ease-in-out infinite;
}
@keyframes search-sweep {
    from { transform: translateX(-100%); }
    to { transform: translateX(300%); }
}

/* Respect "reduce motion": everything appears at once */
@media (prefers-reduced-motion: reduce) {
    .search-field,
    .dropdown-enter-active,
    .dropdown-leave-active,
    .fade-enter-active,
    .fade-leave-active {
        transition: none;
    }
    .result-row,
    .search-panel-in,
    .search-progress > span {
        animation: none;
    }
}
</style>
