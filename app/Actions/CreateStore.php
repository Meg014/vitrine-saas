<?php

namespace App\Actions;

use App\Enums\StoreRole;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Support\Facades\DB;

class CreateStore
{
    public function __construct(private readonly CurrentStore $currentStore) {}

    /** @param array{name:string,slug:string,description?:?string} $data */
    public function handle(User $user, array $data): Store
    {
        $store = DB::transaction(function () use ($user, $data): Store {
            $store = Store::create([...$data, 'status' => StoreStatus::Draft]);
            $store->users()->attach($user, ['role' => StoreRole::Owner->value]);
            $store->settings()->create([]);

            return $store;
        });

        $this->currentStore->set($store);

        return $store;
    }
}
