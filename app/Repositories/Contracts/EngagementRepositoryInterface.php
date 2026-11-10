<?php

namespace App\Repositories\Contracts;

use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EngagementRepositoryInterface
{
    public function reviews(int $productId): LengthAwarePaginator;

    public function review(int $id): Review;

    public function saveReview(int $userId, int $productId, array $data): Review;

    public function deleteReview(Review $review): void;

    public function favorites(int $userId): LengthAwarePaginator;

    public function favorite(int $userId, int $productId): void;

    public function unfavorite(int $userId, int $productId): void;

    public function notifications(User $user): LengthAwarePaginator;

    public function hasNotification(User $user, string $id): bool;

    public function notify(User $user, \Illuminate\Notifications\Notification $notification): void;
}
