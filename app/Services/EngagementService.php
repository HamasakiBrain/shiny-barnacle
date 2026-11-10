<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Review;
use App\Models\User;
use App\Repositories\Contracts\EngagementRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EngagementService
{
    public function __construct(
        private UserRepositoryInterface $users,
        private OrderRepositoryInterface $orders,
        private EngagementRepositoryInterface $engagement,
        private CatalogService $catalog,
    ) {
    }

    public function reviews(int $product): LengthAwarePaginator
    {
        $this->catalog->product($product);

        return $this->engagement->reviews($product);
    }

    public function review(User $user, int $product, array $data): Review
    {
        $this->catalog->product($product);

        return DB::transaction(function () use ($user, $product, $data) {
            $this->users->lock($user->id);

            if (!$this->orders->purchased($user->id, $product)) {
                throw new BusinessException('Only delivered purchases may be reviewed.');
            }

            return $this->engagement->saveReview($user->id, $product, $data);
        });
    }

    public function deleteReview(User $user, int $id): void
    {
        $review = $this->engagement->review($id);
        Gate::forUser($user)->authorize('delete', $review);
        $this->engagement->deleteReview($review);
    }

    public function favorites(User $user): LengthAwarePaginator
    {
        return $this->engagement->favorites($user->id);
    }

    public function favorite(User $user, int $product): void
    {
        $this->catalog->product($product);
        DB::transaction(function () use ($user, $product) {
            $this->users->lock($user->id);
            $this->engagement->favorite($user->id, $product);
        });
    }

    public function unfavorite(User $user, int $product): void
    {
        DB::transaction(function () use ($user, $product) {
            $this->users->lock($user->id);
            $this->engagement->unfavorite($user->id, $product);
        });
    }

    public function notifications(User $user): LengthAwarePaginator
    {
        return $this->engagement->notifications($user);
    }
}
