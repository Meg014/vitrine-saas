<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $title ?? 'Painel' }} — Vitrine</title>@vite(['resources/css/app.css','resources/js/app.js']) @livewireStyles</head>
<body x-data="{ menu: false, userMenu: false }" class="overflow-x-hidden">
<div class="min-h-screen lg:flex">
    <div x-show="menu" x-transition.opacity @click="menu=false" class="fixed inset-0 z-30 bg-slate-950/60 lg:hidden"></div>
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-slate-950 p-5 text-white transition-transform lg:static lg:translate-x-0">
        <div class="flex items-center justify-between"><a href="{{ route('dashboard') }}" class="text-2xl font-black">vitrine<span class="text-amber-400">.</span></a><button @click="menu=false" class="p-2 lg:hidden" aria-label="Fechar menu">✕</button></div>
        <div class="mt-7 rounded-xl bg-white/10 p-3"><p class="text-xs text-slate-400">LOJA ATUAL</p><p class="mt-1 truncate font-semibold">{{ $currentStore->name }}</p></div>
        @php($links = [['Visão geral','dashboard','⌂'],['Produtos','produtos','◇'],['Pedidos','pedidos','▤'],['Clientes','clientes','♙'],['Mensagens','mensagens','✉'],['Aparência','aparencia','✦'],['Configurações','configuracoes','⚙']])
        <nav class="mt-6 flex-1 space-y-1">@foreach($links as [$label,$route,$icon])<a class="nav-link {{ request()->routeIs($route === 'dashboard' ? 'dashboard' : '') || request()->route('page') === $route ? 'nav-link-active' : '' }}" href="{{ $route === 'dashboard' ? route('dashboard') : route('panel.page',$route) }}"><span class="w-5 text-center">{{ $icon }}</span>{{ $label }}</a>@endforeach</nav>
        <p class="text-xs text-slate-500">Vitrine SaaS · versão inicial</p>
    </aside>
    <div class="min-w-0 flex-1">
        <header class="flex h-18 items-center gap-4 border-b border-slate-200 bg-white px-4 sm:px-7"><button @click="menu=true" class="rounded-lg p-2 text-xl lg:hidden" aria-label="Abrir menu">☰</button>
            <div class="ml-auto flex items-center gap-3"><div class="relative"><button @click="userMenu=!userMenu" class="flex min-h-11 items-center gap-3 rounded-xl px-2 hover:bg-slate-50"><span class="grid size-9 place-items-center rounded-full bg-violet-100 font-bold text-violet-700">{{ mb_strtoupper(mb_substr(auth()->user()->name,0,1)) }}</span><span class="hidden text-left sm:block"><b class="block text-sm">{{ auth()->user()->name }}</b><small class="text-slate-500">{{ auth()->user()->email }}</small></span></button><div x-cloak x-show="userMenu" @click.outside="userMenu=false" class="absolute right-0 z-20 mt-2 w-64 rounded-xl border bg-white p-2 shadow-xl">
                <p class="px-3 py-2 text-xs font-semibold text-slate-400">TROCAR DE LOJA</p>@foreach($stores as $store)<form method="POST" action="{{ route('stores.select',$store) }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-slate-50">{{ $store->name }} @if($store->is($currentStore))<span class="text-violet-600">✓</span>@endif</button></form>@endforeach<hr class="my-2 border-slate-100"><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">Sair</button></form>
            </div></div></div>
        </header>
        <main class="p-4 sm:p-7 lg:p-9">{{ $slot }}</main>
    </div>
</div>@livewireScripts</body></html>
