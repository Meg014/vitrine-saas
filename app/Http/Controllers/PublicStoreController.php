<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\View\View;

class PublicStoreController extends Controller
{
    private function page(Store $store, string $component, array $parameters = []): View
    {
        return view('store.page', ['store' => $store, 'livewireComponent' => $component, 'parameters' => $parameters]);
    }

    public function home(Store $store): View
    {
        return $this->page($store, 'storefront.home');
    }

    public function catalog(Store $store): View
    {
        return $this->page($store, 'storefront.catalog');
    }

    public function product(Store $store, Product $product): View
    {
        abort_unless($product->store_id === $store->id && $product->status->value === 'active' && $product->published_at?->isPast(), 404);

        return $this->page($store, 'storefront.product-show', compact('product'));
    }

    public function category(Store $store, Category $category): View
    {
        abort_unless($category->store_id === $store->id, 404);

        return $this->page($store, 'storefront.catalog', ['categoryId' => $category->id]);
    }

    public function cart(Store $store): View
    {
        return $this->page($store, 'storefront.cart-page');
    }

    public function login(Store $store): View
    {
        return view('store.auth.login', compact('store'));
    }

    public function register(Store $store): View
    {
        return view('store.auth.register', compact('store'));
    }

    public function account(Store $store): View
    {
        return $this->page($store, 'storefront.account');
    }

    public function addresses(Store $store): View
    {
        return $this->page($store, 'storefront.addresses');
    }

    public function contact(Store $store): View
    {
        return $this->page($store, 'storefront.contact-form');
    }

    public function checkout(Store $store): View
    {
        return $this->page($store, 'storefront.checkout');
    }

    public function orders(Store $store): View
    {
        return $this->page($store, 'storefront.customer-orders');
    }

    public function order(Store $store, Order $order): View
    {
        return $this->page($store, 'storefront.customer-order-details', compact('order'));
    }

    public function orderSuccess(Store $store, Order $order): View
    {
        abort_unless($order->store_id === $store->id && $order->customer_id === auth('customer')->id(), 404);

        return $this->page($store, 'storefront.customer-order-details', compact('order'));
    }
}
