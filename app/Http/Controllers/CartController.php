<?php

namespace App\Http\Controllers;

use App\Http\Services\LoggerService;
use App\Models\Cart;
use App\Models\Order;
use App\Models\ProductSupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $cart = Cart::with('product_supplier.product')->where("user_id", Auth()->user()->id)
                                                        ->where("order_id", null)->orderBy('id', 'desc')->paginate(8);
        $total_price = 0;

        foreach ($cart as $item) {
           $total_price += $item->quantity * $item->product_supplier->value_un;
        }

        return view('cart.index', compact("cart", "total_price"));
    }

    public function store(Request $request)
    {

        $cart = Cart::with('product_supplier.product')->where("user_id", Auth()->user()->id)
                                                        ->where("order_id", null)->get();

        if($cart->count())
        {
            $total_price = 0;

            foreach ($cart as $item) {
               $total_price += $item->quantity * $item->product_supplier->value_un;

               $product = $item->product_supplier;

               $product->update([
                    'inventory' => $product->inventory - $item->quantity
               ]);
            }

            $order = Order::create([
                'total_price' => $total_price,
                'finished_by' => Auth::user()->id
            ]);
            $cart->map(function($item) use ($order){
                                $item->update([
                                    'order_id' => $order->id
                                ]);
                            });

            LoggerService::log('info', "ORDER CREATE", "Pedido [" . $order->id . "] realizado com sucesso.");

            return redirect()->back()->with("sucess", "Pedido realizado com sucesso!");
        }
        return redirect()->back()->with("message", "Não há produtos no carrinho.");

    }

    public function destroy($cart_id)
    {
        $cart = Cart::findOrFail($cart_id);

        $cart->delete();

        LoggerService::log('info', "CART DELETE", "Item [" . $cart->id . "] retirado do carrinho.");

        return redirect()->back()->with("success", "Item retidado do carrinho");
    }

    public function remove_item_from_order($cart_id)
    {
        $cart = Cart::findOrFail($cart_id);
        $order = $cart->order;

        $product_supplier = $cart->product_supplier;
        $product_supplier->update([
            'inventory' => $product_supplier->inventory + $cart->quantity
        ]);

        $order->update([
            "total_price" => $order->total_price - ($cart->quantity * $cart->product_supplier->value_un)
        ]);
        $cart->delete();

        LoggerService::log('info', "ORDER REMOVE ITEM", "Item [" . $cart->id . "] retirado do pedido.");

        return redirect()->back()->with("success", "Item retidado do carrinho");
    }
}
