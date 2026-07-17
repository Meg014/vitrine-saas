<?php

namespace App\Livewire\Customers;

use App\Actions\SaveCustomer;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Support\CurrentStore;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CustomerForm extends Component
{
    public ?Customer $customer = null;

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $document = null;

    public ?string $birthDate = null;

    public string $status = 'active';

    public bool $acceptsMarketing = false;

    public string $notes = '';

    public function mount(?Customer $customer = null): void
    {
        if ($customer) {
            $this->authorize('update', $customer);
            $this->customer = $customer;
            $this->name = $customer->name;
            $this->email = $customer->email;
            $this->phone = $customer->phone;
            $this->document = $customer->document;
            $this->birthDate = $customer->birth_date?->format('Y-m-d');
            $this->status = $customer->status->value;
            $this->acceptsMarketing = $customer->accepts_marketing;
            $this->notes = $customer->notes ?? '';
        }
    }

    public function save(CurrentStore $current, SaveCustomer $action)
    {
        $store = $current->getOrFail();
        $this->authorize($this->customer ? 'update' : 'create', $this->customer ?? Customer::class);
        $data = $this->validate(['name' => ['required', 'max:150'], 'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->where('store_id', $store->id)->ignore($this->customer?->id)], 'phone' => ['nullable', 'max:30'], 'document' => ['nullable', 'max:20', Rule::unique('customers')->where('store_id', $store->id)->ignore($this->customer?->id)], 'birthDate' => ['nullable', 'date', 'before:today'], 'status' => [Rule::enum(CustomerStatus::class)], 'acceptsMarketing' => ['boolean'], 'notes' => ['nullable', 'max:5000']]);
        $customer = $action->handle($store, ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'document' => $data['document'], 'birth_date' => $data['birthDate'], 'status' => $data['status'], 'accepts_marketing' => $data['acceptsMarketing'], 'notes' => $data['notes']], $this->customer);
        session()->flash('status', 'Cliente salvo com sucesso.');

        return redirect()->route('customers.show', $customer);
    }

    public function render()
    {
        return view('livewire.customers.customer-form');
    }
}
