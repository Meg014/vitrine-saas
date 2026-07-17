<?php

namespace Tests\Feature;

use App\Actions\AdjustInventory;
use App\Actions\AttachProductAttributes;
use App\Actions\CreateProduct;
use App\Actions\CreateProductVariants;
use App\Actions\GenerateVariantCombinations;
use App\Actions\StoreProductImages;
use App\Enums\AttributeDisplayType;
use App\Enums\InventoryMovementType;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StoreRole;
use App\Enums\StoreStatus;
use App\Livewire\Catalog\CategoryManager;
use App\Livewire\Catalog\ProductForm;
use App\Models\Store;
use App\Models\User;
use App\Support\CurrentStore;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->store = Store::create(['name' => 'Loja', 'slug' => 'loja', 'status' => StoreStatus::Active]);
        $this->user->stores()->attach($this->store, ['role' => StoreRole::Owner->value]);
        $this->actingAs($this->user)->withSession(['current_store_id' => $this->store->id]);
        app(CurrentStore::class)->set($this->store);
    }

    public function test_category_slug_is_unique_per_store_but_reusable_by_another_store(): void
    {
        $this->store->categories()->create(['name' => 'Velas', 'slug' => 'velas']);
        $this->expectException(QueryException::class);
        $this->store->categories()->create(['name' => 'Outra', 'slug' => 'velas']);
    }

    public function test_user_creates_category_in_current_store(): void
    {
        Livewire::test(CategoryManager::class)->set('name', 'Acessórios')->set('slug', 'acessorios')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('categories', ['store_id' => $this->store->id, 'slug' => 'acessorios']);
    }

    public function test_user_cannot_update_foreign_category(): void
    {
        $category = $this->otherStore()->categories()->create(['name' => 'Privada', 'slug' => 'privada']);
        $this->assertFalse($this->user->can('update', $category));
    }

    public function test_other_store_can_use_same_category_slug(): void
    {
        $this->store->categories()->create(['name' => 'Velas', 'slug' => 'velas']);
        $other = Store::create(['name' => 'Outra', 'slug' => 'outra', 'status' => StoreStatus::Active]);
        $other->categories()->create(['name' => 'Velas', 'slug' => 'velas']);
        $this->assertDatabaseCount('categories', 2);
    }

    public function test_product_rejects_foreign_category(): void
    {
        $foreign = $this->otherStore()->categories()->create(['name' => 'X', 'slug' => 'x']);
        $this->expectException(ValidationException::class);
        app(CreateProduct::class)->handle($this->store, $this->productData(['category_id' => $foreign->id]));
    }

    public function test_simple_product_is_created_with_cents(): void
    {
        $p = app(CreateProduct::class)->handle($this->store, $this->productData());
        $this->assertSame(4990, $p->base_price);
        $this->assertSame(ProductType::Simple, $p->product_type);
    }

    public function test_promotional_price_must_be_lower(): void
    {
        $this->expectException(ValidationException::class);
        app(CreateProduct::class)->handle($this->store, $this->productData(['promotional_price' => 4990]));
    }

    public function test_sku_is_unique_inside_store_but_reusable_in_other_store(): void
    {
        app(CreateProduct::class)->handle($this->store, $this->productData());
        $this->expectException(QueryException::class);
        app(CreateProduct::class)->handle($this->store, $this->productData(['slug' => 'outra']));
    }

    public function test_inventory_adjustment_creates_movement_and_cannot_be_negative(): void
    {
        $p = app(CreateProduct::class)->handle($this->store, $this->productData(['stock_quantity' => 5]));
        $m = app(AdjustInventory::class)->handle($p, InventoryMovementType::Exit, 3, $this->user, 'Venda balcão');
        $this->assertSame(2, $m->new_quantity);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $p->id, 'previous_quantity' => 5, 'new_quantity' => 2]);
        $this->expectException(ValidationException::class);
        app(AdjustInventory::class)->handle($p->fresh(), InventoryMovementType::Exit, 3, $this->user);
    }

    public function test_attribute_association_requires_same_store(): void
    {
        $p = app(CreateProduct::class)->handle($this->store, $this->productData());
        $foreign = $this->otherStore()->attributes()->create(['name' => 'Cor', 'slug' => 'cor', 'display_type' => AttributeDisplayType::Color]);
        $this->expectException(ValidationException::class);
        app(AttachProductAttributes::class)->handle($p, [$foreign->id]);
    }

    public function test_variant_combinations_are_generated_without_duplicates(): void
    {
        [$p,$a,$values] = $this->variableProduct();
        $result = app(GenerateVariantCombinations::class)->handle($p, [$a->id => $values->pluck('id')->all()]);
        $this->assertCount(2, $result);
        $this->assertSame($result->pluck('combination_key')->unique()->count(), 2);
    }

    public function test_combination_limit_is_enforced(): void
    {
        [$p,$a] = $this->variableProduct();
        $ids = range(1, 101);
        $this->expectException(ValidationException::class);
        app(GenerateVariantCombinations::class)->handle($p, [$a->id => $ids]);
    }

    public function test_variant_rejects_value_from_other_store(): void
    {
        [$p,$a,$values] = $this->variableProduct();
        $foreign = $this->otherStore()->attributes()->create(['name' => 'Tamanho', 'slug' => 'tamanho', 'display_type' => AttributeDisplayType::Select]);
        $foreignValue = $foreign->values()->create(['name' => 'G']);
        $this->expectException(ValidationException::class);
        app(GenerateVariantCombinations::class)->handle($p, [$a->id => [$foreignValue->id]]);
    }

    public function test_variants_are_created_and_duplicate_combinations_fail(): void
    {
        [$p,$a,$values] = $this->variableProduct();
        $groups = [$a->id => [$values->first()->id]];
        $rows = [['sku' => 'VAR-1', 'price' => 5500, 'stock_quantity' => 3]];
        app(CreateProductVariants::class)->handle($p, $groups, $rows);
        $this->assertDatabaseHas('product_variants', ['sku' => 'VAR-1']);
        $this->expectException(ValidationException::class);
        app(CreateProductVariants::class)->handle($p, $groups, $rows);
    }

    public function test_valid_image_is_stored_and_only_one_is_primary(): void
    {
        Storage::fake('public');
        $p = app(CreateProduct::class)->handle($this->store, $this->productData());
        $files = [UploadedFile::fake()->create('one.jpg', 10, 'image/jpeg'), UploadedFile::fake()->create('two.png', 10, 'image/png')];
        app(StoreProductImages::class)->handle($p, $files);
        $this->assertSame(1, $p->images()->where('is_primary', true)->count());
        $this->assertCount(2, $p->images);
    }

    public function test_invalid_image_upload_is_rejected(): void
    {
        Livewire::test(ProductForm::class)->set('name', 'Produto')->set('slug', 'produto')->set('images', [UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream')])->call('save')->assertHasErrors('images.0');
    }

    public function test_cannot_delete_foreign_product_image(): void
    {
        $other = $this->otherStore();
        $p = app(CreateProduct::class)->handle($other, $this->productData());
        $image = $p->images()->create(['path' => 'foreign.jpg', 'is_primary' => true]);
        $this->assertFalse($this->user->can('delete', $image));
    }

    public function test_catalog_routes_require_login_and_current_store(): void
    {
        auth()->logout();
        $this->get(route('products.index'))->assertRedirect(route('login'));
        app(CurrentStore::class)->forget();
        $this->actingAs($this->user)->withSession(['current_store_id' => null])->get(route('products.index'))->assertRedirect(route('onboarding.store'));
    }

    private function productData(array $overrides = []): array
    {
        return array_merge(['name' => 'Vela', 'slug' => 'vela', 'status' => ProductStatus::Draft->value, 'product_type' => ProductType::Simple->value, 'base_price' => 4990, 'promotional_price' => 3990, 'sku' => 'VELA-1', 'stock_quantity' => 0], $overrides);
    }

    private function otherStore(): Store
    {
        return Store::create(['name' => 'Outra '.uniqid(), 'slug' => 'outra-'.uniqid(), 'status' => StoreStatus::Active]);
    }

    private function variableProduct(): array
    {
        $p = app(CreateProduct::class)->handle($this->store, $this->productData(['slug' => 'variavel', 'sku' => null, 'product_type' => 'variable']));
        $a = $this->store->attributes()->create(['name' => 'Aroma', 'slug' => 'aroma', 'display_type' => AttributeDisplayType::Select]);
        $values = collect([$a->values()->create(['name' => 'Lavanda']), $a->values()->create(['name' => 'Baunilha'])]);
        app(AttachProductAttributes::class)->handle($p, [$a->id]);

        return [$p, $a, $values];
    }
}
