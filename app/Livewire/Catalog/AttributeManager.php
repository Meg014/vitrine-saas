<?php

namespace App\Livewire\Catalog;

use App\Enums\AttributeDisplayType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Support\CurrentStore;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AttributeManager extends Component
{
    public string $name = '';

    public string $displayType = 'select';

    public bool $isRequired = false;

    public array $valueNames = [];

    public array $valueColors = [];

    public function save(CurrentStore $current): void
    {
        $store = $current->getOrFail();
        $data = $this->validate(['name' => ['required', 'max:100'], 'displayType' => [Rule::enum(AttributeDisplayType::class)], 'isRequired' => ['boolean']]);
        $attribute = $store->attributes()->create(['name' => $data['name'], 'slug' => Str::slug($data['name']), 'display_type' => $data['displayType'], 'is_required' => $data['isRequired']]);
        $this->reset(['name', 'displayType', 'isRequired']);
        session()->flash('status', 'Atributo criado.');
    }

    public function addValue(int $attributeId, CurrentStore $current): void
    {
        $attribute = Attribute::where('store_id', $current->getOrFail()->id)->findOrFail($attributeId);
        $this->authorize('update', $attribute);
        $name = trim($this->valueNames[$attributeId] ?? '');
        $color = $this->valueColors[$attributeId] ?? null;
        $this->validate(["valueNames.$attributeId" => ['required', 'max:100', Rule::unique('attribute_values', 'name')->where('attribute_id', $attributeId)], "valueColors.$attributeId" => [$attribute->display_type === AttributeDisplayType::Color ? 'required' : 'nullable', 'nullable', 'regex:/^#[0-9A-Fa-f]{6}$/']]);
        $attribute->values()->create(['name' => $name, 'color_hex' => $color]);
        unset($this->valueNames[$attributeId],$this->valueColors[$attributeId]);
    }

    public function deleteValue(int $id, CurrentStore $current): void
    {
        $value = AttributeValue::whereHas('attribute', fn ($q) => $q->where('store_id', $current->getOrFail()->id))->findOrFail($id);
        if ($value->variants()->exists()) {
            $this->addError('value', 'O valor está em uso por uma variação.');

            return;
        }$value->delete();
    }

    public function toggle(int $id, CurrentStore $current): void
    {
        $attribute = Attribute::where('store_id', $current->getOrFail()->id)->findOrFail($id);
        $this->authorize('update', $attribute);
        $attribute->update(['is_active' => ! $attribute->is_active]);
    }

    public function render(CurrentStore $current)
    {
        return view('livewire.catalog.attribute-manager', ['attributes' => Attribute::where('store_id', $current->getOrFail()->id)->with('values')->withCount('products')->orderBy('sort_order')->get(), 'displayTypes' => AttributeDisplayType::cases()]);
    }
}
