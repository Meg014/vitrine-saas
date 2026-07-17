<?php

namespace App\Http\Controllers;

use App\Support\CurrentStore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CurrentStore $currentStore): View
    {
        return view('dashboard', [
            'currentStore' => $currentStore->get(),
            'stores' => $request->user()->stores()->orderBy('name')->get(),
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
