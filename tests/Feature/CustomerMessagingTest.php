<?php

namespace Tests\Feature;

use App\Actions\CreateCustomerFromMessage;
use App\Actions\ReplyToCustomerMessage;
use App\Actions\SaveCustomer;
use App\Actions\SaveCustomerAddress;
use App\Actions\SaveCustomerMessage;
use App\Enums\CustomerMessageSource;
use App\Enums\CustomerMessageStatus;
use App\Enums\CustomerStatus;
use App\Enums\StoreRole;
use App\Models\Customer;
use App\Models\CustomerMessage;
use App\Models\Store;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerMessagingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->user->stores()->attach($this->store, ['role' => StoreRole::Owner->value]);
        $this->actingAs($this->user)->withSession(['current_store_id' => $this->store->id]);
        app(CurrentStore::class)->set($this->store);
    }

    public function test_customer_is_created_in_current_store_and_normalized(): void
    {
        $customer = $this->customer(['email' => ' MARIA@EXAMPLE.COM ', 'phone' => '(11) 99999-1234', 'document' => '123.456.789-00']);
        $this->assertSame(['maria@example.com', '11999991234', '12345678900'], [$customer->email, $customer->phone, $customer->document]);
        $this->assertSame($this->store->id, $customer->store_id);
    }

    public function test_email_and_cpf_are_unique_per_store_but_reusable_elsewhere(): void
    {
        $this->customer(['email' => 'same@example.com', 'document' => '123']);
        app(SaveCustomer::class)->handle($this->otherStore(), $this->customerData(['email' => 'same@example.com', 'document' => '123']));
        $this->assertDatabaseCount('customers', 2);
        $this->expectException(QueryException::class);
        $this->customer(['email' => 'same@example.com', 'document' => '456']);
    }

    public function test_foreign_customer_is_blocked_by_policy_and_route(): void
    {
        $foreign = app(SaveCustomer::class)->handle($this->otherStore(), $this->customerData());
        $this->assertFalse($this->user->can('view', $foreign));
        $this->get(route('customers.show', $foreign))->assertForbidden();
    }

    public function test_address_is_normalized_and_only_one_is_default(): void
    {
        $customer = $this->customer();
        $first = app(SaveCustomerAddress::class)->handle($customer, $this->addressData());
        $second = app(SaveCustomerAddress::class)->handle($customer, $this->addressData(['number' => '2']));
        $this->assertSame('01310100', $second->postal_code);
        $this->assertFalse($first->fresh()->is_default);
        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());
    }

    public function test_foreign_address_cannot_be_accessed(): void
    {
        $foreign = app(SaveCustomer::class)->handle($this->otherStore(), $this->customerData());
        $address = app(SaveCustomerAddress::class)->handle($foreign, $this->addressData());
        $this->assertFalse($this->user->can('update', $address));
    }

    public function test_message_may_have_no_customer_but_rejects_foreign_customer_or_user(): void
    {
        $this->assertNull($this->message()->customer_id);
        $foreign = app(SaveCustomer::class)->handle($this->otherStore(), $this->customerData());
        try {
            $this->message(['customer_id' => $foreign->id]);
            $this->fail();
        } catch (ValidationException) {
        }
        $this->expectException(ValidationException::class);
        $this->message(['assigned_user_id' => User::factory()->create()->id]);
    }

    public function test_opening_message_records_read_at(): void
    {
        $message = $this->message();
        $this->get(route('messages.show', $message))->assertOk();
        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_external_reply_answers_but_internal_note_does_not(): void
    {
        $external = $this->message();
        app(ReplyToCustomerMessage::class)->handle($external, $this->user, 'Resposta');
        $this->assertNotNull($external->fresh()->replied_at);
        $internal = $this->message();
        app(ReplyToCustomerMessage::class)->handle($internal, $this->user, 'Nota', true);
        $this->assertNull($internal->fresh()->replied_at);
    }

    public function test_foreign_user_cannot_reply_and_replies_are_immutable_by_policy(): void
    {
        $message = $this->message();
        $this->expectException(ValidationException::class);
        app(ReplyToCustomerMessage::class)->handle($message, User::factory()->create(), 'Inválida');
    }

    public function test_customer_from_message_is_idempotent(): void
    {
        $message = $this->message(['email' => 'visitor@example.com']);
        $first = app(CreateCustomerFromMessage::class)->handle($message, $this->user);
        $second = app(CreateCustomerFromMessage::class)->handle($message->fresh(), $this->user);
        $this->assertTrue($first->is($second));
        $this->assertSame(1, Customer::where('store_id', $this->store->id)->count());
    }

    public function test_routes_require_authentication_and_scoped_searches_exclude_foreign_data(): void
    {
        $this->customer(['name' => 'Local']);
        app(SaveCustomer::class)->handle($this->otherStore(), $this->customerData(['name' => 'Foreign']));
        $this->assertSame(['Local'], Customer::where('store_id', $this->store->id)->pluck('name')->all());
        auth()->logout();
        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    private function customer(array $overrides = []): Customer
    {
        return app(SaveCustomer::class)->handle($this->store, $this->customerData($overrides));
    }

    private function customerData(array $overrides = []): array
    {
        return array_merge(['name' => 'Maria', 'email' => uniqid().'@example.com', 'status' => CustomerStatus::Active, 'accepts_marketing' => false], $overrides);
    }

    private function message(array $overrides = []): CustomerMessage
    {
        return app(SaveCustomerMessage::class)->handle($this->store, array_merge(['name' => 'Visitante', 'message' => 'Dúvida', 'status' => CustomerMessageStatus::New, 'source' => CustomerMessageSource::ContactForm], $overrides));
    }

    private function addressData(array $overrides = []): array
    {
        return array_merge(['recipient_name' => 'Maria', 'postal_code' => '01310-100', 'street' => 'Paulista', 'number' => '1', 'neighborhood' => 'Centro', 'city' => 'São Paulo', 'state' => 'sp', 'country' => 'br', 'is_default' => true], $overrides);
    }

    private function otherStore(): Store
    {
        return Store::factory()->create();
    }
}
