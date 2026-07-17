<?php

namespace App\Livewire\Storefront;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Account extends Component
{
    public string $name = '';

    public ?string $phone = null;

    public ?string $birthDate = null;

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $c = Auth::guard('customer')->user();
        $this->name = $c->name;
        $this->phone = $c->phone;
        $this->birthDate = $c->birth_date?->format('Y-m-d');
    }

    public function save(): void
    {
        $data = $this->validate(['name' => ['required', 'max:150'], 'phone' => ['nullable', 'max:30'], 'birthDate' => ['nullable', 'date', 'before:today']]);
        Auth::guard('customer')->user()->update(['name' => $data['name'], 'phone' => preg_replace('/\D+/', '', $data['phone'] ?? ''), 'birth_date' => $data['birthDate']]);
        session()->flash('status', 'Dados atualizados.');
    }

    public function changePassword(): void
    {
        $this->validate(['currentPassword' => ['required'], 'password' => ['required', Password::min(8)], 'passwordConfirmation' => ['same:password']]);
        $c = Auth::guard('customer')->user();
        if (! Hash::check($this->currentPassword, $c->password)) {
            $this->addError('currentPassword', 'Senha atual incorreta.');

            return;
        }$c->update(['password' => $this->password]);
        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        session()->flash('status', 'Senha alterada.');
    }

    public function render()
    {
        return view('livewire.storefront.account');
    }
}
