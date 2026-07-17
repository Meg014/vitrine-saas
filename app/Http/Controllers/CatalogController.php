<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    private function page(Request $request, CurrentStore $current, string $component, array $parameters = []): View
    {
        return view('catalog.page', ['livewireComponent' => $component, 'parameters' => $parameters, 'currentStore' => $current->get(), 'stores' => $request->user()->stores()->orderBy('name')->get()]);
    }

    public function categories(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'catalog.category-manager');
    }

    public function attributes(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'catalog.attribute-manager');
    }

    public function products(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'catalog.product-index');
    }

    public function create(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'catalog.product-form');
    }

    public function edit(Request $r, CurrentStore $c, Product $product): View
    {
        $this->authorize('update', $product);

        return $this->page($r, $c, 'catalog.product-form', ['product' => $product]);
    }

    public function inventory(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'catalog.inventory-index');
    }

    public function movements(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'catalog.movement-index');
    }
}
