import { locale as globalLocale, t } from './i18n';

function ruDayWord(n) {
    const mod10 = n % 10;
    const mod100 = n % 100;

    if (mod100 >= 11 && mod100 <= 14) {
        return 'дней';
    }

    if (mod10 === 1) {
        return 'день';
    }

    if (mod10 >= 2 && mod10 <= 4) {
        return 'дня';
    }

    return 'дней';
}

const DAY_FORMATTERS = {
    ru: (n) => `${n} ${ruDayWord(n)}`,
    en: (n) => `${n} day${n === 1 ? '' : 's'}`,
    // Tajik, like Persian, keeps the noun singular after a numeral.
    tg: (n) => `${n} рӯз`,
};

export function formatDays(days, locale = globalLocale.value) {
    const abs = Math.round(Math.abs(days));
    const format = DAY_FORMATTERS[locale] ?? DAY_FORMATTERS.ru;

    return format(abs);
}

const MONTHS = {
    ru: ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'],
    en: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
    tg: ['январ', 'феврал', 'март', 'апрел', 'май', 'июн', 'июл', 'август', 'сентябр', 'октябр', 'ноябр', 'декабр'],
};

// Hand-rolled instead of Intl.DateTimeFormat: a WebView's bundled ICU data
// isn't guaranteed to cover the "tg" locale, so this stays correct everywhere.
export function formatDeadline(deadlineDate, locale = globalLocale.value) {
    if (!deadlineDate) {
        return null;
    }

    const date = new Date(`${deadlineDate}T00:00:00`);
    const months = MONTHS[locale] ?? MONTHS.ru;

    return `${date.getDate()} ${months[date.getMonth()]}`;
}

/**
 * Turns a course/step pace object into a `{ tone, text }` pair for display.
 * Kept separate from the raw pace numbers so every caller renders the same
 * wording, translated through the active locale.
 */
export function describePace(pace) {
    if (!pace) {
        return null;
    }

    if (pace.current_step_status === 'behind') {
        return { tone: 'behind', text: t('pace.current_behind', { days: formatDays(pace.current_step_delta_days) }) };
    }

    if (pace.completed_status === 'behind') {
        return { tone: 'behind', text: t('pace.completed_behind', { days: formatDays(pace.completed_delta_days) }) };
    }

    if (pace.completed_status === 'ahead') {
        return { tone: 'ahead', text: t('pace.completed_ahead') };
    }

    if (pace.current_step_status === 'on_time' || pace.completed_status === 'on_time') {
        return { tone: 'on_time', text: t('pace.on_time') };
    }

    return null;
}
