<?php

namespace App\Http\Middleware;

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Support\PublicStoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePublicStore
{
    public function __construct(private PublicStoreContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->route('store');
        if (! $store instanceof Store || $store->status !== StoreStatus::Active) {
            abort(404, 'Loja indisponível.');
        }$store->loadMissing('settings');
        $this->context->set($store);

        return $next($request);
    }
}
