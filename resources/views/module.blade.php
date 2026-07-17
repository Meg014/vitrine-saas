<x-layouts.panel :title="ucfirst($page)" :$currentStore :$stores>
    <p class="text-sm font-semibold uppercase text-violet-600">{{ str($page)->replace('-',' ') }}</p><h1 class="mt-1 text-3xl font-bold">{{ str($page)->replace('-',' ')->title() }}</h1>
    <div class="mt-7 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center sm:p-16"><span class="text-4xl">◇</span><h2 class="mt-4 text-xl font-bold">Módulo em desenvolvimento</h2><p class="mx-auto mt-2 max-w-md text-slate-500">Esta área já está preparada e será implementada em uma próxima etapa.</p></div>
</x-layouts.panel>
