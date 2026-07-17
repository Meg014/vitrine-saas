<?php

namespace App\Livewire\Storefront;

use App\Actions\SaveCustomerMessage;
use App\Enums\CustomerMessageSource;
use App\Enums\CustomerMessageStatus;
use App\Models\Product;
use App\Support\PublicStoreContext;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContactForm extends Component
{
    public ?int $productId = null;

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $subject = null;

    public string $message = '';

    public string $website = '';

    public function mount(): void
    {
        $this->productId = request()->integer('product') ?: null;
        if ($c = Auth::guard('customer')->user()) {
            $this->name = $c->name;
            $this->email = $c->email;
            $this->phone = $c->phone;
        }
    }

    public function send(SaveCustomerMessage $action, PublicStoreContext $context): void
    {
        if ($this->website !== '') {
            return;
        }
        $data = $this->validate(['name' => ['required', 'max:150'], 'email' => ['nullable', 'email'], 'phone' => ['nullable', 'max:30'], 'subject' => ['nullable', 'max:255'], 'message' => ['required', 'min:10', 'max:5000']]);
        if ($this->productId && ! Product::whereKey($this->productId)->where('store_id', $context->getOrFail()->id)->exists()) {
            abort(404);
        }
        $action->handle($context->getOrFail(), [...$data, 'product_id' => $this->productId, 'customer_id' => Auth::guard('customer')->id(), 'status' => CustomerMessageStatus::New, 'source' => $this->productId ? CustomerMessageSource::ProductQuestion : CustomerMessageSource::ContactForm]);
        $this->reset(['subject', 'message']);
        session()->flash('status', 'Mensagem enviada com sucesso.');
    }

    public function render()
    {
        return view('livewire.storefront.contact-form');
    }
}
