<?php

uses(\Lunar\Tests\Admin\Feature\Filament\TestCase::class)
    ->group('resource.product');

it('can render product inventory page', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create();

    \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
    ]);

    $this->asStaff(admin: true)
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('inventory', [
            'record' => $record,
        ]))
        ->assertSuccessful();
});

it('will show in navigation when only one variant exists', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create();

    \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
    ]);

    $this->asStaff(admin: true)
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('edit', [
            'record' => $record,
        ]))
        ->assertSuccessful()
        ->assertSeeText(
            __('lunarpanel::product.pages.inventory.label')
        );
});

it('will not show in navigation when multiple variants exist', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create();

    \Lunar\Models\ProductVariant::factory(2)->create([
        'product_id' => $record->id,
    ]);

    $this->asStaff(admin: true)
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('edit', [
            'record' => $record,
        ]))
        ->assertSuccessful()
        ->assertDontSeeText(
            __('lunarpanel::product.pages.inventory.label')
        );
});

it('will show in navigation when product has soft-deleted variants but only one active variant', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create();

    // Create an active variant
    \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
    ]);

    // Create a soft-deleted variant
    \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
        'deleted_at' => now(),
    ]);

    $this->asStaff(admin: true)
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('edit', [
            'record' => $record,
        ]))
        ->assertSuccessful()
        ->assertSeeText(
            __('lunarpanel::product.pages.inventory.label')
        );
});

it('will show in navigation for soft-deleted product with single trashed variant', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create([
        'deleted_at' => now(),
    ]);

    // Create a soft-deleted variant (since product is trashed)
    \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
        'deleted_at' => now(),
    ]);

    $this->asStaff(admin: true)
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('edit', [
            'record' => $record,
        ]))
        ->assertSuccessful()
        ->assertSeeText(
            __('lunarpanel::product.pages.inventory.label')
        );
});

it('will not show in navigation for soft-deleted product with multiple trashed variants', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create([
        'deleted_at' => now(),
    ]);

    // Create multiple soft-deleted variants
    \Lunar\Models\ProductVariant::factory(2)->create([
        'product_id' => $record->id,
        'deleted_at' => now(),
    ]);

    $this->asStaff(admin: true)
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('edit', [
            'record' => $record,
        ]))
        ->assertSuccessful()
        ->assertDontSeeText(
            __('lunarpanel::product.pages.inventory.label')
        );
});

it('can update variant stock figures', function () {
    $language = \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    $currency = \Lunar\Models\Currency::factory()->create([
        'default' => true,
        'decimal_places' => 2,
    ]);

    $record = \Lunar\Models\Product::factory()->create();

    $variant = \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
    ]);

    $this->asStaff();

    \Livewire\Livewire::test(
        \Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductInventory::class, [
            'record' => $record->getRouteKey(),
        ])->fillForm([
            'stock' => 500,
            'backorder' => 50,
            'purchasable' => 'in_stock_or_on_backorder',
        ])->call('save')->assertHasNoErrors();

    $this->assertDatabaseHas((new \Lunar\Models\ProductVariant)->getTable(), [
        'stock' => 500,
        'backorder' => 50,
        'purchasable' => 'in_stock_or_on_backorder',
    ]);
});
