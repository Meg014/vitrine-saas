<?php

namespace App\Livewire\Catalog;

use App\Models\Category;
use App\Support\CurrentStore;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public ?int $editing = null;

    public string $name = '';

    public string $slug = '';

    public ?int $parentId = null;

    public string $description = '';

    public bool $isActive = true;

    public function save(CurrentStore $current): void
    {
        $store = $current->getOrFail();
        $data = $this->validate(['name' => ['required', 'max:120'], 'slug' => ['required', 'alpha_dash:ascii', Rule::unique('categories')->where('store_id', $store->id)->ignore($this->editing)], 'parentId' => ['nullable', Rule::exists('categories', 'id')->where('store_id', $store->id)], 'description' => ['nullable', 'max:1000'], 'isActive' => ['boolean']]);
        $category = $this->editing ? Category::where('store_id', $store->id)->findOrFail($this->editing) : new Category;
        if ($this->editing && $this->parentId === $this->editing) {
            $this->addError('parentId', 'Uma categoria não pode ser pai dela mesma.');
        } elseif ($this->parentId && Category::whereKey($this->parentId)->whereNotNull('parent_id')->exists()) {
            $this->addError('parentId', 'A hierarquia está limitada a um nível.');
        } else {
            $category->fill(['name' => $data['name'], 'slug' => $data['slug'], 'parent_id' => $data['parentId'], 'description' => $data['description'], 'is_active' => $data['isActive']]);
            $store->categories()->save($category);
            $this->resetForm();
            session()->flash('status', 'Categoria salva com sucesso.');
        }
    }

    public function edit(int $id, CurrentStore $current): void
    {
        $c = Category::where('store_id', $current->getOrFail()->id)->findOrFail($id);
        $this->authorize('update', $c);
        $this->editing = $c->id;
        $this->name = $c->name;
        $this->slug = $c->slug;
        $this->parentId = $c->parent_id;
        $this->description = $c->description ?? '';
        $this->isActive = $c->is_active;
    }

    public function delete(int $id, CurrentStore $current): void
    {
        $c = Category::where('store_id', $current->getOrFail()->id)->findOrFail($id);
        $this->authorize('delete', $c);
        $c->delete();
        session()->flash('status', 'Categoria excluída.');
    }

    public function updatedName(): void
    {
        if (! $this->editing) {
            $this->slug = Str::slug($this->name);
        }
    }

    private function resetForm(): void
    {
        $this->reset(['editing', 'name', 'slug', 'parentId', 'description']);
        $this->isActive = true;
    }

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();
        $query = Category::where('store_id', $store->id)->with('parent')->withCount('products')->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))->when($this->status !== 'all', fn ($q) => $q->where('is_active', $this->status === 'active'))->orderBy('sort_order')->orderBy('name');

        return view('livewire.catalog.category-manager', ['categories' => $query->paginate(12), 'parents' => Category::where('store_id', $store->id)->whereNull('parent_id')->orderBy('name')->get()]);
    }
}
