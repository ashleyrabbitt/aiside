<?php

namespace App\Policies;

use App\Models\BusinessIdea;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessIdeaPolicy
{
    use HandlesAuthorization;

    public function view(User $user, BusinessIdea $businessIdea): bool
    {
        return $user->id === $businessIdea->user_id;
    }

    public function update(User $user, BusinessIdea $businessIdea): bool
    {
        return $user->id === $businessIdea->user_id;
    }

    public function delete(User $user, BusinessIdea $businessIdea): bool
    {
        return $user->id === $businessIdea->user_id;
    }
}