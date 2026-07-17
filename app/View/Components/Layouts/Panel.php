<?php

namespace App\View\Components\Layouts;

use App\Models\Store;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Panel extends Component
{
    /** @param Collection<int, Store> $stores */
    public function __construct(
        public readonly Store $currentStore,
        public readonly Collection $stores,
        public readonly string $title = 'Painel',
    ) {}

    public function render(): View
    {
        return view('layouts.panel');
    }
}
