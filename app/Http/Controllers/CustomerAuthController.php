<?php

namespace App\Http\Controllers;

use App\Actions\MergeCarts;
use App\Actions\ResolveCart;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    public function register(Request $r, Store $store, ResolveCart $resolve, MergeCarts $merge): RedirectResponse
    {
        $data = $r->validate(['name' => ['required', 'max:150'], 'email' => ['required', 'email', 'unique:customers,email,NULL,id,store_id,'.$store->id], 'phone' => ['nullable', 'max:30'], 'password' => ['required', 'confirmed', Password::min(8)], 'terms' => ['accepted'], 'accepts_marketing' => ['nullable', 'boolean']]);
        $guest = $resolve->handle($store);
        $customer = $store->customers()->create(['name' => $data['name'], 'email' => strtolower($data['email']), 'phone' => preg_replace('/\D+/', '', $data['phone'] ?? ''), 'password' => $data['password'], 'status' => CustomerStatus::Active, 'accepts_marketing' => (bool) ($data['accepts_marketing'] ?? false)]);
        Auth::guard('customer')->login($customer);
        $r->session()->regenerate();
        $merge->handle($guest, $customer);

        return redirect()->route('store.account', $store);
    }

    public function login(Request $r, Store $store, ResolveCart $resolve, MergeCarts $merge): RedirectResponse
    {
        $r->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        $customer = Customer::where('store_id', $store->id)->where('email', strtolower($r->email))->first();
        if (! $customer || $customer->status !== CustomerStatus::Active || ! Hash::check($r->password, $customer->password ?? '')) {
            return back()->withErrors(['email' => 'Não foi possível entrar com esses dados.'])->onlyInput('email');
        }$guest = $resolve->handle($store);
        Auth::guard('customer')->login($customer, $r->boolean('remember'));
        $r->session()->regenerate();
        $merge->handle($guest, $customer);

        return redirect()->intended(route('store.account', $store));
    }

    public function logout(Request $r, Store $store): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $r->session()->regenerateToken();

        return redirect()->route('store.home', $store);
    }
}
