<x-layouts.guest title="Criar conta — Vitrine">
    <p class="text-sm font-semibold text-violet-600">COMECE AGORA</p><h1 class="mt-2 text-3xl font-bold">Crie sua conta</h1><p class="mt-2 text-slate-500">Em poucos passos sua primeira loja estará pronta.</p>
    <form method="POST" action="{{ route('register.store') }}" class="mt-7 space-y-4">@csrf
        <div><label for="name">Nome</label><input id="name" name="name" value="{{ old('name') }}" required>@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="email">E-mail</label><input id="email" name="email" type="email" value="{{ old('email') }}" required>@error('email')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="password">Senha</label><input id="password" name="password" type="password" required>@error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="password_confirmation">Confirme a senha</label><input id="password_confirmation" name="password_confirmation" type="password" required></div>
        <button class="btn-primary w-full" type="submit">Criar minha conta</button>
    </form>
    <p class="mt-6 text-center text-sm">Já tem conta? <a class="font-semibold text-violet-600" href="{{ route('login') }}">Entrar</a></p>
</x-layouts.guest>
