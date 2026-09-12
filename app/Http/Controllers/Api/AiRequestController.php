<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteAiRequestRequest;
use App\Models\AiRequest;
use Illuminate\Http\Request;

class AiRequestController extends Controller
{
    /**
     * The browser polls this endpoint instead of listening for a push
     * notification. Every pending request for the user is claimed
     * (marked "processing") in the same call, so a second poll tick
     * before the browser finishes relaying it does not pick it up again.
     */
    public function pending(Request $request)
    {
        $aiRequests = AiRequest::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->get();

        AiRequest::whereIn('id', $aiRequests->pluck('id'))->update(['status' => 'processing']);

        return response()->json([
            'data' => $aiRequests->map(fn (AiRequest $aiRequest) => [
                'id' => $aiRequest->id,
                'payload' => $aiRequest->payload,
            ]),
        ]);
    }

    public function complete(CompleteAiRequestRequest $request, AiRequest $aiRequest)
    {
        abort_if(in_array($aiRequest->status, ['completed', 'failed'], true), 409, 'Запрос уже обработан.');

        if ($request->filled('error')) {
            $aiRequest->update([
                'status' => 'failed',
                'error' => $request->validated('error'),
            ]);
        } else {
            $aiRequest->update([
                'status' => 'completed',
                'response' => $request->validated('response'),
            ]);
        }

        return response()->json(['status' => $aiRequest->status]);
    }
}
