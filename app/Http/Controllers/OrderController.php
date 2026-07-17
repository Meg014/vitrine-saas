<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    private function page(Request $r, CurrentStore $c, string $component, array $parameters = []): View
    {
        return view('catalog.page', ['livewireComponent' => $component, 'parameters' => $parameters, 'currentStore' => $c->getOrFail(), 'stores' => $r->user()->stores()->orderBy('name')->get()]);
    }

    public function index(Request $r, CurrentStore $c): View
    {
        $this->authorize('viewAny', Order::class);

        return $this->page($r, $c, 'orders.order-index');
    }

    public function show(Request $r, CurrentStore $c, Order $order): View
    {
        $this->authorize('view', $order);

        return $this->page($r, $c, 'orders.order-details', compact('order'));
    }

    public function settings(Request $r, CurrentStore $c): View
    {
        return $this->page($r,$c,'orders.order-settings');
    }
}
