<?php

namespace Tests\Feature;

use App\Enums\StoreRole;
use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_store_is_created_with_owner_settings_and_current_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('onboarding.store.save'), [
            'name' => 'Luz & Aroma', 'slug' => 'luz-aroma', 'description' => 'Velas artesanais',
        ])->assertRedirect(route('dashboard'))->assertSessionHas('current_store_id');

        $store = Store::firstOrFail();
        $this->assertDatabaseHas('store_user', ['store_id' => $store->id, 'user_id' => $user->id, 'role' => StoreRole::Owner->value]);
        $this->assertDatabaseHas('store_settings', ['store_id' => $store->id, 'currency' => 'BRL', 'locale' => 'pt_BR']);
    }

    public function test_member_can_access_dashboard(): void
    {
        [$user, $store] = $this->membership();
        $this->actingAs($user)->withSession(['current_store_id' => $store->id])->get(route('dashboard'))->assertOk()->assertSee($store->name);
    }

    public function test_user_cannot_access_another_users_store_through_session(): void
    {
        [$owner, $foreignStore] = $this->membership();
        $intruder = User::factory()->create();
        $this->actingAs($intruder)->withSession(['current_store_id' => $foreignStore->id])->get(route('dashboard'))
            ->assertRedirect(route('onboarding.store'))->assertSessionMissing('current_store_id');
    }

    public function test_user_can_switch_between_own_stores(): void
    {
        [$user, $first] = $this->membership();
        $second = Store::create(['name' => 'Segunda', 'slug' => 'segunda', 'status' => StoreStatus::Active]);
        $user->stores()->attach($second, ['role' => StoreRole::Admin->value]);

        $this->actingAs($user)->withSession(['current_store_id' => $first->id])->post(route('stores.select', $second))
            ->assertRedirect(route('dashboard'))->assertSessionHas('current_store_id', $second->id);
    }

    public function test_user_cannot_switch_to_foreign_store(): void
    {
        [$owner, $foreignStore] = $this->membership();
        $this->actingAs(User::factory()->create())->post(route('stores.select', $foreignStore))->assertForbidden();
    }

    public function test_slug_must_be_unique(): void
    {
        [$user] = $this->membership();
        $this->actingAs($user)->post(route('onboarding.store.save'), ['name' => 'Outra', 'slug' => 'loja-teste'])
            ->assertSessionHasErrors('slug');
        $this->assertSame(1, Store::count());
    }

    private function membership(string $role = 'owner'): array
    {
        $user = User::factory()->create();
        $store = Store::create(['name' => 'Loja Teste', 'slug' => 'loja-teste', 'status' => StoreStatus::Draft]);
        $store->settings()->create([]);
        $user->stores()->attach($store, ['role' => $role]);

        return [$user, $store];
    }
}
