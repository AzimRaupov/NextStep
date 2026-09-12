// Client-relay mode ("AI_REQUEST_MODE=client" on the backend): the server
// cannot reach OpenAI itself, so it leaves the request in a table for this
// user's browser to pick up. The browser polls for pending requests over
// plain HTTP — no websocket/Reverb involved — and is a plain proxy: it makes
// the OpenAI call and posts the raw response back, nothing more.
import api from './api';

const OPENAI_RESPONSES_URL = 'https://api.openai.com/v1/responses';
const CLIENT_API_KEY = import.meta.env.VITE_OPENAI_API_KEY;
const POLL_INTERVAL_MS = 3000;

let timer = null;

async function relay(aiRequest) {
    try {
        const openaiResponse = await fetch(OPENAI_RESPONSES_URL, {
            method: 'POST',
            headers: {
                Authorization: `Bearer ${CLIENT_API_KEY}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(aiRequest.payload),
        });

        const data = await openaiResponse.json();

        if (!openaiResponse.ok) {
            await api.post(`/api/ai-requests/${aiRequest.id}/complete`, {
                error: data?.error?.message ?? `OpenAI ответил с ошибкой ${openaiResponse.status}.`,
            });

            return;
        }

        await api.post(`/api/ai-requests/${aiRequest.id}/complete`, { response: data });
    } catch (error) {
        await api
            .post(`/api/ai-requests/${aiRequest.id}/complete`, {
                error: error?.message ?? 'Не удалось выполнить запрос к OpenAI из браузера.',
            })
            .catch(() => {});
    }
}

async function poll() {
    try {
        const { data } = await api.get('/api/ai-requests/pending');

        data.data.forEach(relay);
    } catch {
        // Ignore a transient network error; the next tick tries again.
    }
}

export function startAiRelay() {
    if (timer !== null) {
        return;
    }

    timer = setInterval(poll, POLL_INTERVAL_MS);
    poll();
}

export function stopAiRelay() {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}
