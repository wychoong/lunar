<?php

uses(\Lunar\Tests\Admin\Feature\Filament\TestCase::class)
    ->group('resource.product');

it('can render product prices create page', function () {
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
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('pricing', [
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
            __('lunarpanel::relationmanagers.pricing.title')
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
        ->get(\Lunar\Admin\Filament\Resources\ProductResource::getUrl('index', [
            'record' => $record,
        ]))
        ->assertSuccessful()
        ->assertDontSeeText(
            __('lunarpanel::relationmanagers.pricing.title')
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
            __('lunarpanel::relationmanagers.pricing.title')
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
            __('lunarpanel::relationmanagers.pricing.title')
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
            __('lunarpanel::relationmanagers.pricing.title')
        );
});

it('can set product base prices correctly', function () {
    \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    \Lunar\Models\Currency::factory()->create([
        'default' => true,
    ]);

    $record = \Lunar\Models\Product::factory()->create();

    $variant = \Lunar\Models\ProductVariant::factory()->create([
        'product_id' => $record->id,
    ]);

    $this->asStaff(admin: true);

    \Livewire\Livewire::test(\Lunar\Admin\Filament\Resources\ProductResource\Pages\ManageProductPricing::class, [
        'record' => $record->id,
        'pageClass' => 'productPriceRelationManager',
    ])->set('basePrices', [
        [
            'id' => null,
            'currency_id' => \Lunar\Models\Currency::getDefault()->id,
            'sync_prices' => false,
            'label' => 'GBP',
            'value' => '2.32',
            'factor' => '100',
        ],
    ])->call('save')->assertHasNoErrors();

    \Pest\Laravel\assertDatabaseHas((new \Lunar\Models\Price)->getTable(), [
        'price' => '232',
    ]);

});
