<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\ReviewResource;
use App\Services\EngagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class EngagementController extends Controller
{
    public function __construct(private EngagementService $engagement)
    {
    }

    public function reviews(int $product): AnonymousResourceCollection
    {
        return ReviewResource::collection($this->engagement->reviews($product));
    }

    public function review(ReviewRequest $request, int $product): JsonResponse
    {
        return (new ReviewResource($this->engagement->review($request->user(), $product, $request->validated())))
            ->response()
            ->setStatusCode(200);
    }

    public function deleteReview(Request $request, int $review): Response
    {
        $this->engagement->deleteReview($request->user(), $review);

        return response()->noContent();
    }

    public function favorites(Request $request): AnonymousResourceCollection
    {
        return FavoriteResource::collection($this->engagement->favorites($request->user()));
    }

    public function favorite(Request $request, int $product): Response
    {
        $this->engagement->favorite($request->user(), $product);

        return response()->noContent();
    }

    public function unfavorite(Request $request, int $product): Response
    {
        $this->engagement->unfavorite($request->user(), $product);

        return response()->noContent();
    }

    public function notifications(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection($this->engagement->notifications($request->user()));
    }
}
