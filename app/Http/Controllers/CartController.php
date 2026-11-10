<?php

namespace App\Http\Controllers;

use App\Http\Requests\CartItemRequest;
use App\Http\Resources\CartItemResource;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cart)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return CartItemResource::collection($this->cart->items($request->user()));
    }

    public function put(CartItemRequest $request): AnonymousResourceCollection
    {
        return CartItemResource::collection($this->cart->put(
            $request->user(),
            $request->integer('product_id'),
            $request->integer('quantity'),
        ));
    }

    public function remove(Request $request, int $product): Response
    {
        $this->cart->remove($request->user(), $product);

        return response()->noContent();
    }

    public function clear(Request $request): Response
    {
        $this->cart->clear($request->user());

        return response()->noContent();
    }
}
