// Client-relay mode ("AI_REQUEST_MODE=client" on the backend): the server
// cannot reach OpenAI itself, so it leaves the request in a table for this
// user's browser to pick up. The browser polls for pending requests over
// plain HTTP — no websocket/Reverb involved — and is a plain proxy: it makes
// the OpenAI call and posts the raw response back, nothing more.
//
// OpenAI's API never sends Access-Control-Allow-Origin on the actual
// response (only on the CORS preflight), so a plain browser fetch() to it
// is always blocked by CORS — this is true for every origin, not something
// our code can work around. Inside the Android app, native HTTP requests
// (via Capacitor's CapacitorHttp) go through the OS network stack instead
// of the WebView, so they are not subject to CORS at all. In a regular web
// browser there is no such escape hatch, so the relay simply cannot
// complete there; the request will keep failing until the browser polls it
// again or it times out server-side.
import { Capacitor, CapacitorHttp } from '@capacitor/core';
import api from './api';

const OPENAI_RESPONSES_URL = 'https://api.openai.com/v1/responses';
const CLIENT_API_KEY = import.meta.env.VITE_OPENAI_API_KEY;
const POLL_INTERVAL_MS = 3000;

let timer = null;

async function callOpenAi(payload) {
    const headers = {
        Authorization: `Bearer ${CLIENT_API_KEY}`,
        'Content-Type': 'application/json',
    };

    if (Capacitor.isNativePlatform()) {
        const response = await CapacitorHttp.post({ url: OPENAI_RESPONSES_URL, headers, data: payload });

        return { ok: response.status >= 200 && response.status < 300, status: response.status, data: response.data };
    }

    const response = await fetch(OPENAI_RESPONSES_URL, {
        method: 'POST',
        headers,
        body: JSON.stringify(payload),
    });

    return { ok: response.ok, status: response.status, data: await response.json() };
}

async function relay(aiRequest) {
    try {
        const { ok, status, data } = await callOpenAi(aiRequest.payload);

        if (!ok) {
            await api.post(`/api/ai-requests/${aiRequest.id}/complete`, {
                error: data?.error?.message ?? `OpenAI ответил с ошибкой ${status}.`,
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
