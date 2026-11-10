<?php

namespace App\Repositories\Eloquent;

use App\Models\Favorite;
use App\Models\Review;
use App\Models\User;
use App\Repositories\Contracts\EngagementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EngagementRepository implements EngagementRepositoryInterface
{
    public function reviews(int $productId): LengthAwarePaginator
    {
        return Review::with('user:id,name')
            ->where('product_id', $productId)
            ->latest('id')
            ->paginate(20);
    }

    public function review(int $id): Review
    {
        return Review::findOrFail($id);
    }

    public function saveReview(int $userId, int $productId, array $data): Review
    {
        return Review::updateOrCreate([
            'user_id' => $userId,
            'product_id' => $productId,
        ], $data)
            ->load('user:id,name');
    }

    public function deleteReview(Review $review): void
    {
        $review->delete();
    }

    public function favorites(int $userId): LengthAwarePaginator
    {
        return Favorite::with('product.category')
            ->where('user_id', $userId)
            ->latest('id')
            ->paginate(20);
    }

    public function favorite(int $userId, int $productId): void
    {
        Favorite::firstOrCreate([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    public function unfavorite(int $userId, int $productId): void
    {
        Favorite::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    }

    public function notifications(User $user): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate(20);
    }

    public function hasNotification(User $user, string $id): bool
    {
        return $user->notifications()
            ->whereKey($id)
            ->exists();
    }

    public function notify(User $user, \Illuminate\Notifications\Notification $notification): void
    {
        $user->notifyNow($notification);
    }
}
