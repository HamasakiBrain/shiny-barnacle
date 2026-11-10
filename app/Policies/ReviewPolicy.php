<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function delete(User $user, Review $review): bool
    {
        return $user->role === Role::Admin || $user->id === $review->user_id;
    }
}
