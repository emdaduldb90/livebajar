<?php

namespace Tests\Feature\Tracking;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\Tracking\TrackingEventStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class CoreFunnelTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_cart_add_records_one_post_success_tracking_event(): void
    {
        Session::start();
        $product = $this->product();

        $added = app(CartService::class)->add($product, 2);
        app(TrackingEventStore::class)->flashEcommerce('Product Added', ['product_id' => $product->sku, 'quantity' => $added]);

        $events = app(TrackingEventStore::class)->pull();

        $this->assertSame(2, $added);
        $this->assertCount(1, $events);
        $this->assertSame('Product Added', $events[0]['name']);
        $this->assertSame('ecommerce', $events[0]['type']);
        $this->assertSame(2, $events[0]['properties']['quantity']);
    }

    public function test_checkout_is_idempotent_and_purchase_identifiers_are_persisted(): void
    {
        Session::start();
        $product = $this->product();
        $cart = app(CartService::class);
        $cart->add($product, 2);
        $checkoutId = $cart->checkoutId();
        $checkout = app(CheckoutService::class);
        $data = [
            'name' => 'Test Customer',
            'email' => 'customer@example.test',
            'phone' => '+8801700000000',
            'address' => 'Test Address',
            'shipping_zone' => 'dhaka',
            'payment_method' => 'cod',
        ];

        $order = $checkout->place($data, $cart, $checkoutId);
        $repeat = $checkout->place($data, $cart, $checkoutId);

        $this->assertSame($order->id, $repeat->id);
        $this->assertSame($checkoutId, $order->tracking_checkout_id);
        $this->assertNotEmpty($order->tracking_purchase_event_id);
        $this->assertSame(48000, $order->total_cents);
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(8, $product->fresh()->stock_quantity);
    }

    public function test_product_page_renders_an_in_place_gallery_controller(): void
    {
        $product = $this->product();

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('data-product-gallery', false);
        $response->assertSee('data-gallery-main-image', false);
        $response->assertSee('js/product-gallery.js', false);
        $response->assertSee('Specification');
        $response->assertSee('Description');
        $response->assertSee('Questions (0)');
        $response->assertSee('Reviews (0)');
        $response->assertSee('Specification details will be available soon.');
    }

    public function test_product_detail_tabs_render_admin_managed_content(): void
    {
        $product = $this->product();
        $product->update([
            'key_features' => ['Battery: 7020 mAh', 'Warranty: 1 year'],
            'specifications' => [
                'Main Features' => [['label' => 'Display', 'value' => 'IPS LCD']],
            ],
            'faqs' => [['question' => 'Does it support Wi-Fi?', 'answer' => 'Yes, it supports Wi-Fi.']],
            'customer_reviews' => [['rating' => 5, 'name' => 'Rahim Ahmed', 'body' => 'Excellent product.']],
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('Key Features');
        $response->assertSee('Battery: 7020 mAh');
        $response->assertSee('Main Features');
        $response->assertSee('Does it support Wi-Fi?');
        $response->assertSee('Rahim Ahmed');
        $response->assertDontSee('Similar Products', false);
    }

    private function product(): Product
    {
        $category = Category::query()->create([
            'name' => 'Tracking Category',
            'slug' => 'tracking-category',
            'description' => 'Tracking test category',
            'is_active' => true,
        ]);

        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Tracking Product',
            'slug' => 'tracking-product',
            'sku' => 'TRACKING-SKU-1',
            'short_description' => 'Tracking test product',
            'price' => 200,
            'stock_quantity' => 10,
            'image_url' => 'products/tracking.png',
            'is_active' => true,
        ]);
    }
}
