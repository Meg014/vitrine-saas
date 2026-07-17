<?php

namespace Tests\Feature;

use App\Actions\AddCartItem;
use App\Actions\CancelOrder;
use App\Actions\CreateOrder;
use App\Enums\CartStatus;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StoreStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Customer $customer;

    private Product $product;

    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::factory()->create(['status' => StoreStatus::Active]);
        $this->store->settings()->create(['pickup_enabled' => true, 'payment_methods' => ['pix']]);
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
        $this->product = $this->store->products()->create(['name' => 'Vela', 'slug' => 'vela', 'status' => ProductStatus::Active, 'product_type' => ProductType::Simple, 'base_price' => 2500, 'promotional_price' => 2000, 'sku' => 'VELA', 'track_inventory' => true, 'stock_quantity' => 5, 'published_at' => now()->subMinute()]);
        $this->cart = $this->store->carts()->create(['customer_id' => $this->customer->id, 'status' => CartStatus::Active]);
        app(AddCartItem::class)->handle($this->cart, $this->product, 2);
    }

    public function test_checkout_recalculates_price_creates_snapshots_and_decrements_stock(): void
    {
        $order = $this->checkout();
        $this->assertSame(4000, $order->subtotal);
        $this->assertSame('PED-000001', $order->number);
        $this->assertSame(3, $this->product->fresh()->stock_quantity);
        $this->assertSame(CartStatus::Converted, $this->cart->fresh()->status);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_name' => 'Vela', 'unit_price' => 2000, 'quantity' => 2]);
        $this->assertDatabaseHas('inventory_movements', ['reference_type' => Order::class, 'reference_id' => $order->id, 'quantity' => -2]);
    }

    public function test_checkout_token_is_idempotent_and_sequence_advances_once(): void
    {
        $token = (string) Str::uuid();
        $one = $this->checkout($token);
        $two = $this->checkout($token);
        $this->assertTrue($one->is($two));
        $this->assertSame(1, Order::count());
        $this->assertSame(2, (int) $this->store->fresh()->next_order_number);
    }

    public function test_insufficient_stock_rolls_back_the_whole_order(): void
    {
        $this->product->update(['stock_quantity' => 1]);
        $this->expectException(ValidationException::class);
        try {
            $this->checkout();
        } finally {
            $this->assertDatabaseCount('orders', 0);
            $this->assertSame(1, $this->product->fresh()->stock_quantity);
            $this->assertSame(CartStatus::Active, $this->cart->fresh()->status);
        }
    }

    public function test_cancel_restores_stock_exactly_once(): void
    {
        $order = $this->checkout();
        app(CancelOrder::class)->handle($order, customer: $this->customer);
        app(CancelOrder::class)->handle($order->fresh(), customer: $this->customer);
        $this->assertSame(5, $this->product->fresh()->stock_quantity);
        $this->assertSame(1, $order->fresh()->histories()->where('to_status', 'canceled')->count());
    }

    public function test_customer_cannot_open_another_customers_order(): void
    {
        $order = $this->checkout();
        $other = Customer::factory()->create(['store_id' => $this->store->id]);
        $this->actingAs($other, 'customer')->get(route('store.orders.show', [$this->store, $order]))->assertNotFound();
    }

    private function checkout(?string $token = null): Order
    {
        return app(CreateOrder::class)->handle($this->store, $this->customer, $this->cart, ['shipping_method' => 'pickup', 'payment_method' => 'pix'], $token ?? (string) Str::uuid());
    }
}
