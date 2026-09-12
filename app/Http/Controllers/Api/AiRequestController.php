<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteAiRequestRequest;
use App\Models\AiRequest;
use Illuminate\Support\Facades\Gate;

class AiRequestController extends Controller
{
    public function show(AiRequest $aiRequest)
    {
        Gate::authorize('view', $aiRequest);

        return response()->json(['payload' => $aiRequest->payload]);
    }

    public function complete(CompleteAiRequestRequest $request, AiRequest $aiRequest)
    {
        abort_if($aiRequest->status !== 'pending', 409, 'Запрос уже обработан.');

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
