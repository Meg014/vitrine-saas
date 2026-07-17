<?php

namespace App\Livewire\Storefront;

use App\Actions\SaveCustomerAddress;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Addresses extends Component
{
    public string $recipientName = '';

    public string $postalCode = '';

    public string $street = '';

    public string $number = '';

    public string $neighborhood = '';

    public string $city = '';

    public string $state = '';

    public bool $isDefault = false;

    public function save(SaveCustomerAddress $action): void
    {
        $data = $this->validate(['recipientName' => ['required'], 'postalCode' => ['required', 'max:10'], 'street' => ['required'], 'number' => ['required'], 'neighborhood' => ['required'], 'city' => ['required'], 'state' => ['required', 'size:2'], 'isDefault' => ['boolean']]);
        $action->handle(Auth::guard('customer')->user(), ['recipient_name' => $data['recipientName'], 'postal_code' => $data['postalCode'], 'street' => $data['street'], 'number' => $data['number'], 'neighborhood' => $data['neighborhood'], 'city' => $data['city'], 'state' => $data['state'], 'country' => 'BR', 'is_default' => $data['isDefault']]);
        $this->reset();
    }

    public function delete(int $id): void
    {
        Auth::guard('customer')->user()->addresses()->findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.storefront.addresses', ['addresses' => Auth::guard('customer')->user()->addresses()->orderByDesc('is_default')->get()]);
    }
}
