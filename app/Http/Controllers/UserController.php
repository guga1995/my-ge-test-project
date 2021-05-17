<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserController extends Controller
{
    public function getUserCart(User $user)
    {
        $user->load('carts.product');

        $productGroups =  $user->productGroups()
            ->with('items')
            ->cursor();

        $cartProductIds = $user->carts->map->id;

        $discountedProductIds = collect([]);
        $discountedProductGroup = null;

        foreach ($productGroups as $productGroup) {
            $groupProductIds = $productGroup->items->map->id;

            if ($groupProductIds->every(function ($id) use ($cartProductIds) {
                $cartProductIds->contains($id);
            })) {
                $discountedProductIds = $groupProductIds;
                $discountedProductGroup = $productGroup;
                break;
            }
        }

        $carts = $user->carts;

        $discountedCarts = $discountedProductIds->map(function($id) use ($carts) {
            return $carts->where('product_id', $id)->first();
        });

        return response()->json([
            'products' => $carts->map(function ($cart) use ($discountedCarts, $discountedProductGroup) {
                return [
                    'product_id' => $cart->product_id,
                    'quantity' => $cart->quantity,
                    'price' => $this->getDiscountedPrice($cart, $discountedCarts, $discountedProductGroup),
                ];
            }),
        ]);
    }

    public function addProductToCart(Request $request, User $user)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $cart = $user->carts()
            ->where('product_id', $request->product_id)
            ->first();

        if ($cart) {
            $cart->update([
                'quantity' => $cart->quantity + 1
            ]);
        } else {
            $cart = Cart::query()->create([
                'product_id' => $request->product_id,
                'quantity' => 1
            ]);
        }

        return new JsonResource($user);
    }

    public function removeProductFromCart(Request $request, User $user)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $cart = $user->carts()
            ->where('product_id', $request->product_id)
            ->first();

        if ($cart) {

            if ($cart->quantity > 1) {
                $cart->update([
                    'quantity' => $cart->quantity - 1
                ]);
            } else {
                $cart->delete();
            }
        }

        return new JsonResource($user);
    }

    public function setCartProductQuantity(Request $request, User $user)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric'
        ]);

        $cart = $user->carts()
            ->where('product_id', $request->product_id)
            ->firstOrFail();

        $cart->update([
            'quantity' => $request->quantity
        ]);

        return new JsonResource($user);
    }

    private function getDiscountedPrice($cart, $discountedCarts, $discountedProductGroup) {
        if ($discountedCarts->map->product_id->contains($cart->product_id)) {
            $min = $discountedCarts->map->quantity->min();

            $price = $cart->product->price * $min;

            $discountedPrice = $price - ($price * $discountedProductGroup->discount / 100);

            $remainingPrice = $cart->product->price * ($cart->quantity - $min);

            return $discountedPrice + $remainingPrice;
        } else {
            return $cart->product->price * $cart->quantity;
        }
    }
}
