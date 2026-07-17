<?php

namespace App\Http\Middleware;

use App\Support\PublicStoreContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCustomer
{
    public function __construct(private PublicStoreContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer || $customer->store_id !== $this->context->getOrFail()->id) {
            return redirect()->route('store.login', $this->context->getOrFail());
        }

        return $next($request);
    }
}
