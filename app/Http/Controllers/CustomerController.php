<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    private function page(Request $r, CurrentStore $c, string $livewire, array $parameters = []): View
    {
        return view('catalog.page', ['livewireComponent' => $livewire, 'parameters' => $parameters, 'currentStore' => $c->get(), 'stores' => $r->user()->stores()->orderBy('name')->get()]);
    }

    public function index(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'customers.customer-index');
    }

    public function create(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'customers.customer-form');
    }

    public function show(Request $r, CurrentStore $c, Customer $customer): View
    {
        $this->authorize('view', $customer);

        return $this->page($r, $c, 'customers.customer-details', ['customer' => $customer]);
    }

    public function edit(Request $r, CurrentStore $c, Customer $customer): View
    {
        $this->authorize('update', $customer);

        return $this->page($r, $c, 'customers.customer-form', ['customer' => $customer]);
    }

    public function messages(Request $r, CurrentStore $c): View
    {
        return $this->page($r, $c, 'messages.message-index');
    }

    public function message(Request $r, CurrentStore $c, CustomerMessage $customerMessage): View
    {
        $this->authorize('view', $customerMessage);
        if (! $customerMessage->read_at) {
            $customerMessage->update(['read_at' => now()]);
        }

        return $this->page($r, $c, 'messages.message-details', ['customerMessage' => $customerMessage]);
    }
}
