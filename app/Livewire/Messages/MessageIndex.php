<?php

namespace App\Livewire\Messages;

use App\Enums\CustomerMessageSource;
use App\Enums\CustomerMessageStatus;
use App\Models\CustomerMessage;
use App\Support\CurrentStore;
use Livewire\Component;
use Livewire\WithPagination;

class MessageIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $status = 'all';

    public string $source = 'all';

    public ?int $assigned = null;

    public bool $unread = false;

    public function render(CurrentStore $current)
    {
        $store = $current->getOrFail();
        $messages = CustomerMessage::where('store_id', $store->id)->with(['customer:id,name', 'assignedUser:id,name'])->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")->orWhere('subject', 'like', "%{$this->search}%")->orWhere('message', 'like', "%{$this->search}%")))->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))->when($this->source !== 'all', fn ($q) => $q->where('source', $this->source))->when($this->assigned, fn ($q) => $q->where('assigned_user_id', $this->assigned))->when($this->unread, fn ($q) => $q->whereNull('read_at'))->latest()->paginate(20);

        return view('livewire.messages.message-index', ['messages' => $messages, 'statuses' => CustomerMessageStatus::cases(), 'sources' => CustomerMessageSource::cases(), 'users' => $store->users()->orderBy('name')->get()]);
    }
}
