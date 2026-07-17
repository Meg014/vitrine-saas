<?php

namespace App\Livewire\Messages;

use App\Actions\CreateCustomerFromMessage;
use App\Actions\ReplyToCustomerMessage;
use App\Actions\SaveCustomerMessage;
use App\Enums\CustomerMessageStatus;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Support\CurrentStore;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MessageDetails extends Component
{
    public CustomerMessage $customerMessage;

    public string $status = 'new';

    public ?int $assignedUserId = null;

    public ?int $customerId = null;

    public string $reply = '';

    public bool $isInternal = false;

    public function mount(CustomerMessage $customerMessage): void
    {
        $this->authorize('view', $customerMessage);
        $this->customerMessage = $customerMessage;
        $this->status = $customerMessage->status->value;
        $this->assignedUserId = $customerMessage->assigned_user_id;
        $this->customerId = $customerMessage->customer_id;
        if (! $customerMessage->read_at) {
            $customerMessage->update(['read_at' => now()]);
        }
    }

    public function updateMeta(CurrentStore $current, SaveCustomerMessage $action): void
    {
        $store = $current->getOrFail();
        $this->validate(['status' => [Rule::enum(CustomerMessageStatus::class)], 'assignedUserId' => ['nullable', 'integer'], 'customerId' => ['nullable', 'integer']]);
        $action->handle($store, [...$this->customerMessage->only(['name', 'email', 'phone', 'subject', 'message', 'source']), 'status' => $this->status, 'assigned_user_id' => $this->assignedUserId, 'customer_id' => $this->customerId], $this->customerMessage);
        session()->flash('status', 'Mensagem atualizada.');
    }

    public function sendReply(ReplyToCustomerMessage $action): void
    {
        $this->validate(['reply' => ['required', 'min:2', 'max:10000']]);
        $action->handle($this->customerMessage, auth()->user(), $this->reply, $this->isInternal);
        $this->reset(['reply', 'isInternal']);
        $this->customerMessage->refresh();
        $this->status = $this->customerMessage->status->value;
        session()->flash('status', 'Registro adicionado ao histórico.');
    }

    public function createCustomer(CreateCustomerFromMessage $action)
    {
        $customer = $action->handle($this->customerMessage, auth()->user());
        $this->customerId = $customer->id;
        session()->flash('status', 'Cliente vinculado à mensagem.');
    }

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();

        return view('livewire.messages.message-details', ['replies' => $this->customerMessage->replies()->with('user:id,name')->oldest()->get(), 'customers' => Customer::where('store_id', $store->id)->orderBy('name')->get(['id', 'name', 'email']), 'users' => $store->users()->orderBy('name')->get()]);
    }
}
