<?php

namespace Tests\Feature;

use App\Actions\AddCartItem;
use App\Actions\MergeCarts;
use App\Actions\ResolveCart;
use App\Actions\UpdateCartItem;
use App\Enums\CustomerStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StoreStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PublicStorefrontTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::factory()->create(['status' => StoreStatus::Active]);
        $this->store->settings()->create([]);
    }

    public function test_only_active_store_is_public(): void
    {
        $this->get(route('store.home', $this->store))->assertOk()->assertSee($this->store->name);
        $suspended = Store::factory()->create(['status' => StoreStatus::Suspended]);
        $this->get(route('store.home', $suspended))->assertNotFound();
    }

    public function test_catalog_only_shows_published_products_from_store(): void
    {
        $local = $this->product();
        $foreign = $this->product($this->otherStore(), ['name' => 'Produto estrangeiro', 'slug' => 'foreign']);
        $this->get(route('store.catalog', $this->store))->assertOk()->assertSee($local->name)->assertDontSee($foreign->name);
    }

    public function test_foreign_product_and_category_slugs_are_not_accessible(): void
    {
        $other = $this->otherStore();
        $product = $this->product($other);
        $category = $other->categories()->create(['name' => 'Privada', 'slug' => 'privada']);
        $this->get(route('store.product', [$this->store, $product]))->assertNotFound();
        $this->get(route('store.category', [$this->store, $category]))->assertNotFound();
    }

    public function test_simple_product_adds_increments_and_ignores_browser_price(): void
    {
        $product = $this->product();
        $cart = $this->cart();
        app(AddCartItem::class)->handle($cart, $product, 1);
        app(AddCartItem::class)->handle($cart, $product, 2);
        $item = $cart->items()->firstOrFail();
        $this->assertSame(3, $item->quantity);
        $this->assertSame(5000, $item->unit_price);
        $this->assertSame(12000, $cart->fresh()->subtotal());
    }

    public function test_stock_and_inactive_product_are_enforced(): void
    {
        $product = $this->product(null, ['stock_quantity' => 2]);
        try {
            app(AddCartItem::class)->handle($this->cart(), $product, 3);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->expectException(ValidationException::class);
        app(AddCartItem::class)->handle($this->cart(), $this->product(null, ['slug' => 'off', 'sku' => 'OFF', 'status' => ProductStatus::Inactive]), 1);
    }

    public function test_variable_product_requires_its_own_active_variant(): void
    {
        $product = $this->product(null, ['product_type' => ProductType::Variable, 'sku' => null]);
        $variant = $product->variants()->create(['sku' => 'VAR', 'price' => 6000, 'stock_quantity' => 5, 'is_active' => true, 'combination_key' => '1']);
        try {
            app(AddCartItem::class)->handle($this->cart(), $product, 1);
            $this->fail();
        } catch (ValidationException) {
        }
        app(AddCartItem::class)->handle($this->cart(), $product, 1, $variant);
        $foreignVariant = $this->product($this->otherStore(), ['slug' => 'variable-2', 'product_type' => ProductType::Variable, 'sku' => null])->variants()->create(['sku' => 'FV', 'price' => 1, 'stock_quantity' => 1, 'is_active' => true, 'combination_key' => '2']);
        $this->expectException(ValidationException::class);
        app(AddCartItem::class)->handle($this->cart(), $product, 1, $foreignVariant);
    }

    public function test_item_updates_removes_and_carts_do_not_mix_stores(): void
    {
        $cart = $this->cart();
        $item = app(AddCartItem::class)->handle($cart, $this->product(), 1);
        app(UpdateCartItem::class)->handle($item->load(['product', 'productVariant']), 4);
        $this->assertSame(4, $item->fresh()->quantity);
        $item->delete();
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertNotSame($cart->id, $this->cart($this->otherStore())->id);
    }

    public function test_registration_is_scoped_hashed_and_duplicate_email_is_rejected(): void
    {
        $data = ['name' => 'Cliente', 'email' => 'client@example.com', 'phone' => '(11) 99999-0000', 'password' => 'password123', 'password_confirmation' => 'password123', 'terms' => '1'];
        $this->post(route('store.register.submit', $this->store), $data)->assertRedirect(route('store.account', $this->store));
        $customer = Customer::firstOrFail();
        $this->assertSame($this->store->id, $customer->store_id);
        $this->assertTrue(Hash::check('password123', $customer->password));
        Auth::guard('customer')->logout();
        $this->post(route('store.register.submit', $this->store), $data)->assertSessionHasErrors('email');
        $other = $this->otherStore();
        $this->post(route('store.register.submit', $other), $data)->assertRedirect(route('store.account', $other));
        $this->assertSame(2, Customer::where('email', 'client@example.com')->count());
    }

    public function test_login_is_store_scoped_and_blocked_customer_cannot_enter(): void
    {
        $customer = $this->customer(['password' => 'password123']);
        $this->post(route('store.login.submit', $this->store), ['email' => $customer->email, 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticatedAs($customer, 'customer');
        Auth::guard('customer')->logout();
        $customer->update(['status' => CustomerStatus::Blocked]);
        $this->post(route('store.login.submit', $this->store), ['email' => $customer->email, 'password' => 'password123'])->assertSessionHasErrors('email');
    }

    public function test_customer_auth_does_not_grant_panel_and_private_account_requires_customer(): void
    {
        $this->get(route('store.account', $this->store))->assertRedirect(route('store.login', $this->store));
        $customer = $this->customer();
        Auth::guard('customer')->login($customer);
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->post(route('store.logout', $this->store))->assertRedirect(route('store.home', $this->store));
        $this->assertGuest('customer');
    }

    public function test_guest_cart_merges_without_duplicate_items(): void
    {
        $product = $this->product();
        $guest = $this->cart();
        app(AddCartItem::class)->handle($guest, $product, 2);
        $customer = $this->customer();
        $target = app(ResolveCart::class)->handle($this->store, $customer);
        app(AddCartItem::class)->handle($target, $product, 1);
        $merged = app(MergeCarts::class)->handle($guest, $customer);
        $this->assertSame(1, $merged->items()->count());
        $this->assertSame(3, $merged->items()->first()->quantity);
    }

    private function product(?Store $store = null, array $overrides = []): Product
    {
        return ($store ?? $this->store)->products()->create(array_merge(['name' => 'Vela artesanal', 'slug' => 'vela', 'status' => ProductStatus::Active, 'product_type' => ProductType::Simple, 'base_price' => 5000, 'promotional_price' => 4000, 'sku' => 'VELA', 'stock_quantity' => 10, 'published_at' => now()], $overrides));
    }

    private function cart(?Store $store = null): Cart
    {
        return app(ResolveCart::class)->handle($store ?? $this->store);
    }

    private function customer(array $overrides = []): Customer
    {
        return $this->store->customers()->create(array_merge(['name' => 'Cliente', 'email' => uniqid().'@example.com', 'password' => 'password123', 'status' => CustomerStatus::Active], $overrides));
    }

    private function otherStore(): Store
    {
        $store = Store::factory()->create(['status' => StoreStatus::Active]);
        $store->settings()->create([]);

        return $store;
    }
}
