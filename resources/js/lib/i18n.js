import { ref } from 'vue';
import { messages } from './messages';

const STORAGE_KEY = 'lms.locale';
const DEFAULT_LOCALE = 'ru';

export const locales = [
    { value: 'ru', label: 'Русский' },
    { value: 'en', label: 'English' },
    { value: 'tg', label: 'Тоҷикӣ' },
];

const supported = locales.map((option) => option.value);

function detectInitialLocale() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        if (stored && supported.includes(stored)) {
            return stored;
        }
    } catch {
        // localStorage can be unavailable in a locked-down webview — fall through to detection.
    }

    const browserLanguage = (navigator.language || '').slice(0, 2).toLowerCase();

    return supported.includes(browserLanguage) ? browserLanguage : DEFAULT_LOCALE;
}

// Module-scoped singleton so every component that imports `locale` shares the
// same reactive value, the same way a small Pinia store would work.
export const locale = ref(detectInitialLocale());

export function setLocale(next) {
    if (!supported.includes(next)) {
        return;
    }

    locale.value = next;

    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch {
        // Best effort only — an in-memory locale change still works for this session.
    }
}

function resolve(dict, path) {
    return path.split('.').reduce((acc, key) => (acc && typeof acc === 'object' ? acc[key] : undefined), dict);
}

function interpolate(template, params) {
    if (!params) {
        return template;
    }

    return template.replace(/\{(\w+)\}/g, (match, key) => (key in params ? params[key] : match));
}

export function t(path, params) {
    const template = resolve(messages[locale.value], path) ?? resolve(messages[DEFAULT_LOCALE], path);

    if (typeof template !== 'string') {
        return path;
    }

    return interpolate(template, params);
}

export function useI18n() {
    return { t, locale, setLocale, locales };
}
