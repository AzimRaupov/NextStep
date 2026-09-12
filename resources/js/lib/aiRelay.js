// Client-relay mode ("AI_REQUEST_MODE=client" on the backend): the server
// cannot reach OpenAI itself, so it broadcasts the request to this user's
// browser over Reverb. The browser is a plain proxy — it makes the OpenAI
// call and posts the raw response back, nothing more.
import api from './api';

const OPENAI_RESPONSES_URL = 'https://api.openai.com/v1/responses';
const CLIENT_API_KEY = import.meta.env.VITE_OPENAI_API_KEY;

let subscribedUserId = null;

async function relay(aiRequestId) {
    try {
        const { data: aiRequest } = await api.get(`/api/ai-requests/${aiRequestId}`);

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
            await api.post(`/api/ai-requests/${aiRequestId}/complete`, {
                error: data?.error?.message ?? `OpenAI ответил с ошибкой ${openaiResponse.status}.`,
            });

            return;
        }

        await api.post(`/api/ai-requests/${aiRequestId}/complete`, { response: data });
    } catch (error) {
        await api
            .post(`/api/ai-requests/${aiRequestId}/complete`, {
                error: error?.message ?? 'Не удалось выполнить запрос к OpenAI из браузера.',
            })
            .catch(() => {});
    }
}

export function startAiRelay(userId) {
    if (!window.Echo || subscribedUserId === userId) {
        return;
    }

    stopAiRelay();

    window.Echo.private(`ai-requests.${userId}`).listen('.ai.request.created', (event) => {
        relay(event.id);
    });

    subscribedUserId = userId;
}

export function stopAiRelay() {
    if (subscribedUserId !== null) {
        window.Echo?.leave(`ai-requests.${subscribedUserId}`);
    }

    subscribedUserId = null;
}
