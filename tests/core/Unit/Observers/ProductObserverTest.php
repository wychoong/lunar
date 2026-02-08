<?php

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

test('soft delete product does not affect variants', function () {
    $product = Product::factory()->create();

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    // Soft delete the product
    $product->delete();

    // Variants should NOT be soft deleted
    expect($variant1->fresh()->deleted_at)->toBeNull();
    expect($variant2->fresh()->deleted_at)->toBeNull();

    // Product should be soft deleted
    expect($product->fresh())->toBeNull();
    expect(Product::withTrashed()->find($product->id)->deleted_at)->not->toBeNull();
});

test('soft deleted variant is not affected by product delete or restore', function () {
    $product = Product::factory()->create();

    $activeVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    // Create a manually soft-deleted variant
    $trashedVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'deleted_at' => now(),
    ]);

    // Soft delete the product
    $product->delete();

    // Active variant should remain active
    expect($activeVariant->fresh()->deleted_at)->toBeNull();

    // Trashed variant should remain trashed
    expect(ProductVariant::withTrashed()->find($trashedVariant->id)->deleted_at)->not->toBeNull();
});

test('force delete product also force deletes all variants', function () {
    $product = Product::factory()->create();

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    $trashedVariant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'deleted_at' => now(),
    ]);

    // Force delete the product
    $product->forceDelete();

    // All variants should be force deleted (not found even with trashed)
    expect(ProductVariant::withTrashed()->find($variant1->id))->toBeNull();
    expect(ProductVariant::withTrashed()->find($variant2->id))->toBeNull();
    expect(ProductVariant::withTrashed()->find($trashedVariant->id))->toBeNull();

    // Product should be force deleted
    expect(Product::withTrashed()->find($product->id))->toBeNull();
});
