<?php

namespace App\Http\Controllers;

use App\DTOs\CheckoutData;
use App\Enums\OrderStatus;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\MockPaymentRequest;
use App\Http\Requests\OrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\CheckoutService;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private CheckoutService $checkout,
        private OrderService $orders,
        private PaymentService $payments,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->listing($request->user()));
    }

    public function adminIndex(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->listing($request->user(), true));
    }

    public function show(Request $request, int $order): OrderResource
    {
        return new OrderResource($this->orders->get($request->user(), $order));
    }

    public function store(CheckoutRequest $request): JsonResponse
    {
        return (new OrderResource($this->checkout->checkout(
            $request->user(),
            CheckoutData::fromArray($request->validated()),
        )))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(Request $request, int $order): OrderResource
    {
        return new OrderResource($this->orders->cancel($request->user(), $order));
    }

    public function status(OrderStatusRequest $request, int $order): OrderResource
    {
        return new OrderResource($this->orders->transition(
            $request->user(),
            $order,
            OrderStatus::from($request->validated('status')),
        ));
    }

    public function pay(MockPaymentRequest $request, int $order): OrderResource
    {
        return new OrderResource($this->payments->mock($request->user(), $order, $request->validated('outcome')));
    }
}
