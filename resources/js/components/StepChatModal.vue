<script setup>
import { nextTick, onMounted, ref } from 'vue';
import { useCoursesStore } from '../stores/courses';
import { t } from '../lib/i18n';

const props = defineProps({
    courseId: { type: [String, Number], required: true },
    step: { type: Object, required: true },
});

const emit = defineEmits(['close']);

const courses = useCoursesStore();
const loading = ref(true);
const sending = ref(false);
const messages = ref([]);
const draft = ref('');
const error = ref('');
const scrollEl = ref(null);

onMounted(async () => {
    messages.value = await courses.fetchStepChat(props.courseId, props.step.id);
    loading.value = false;
    scrollToBottom();
});

function scrollToBottom() {
    nextTick(() => {
        if (scrollEl.value) {
            scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
        }
    });
}

async function send() {
    const text = draft.value.trim();

    if (!text || sending.value) {
        return;
    }

    error.value = '';
    sending.value = true;
    draft.value = '';

    try {
        const newMessages = await courses.sendStepChatMessage(props.courseId, props.step.id, text);
        messages.value.push(...newMessages);
        scrollToBottom();
    } catch {
        error.value = t('chat.send_error');
        draft.value = text;
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-black/30 sm:items-center" @click.self="$emit('close')">
        <div class="flex h-[85vh] w-full max-w-lg flex-col rounded-t-2xl bg-white sm:h-[70vh] sm:rounded-2xl">
            <div class="flex items-center justify-between border-b border-neutral-100 px-5 py-4">
                <div>
                    <p class="text-xs font-medium text-neutral-400 uppercase">{{ t('chat.header_label') }}</p>
                    <p class="text-sm font-medium text-neutral-900">{{ step.title }}</p>
                </div>
                <button class="text-neutral-400 hover:text-neutral-900" @click="$emit('close')">✕</button>
            </div>

            <div ref="scrollEl" class="flex-1 space-y-3 overflow-y-auto px-5 py-4">
                <p v-if="loading" class="text-sm text-neutral-400">{{ t('chat.loading') }}</p>

                <template v-else>
                    <p v-if="messages.length === 0" class="text-sm text-neutral-400">
                        {{ t('chat.empty_hint') }}
                    </p>

                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex"
                        :class="message.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <p
                            class="max-w-[80%] whitespace-pre-wrap rounded-2xl px-3.5 py-2 text-sm"
                            :class="
                                message.role === 'user'
                                    ? 'bg-neutral-900 text-white'
                                    : 'bg-neutral-100 text-neutral-800'
                            "
                        >
                            {{ message.content }}
                        </p>
                    </div>

                    <div v-if="sending" class="flex justify-start">
                        <p class="rounded-2xl bg-neutral-100 px-3.5 py-2 text-sm text-neutral-400">{{ t('chat.typing') }}</p>
                    </div>
                </template>
            </div>

            <div class="border-t border-neutral-100 px-4 py-3">
                <p v-if="error" class="mb-2 text-xs text-red-500">{{ error }}</p>
                <form class="flex gap-2" @submit.prevent="send">
                    <input
                        v-model="draft"
                        type="text"
                        :placeholder="t('chat.input_placeholder')"
                        :disabled="loading"
                        class="w-full rounded-lg border border-neutral-200 px-3.5 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 focus:border-neutral-900 focus:outline-none focus:ring-1 focus:ring-neutral-900 sm:text-sm"
                    />
                    <button
                        type="submit"
                        :disabled="sending || loading"
                        class="shrink-0 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800 disabled:opacity-50"
                    >
                        →
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
