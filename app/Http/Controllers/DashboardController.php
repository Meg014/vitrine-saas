<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Order;
use App\Models\Product;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CurrentStore $currentStore): View
    {
        $store = $currentStore->getOrFail();

        return view('dashboard', [
            'currentStore' => $store,
            'stores' => $request->user()->stores()->orderBy('name')->get(),
            'realMetrics' => [
                'activeCustomers' => Customer::where('store_id', $store->id)->where('status', 'active')->count(),
                'newCustomers' => Customer::where('store_id', $store->id)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'newMessages' => CustomerMessage::where('store_id', $store->id)->where('status', 'new')->count(),
                'activeProducts' => Product::where('store_id', $store->id)->where('status', 'active')->count(),
                'orders' => Order::where('store_id', $store->id)->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'sales' => Order::where('store_id', $store->id)->where('status', '!=', 'canceled')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total'),
            ],
        ]);
    }

    public function page(Request $request, CurrentStore $currentStore, string $page): View
    {
        return view('module', [
            'page' => $page,
            'currentStore' => $currentStore->get(),
            'stores' => $request->user()->stores()->orderBy('name')->get(),
        ]);
    }
}
