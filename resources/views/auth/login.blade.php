<x-layouts.guest title="Entrar — Vitrine">
    <a href="/" class="mb-10 block text-2xl font-black lg:hidden">vitrine<span class="text-amber-500">.</span></a>
    <p class="text-sm font-semibold text-violet-600">BEM-VINDO DE VOLTA</p><h1 class="mt-2 text-3xl font-bold">Entre na sua conta</h1><p class="mt-2 text-slate-500">Acesse o painel para continuar gerenciando sua loja.</p>
    <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">@csrf
        <div><label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email">@error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="password">Senha</label><input id="password" name="password" type="password" required autocomplete="current-password"></div>
        <label class="flex items-center gap-2 font-normal"><input class="size-4 w-auto" type="checkbox" name="remember"> Manter conectado</label>
        <button class="btn-primary w-full" type="submit">Entrar</button>
    </form>
    <p class="mt-7 text-center text-sm text-slate-600">Ainda não tem conta? <a class="font-semibold text-violet-600" href="{{ route('register') }}">Criar conta</a></p>
</x-layouts.guest>
