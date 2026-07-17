<x-layouts.guest title="Crie sua loja — Vitrine">
    <div class="mb-8 flex items-center gap-2"><span class="h-2 w-16 rounded bg-violet-600"></span><span class="h-2 w-16 rounded bg-slate-200"></span></div>
    <p class="text-sm font-semibold text-violet-600">PRIMEIROS PASSOS</p><h1 class="mt-2 text-3xl font-bold">Vamos criar sua loja</h1><p class="mt-2 text-slate-500">Você poderá ajustar estas informações depois.</p>
    <form method="POST" action="{{ route('onboarding.store.save') }}" class="mt-8 space-y-5">@csrf
        <div><label for="name">Nome da loja</label><input id="name" name="name" value="{{ old('name') }}" required x-data @input="$refs.slug.value = $el.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '')">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="slug">Endereço da loja</label><div class="flex items-center rounded-xl border border-slate-300 bg-white focus-within:ring-4 focus-within:ring-violet-100"><span class="pl-4 text-sm text-slate-400">vitrine.com/</span><input x-ref="slug" class="border-0 pl-1 focus:ring-0" id="slug" name="slug" value="{{ old('slug') }}" required></div>@error('slug')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="description">Descrição <span class="font-normal text-slate-400">(opcional)</span></label><textarea id="description" name="description" rows="3">{{ old('description') }}</textarea></div>
        <button class="btn-primary w-full" type="submit">Criar loja e continuar</button>
    </form>
</x-layouts.guest>
