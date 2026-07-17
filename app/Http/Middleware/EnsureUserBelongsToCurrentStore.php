<?php

namespace App\Http\Middleware;

use App\Support\CurrentStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToCurrentStore
{
    public function __construct(private readonly CurrentStore $currentStore) {}

    public function handle(Request $request, Closure $next): Response
    {
        $store = $this->currentStore->get();

        if (! $store || ! $request->user()->can('view', $store)) {
            $this->currentStore->forget();

            return redirect()->route('onboarding.store');
        }

        return $next($request);
    }
}
