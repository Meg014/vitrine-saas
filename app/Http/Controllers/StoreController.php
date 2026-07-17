<?php

namespace App\Http\Controllers;

use App\Actions\CreateStore;
use App\Http\Requests\StoreStoreRequest;
use App\Models\Store;
use App\Support\CurrentStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function create(): View
    {
        return view('onboarding.store');
    }

    public function store(StoreStoreRequest $request, CreateStore $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return redirect()->route('dashboard')->with('status', 'Sua loja foi criada.');
    }

    public function select(Request $request, Store $store, CurrentStore $currentStore): RedirectResponse
    {
        $this->authorize('view', $store);
        $currentStore->set($store);

        return redirect()->route('dashboard')->with('status', "Loja alterada para {$store->name}.");
    }
}
