<?php

namespace App\Policies;

use App\Models\Context;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContextPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Context $context): bool
    {
        return $user->id === $context->user_id;
    }

    public function update(User $user, Context $context): bool
    {
        return $user->id === $context->user_id;
    }

    public function delete(User $user, Context $context): bool
    {
        return $user->id === $context->user_id;
    }
}