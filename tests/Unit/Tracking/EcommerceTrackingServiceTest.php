<?php

namespace Tests\Unit\Tracking;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\Tracking\EcommerceTrackingService;
use Tests\TestCase;

class EcommerceTrackingServiceTest extends TestCase
{
    public function test_catalog_product_mapping_uses_sku_and_actual_selling_price(): void
    {
        $product = new Product([
            'sku' => 'CATALOG-42',
            'name' => 'Verified Product',
            'price' => '899.50',
        ]);
        $product->setRelation('category', new Category(['name' => 'Electronics']));
        $product->setRelation('brand', new Brand(['name' => 'Live Brand']));

        $payload = app(EcommerceTrackingService::class)->product($product, 3, 2);

        $this->assertSame('CATALOG-42', $payload['product_id']);
        $this->assertSame('CATALOG-42', $payload['sku']);
        $this->assertSame(899.5, $payload['price']);
        $this->assertSame(2698.5, $payload['value']);
        $this->assertSame('BDT', $payload['currency']);
        $this->assertSame(3, $payload['quantity']);
        $this->assertSame(2, $payload['position']);
    }
}
