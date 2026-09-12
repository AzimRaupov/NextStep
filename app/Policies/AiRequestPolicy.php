<?php

namespace App\Policies;

use App\Models\AiRequest;
use App\Models\User;

class AiRequestPolicy
{
    public function update(User $user, AiRequest $aiRequest): bool
    {
        return $user->id === $aiRequest->user_id;
    }
}
