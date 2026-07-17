<?php

namespace App\Livewire\Customers;

use App\Actions\SaveCustomerAddress;
use App\Models\Customer;
use Livewire\Component;

class CustomerDetails extends Component
{
    public Customer $customer;

    public ?int $editingAddress = null;

    public string $label = 'Casa';

    public string $recipientName = '';

    public string $postalCode = '';

    public string $street = '';

    public string $number = '';

    public string $complement = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public string $country = 'BR';

    public ?string $phone = null;

    public bool $isDefault = false;

    public function mount(Customer $customer): void
    {
        $this->authorize('view', $customer);
        $this->customer = $customer;
    }

    public function saveAddress(SaveCustomerAddress $action): void
    {
        $this->authorize('update', $this->customer);
        $data = $this->validate(['recipientName' => ['required', 'max:150'], 'postalCode' => ['required', 'regex:/^[0-9.\- ]{8,10}$/'], 'street' => ['required', 'max:255'], 'number' => ['required', 'max:30'], 'neighborhood' => ['required', 'max:120'], 'city' => ['required', 'max:120'], 'state' => ['required', 'regex:/^[A-Za-z]{2}$/'], 'country' => ['required', 'size:2'], 'phone' => ['nullable', 'max:30'], 'isDefault' => ['boolean']]);
        $address = $this->editingAddress ? $this->customer->addresses()->findOrFail($this->editingAddress) : null;
        $action->handle($this->customer, ['label' => $this->label ?: null, 'recipient_name' => $data['recipientName'], 'postal_code' => $data['postalCode'], 'street' => $data['street'], 'number' => $data['number'], 'complement' => $this->complement ?: null, 'neighborhood' => $data['neighborhood'], 'city' => $data['city'], 'state' => $data['state'], 'country' => $data['country'], 'phone' => $data['phone'], 'is_default' => $data['isDefault']], $address);
        $this->resetAddress();
        session()->flash('status', 'Endereço salvo.');
    }

    public function makeDefault(int $id, SaveCustomerAddress $action): void
    {
        $address = $this->customer->addresses()->findOrFail($id);
        $action->handle($this->customer, [...$address->only(['label', 'recipient_name', 'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'country', 'phone']), 'is_default' => true], $address);
    }

    public function deleteAddress(int $id): void
    {
        $a = $this->customer->addresses()->findOrFail($id);
        $this->authorize('delete', $a);
        $a->delete();
    }

    private function resetAddress(): void
    {
        $this->reset(['editingAddress', 'recipientName', 'postalCode', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'phone', 'isDefault']);
        $this->label = 'Casa';
        $this->country = 'BR';
    }

    public function render()
    {
        return view('livewire.customers.customer-details', ['addresses' => $this->customer->addresses()->orderByDesc('is_default')->get(), 'messages' => $this->customer->messages()->latest()->limit(10)->get()]);
    }
}
