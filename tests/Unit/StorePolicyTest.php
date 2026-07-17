<?php

namespace Tests\Unit;

use App\Enums\StoreRole;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use App\Policies\StorePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_but_stranger_cannot(): void
    {
        [$owner, $store] = $this->membership(StoreRole::Owner);
        $policy = new StorePolicy;
        $this->assertTrue($policy->view($owner, $store));
        $this->assertFalse($policy->view(User::factory()->create(), $store));
    }

    public function test_only_owner_or_admin_can_update_and_manage_team(): void
    {
        [$owner, $store] = $this->membership(StoreRole::Owner);
        $attendant = User::factory()->create();
        $attendant->stores()->attach($store, ['role' => StoreRole::Attendant->value]);
        $policy = new StorePolicy;
        $this->assertTrue($policy->update($owner, $store));
        $this->assertTrue($policy->manageTeam($owner, $store));
        $this->assertFalse($policy->update($attendant, $store));
        $this->assertFalse($policy->manageTeam($attendant, $store));
    }

    private function membership(StoreRole $role): array
    {
        $user = User::factory()->create();
        $store = Store::create(['name' => 'Loja', 'slug' => 'loja', 'status' => StoreStatus::Draft]);
        $user->stores()->attach($store, ['role' => $role->value]);

        return [$user, $store];
    }
}
