<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Request Mode
    |--------------------------------------------------------------------------
    |
    | "server": the backend calls the OpenAI API directly (default).
    | "client": the browser polls for pending requests, calls OpenAI itself,
    | and relays the raw response back to the backend.
    |
    */

    'request_mode' => env('AI_REQUEST_MODE', 'server'),

    /*
    |--------------------------------------------------------------------------
    | Client Relay Timeout
    |--------------------------------------------------------------------------
    |
    | How many seconds the backend waits for the browser to relay back the
    | OpenAI response when running in "client" mode, before giving up.
    |
    */

    'client_timeout' => (int) env('AI_CLIENT_TIMEOUT', 90),

];
