<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\ImageWebpOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', ['settings' => array_replace_recursive($this->defaults(), SiteSetting::values())]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'header_menu_text' => ['nullable', 'string', 'max:10000'],
            'hero_badge' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['nullable', 'string', 'max:180'],
            'hero_accent' => ['nullable', 'string', 'max:100'],
            'hero_description' => ['nullable', 'string', 'max:600'],
            'hero_image_url' => ['nullable', 'string', 'max:2048'],
            'hero_image_alt' => ['nullable', 'string', 'max:255'],
            'hero_image_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'hero_primary_text' => ['nullable', 'string', 'max:60'],
            'hero_primary_url' => ['nullable', 'string', 'max:2048'],
            'hero_secondary_text' => ['nullable', 'string', 'max:60'],
            'hero_secondary_url' => ['nullable', 'string', 'max:2048'],
            'footer_about' => ['nullable', 'string', 'max:1000'],
            'footer_quick_links_text' => ['nullable', 'string', 'max:10000'],
            'footer_care_links_text' => ['nullable', 'string', 'max:10000'],
            'footer_newsletter_title' => ['nullable', 'string', 'max:100'],
            'footer_newsletter_text' => ['nullable', 'string', 'max:600'],
            'trust_one_title' => ['nullable', 'string', 'max:100'],
            'trust_one_text' => ['nullable', 'string', 'max:255'],
            'trust_two_title' => ['nullable', 'string', 'max:100'],
            'trust_two_text' => ['nullable', 'string', 'max:255'],
            'trust_three_title' => ['nullable', 'string', 'max:100'],
            'trust_three_text' => ['nullable', 'string', 'max:255'],
            'shipping_text' => ['nullable', 'string', 'max:255'],
            'return_text' => ['nullable', 'string', 'max:255'],
            'home_seo_title' => ['nullable', 'string', 'max:70'],
            'home_seo_description' => ['nullable', 'string', 'max:160'],
            'home_seo_keywords' => ['nullable', 'string', 'max:255'],
        ]);

        $existing = array_replace_recursive($this->defaults(), SiteSetting::values());
        $heroImage = trim((string) ($data['hero_image_url'] ?? ''));
        if ($request->hasFile('hero_image_upload')) {
            try {
                $heroImage = app(ImageWebpOptimizer::class)->storeUpload($request->file('hero_image_upload'), 'settings/hero')['path'];
            } catch (\RuntimeException $exception) {
                throw ValidationException::withMessages(['hero_image_upload' => $exception->getMessage()]);
            }
        }

        SiteSetting::put('header_menu', $this->links($data['header_menu_text'] ?? ''));
        SiteSetting::put('hero', [
            'badge' => trim((string) ($data['hero_badge'] ?? '')),
            'title' => trim((string) ($data['hero_title'] ?? '')),
            'accent' => trim((string) ($data['hero_accent'] ?? '')),
            'description' => trim((string) ($data['hero_description'] ?? '')),
            'image' => $heroImage ?: data_get($existing, 'hero.image'),
            'image_alt' => trim((string) ($data['hero_image_alt'] ?? '')),
            'primary_text' => trim((string) ($data['hero_primary_text'] ?? '')),
            'primary_url' => $this->url($data['hero_primary_url'] ?? '/shop'),
            'secondary_text' => trim((string) ($data['hero_secondary_text'] ?? '')),
            'secondary_url' => $this->url($data['hero_secondary_url'] ?? '/category/electronics'),
        ]);
        SiteSetting::put('footer', [
            'about' => trim((string) ($data['footer_about'] ?? '')),
            'quick_links' => $this->links($data['footer_quick_links_text'] ?? ''),
            'care_links' => $this->links($data['footer_care_links_text'] ?? ''),
            'newsletter_title' => trim((string) ($data['footer_newsletter_title'] ?? '')),
            'newsletter_text' => trim((string) ($data['footer_newsletter_text'] ?? '')),
        ]);
        SiteSetting::put('trust_blocks', [
            ['icon' => 'fa-truck-fast', 'title' => trim((string) ($data['trust_one_title'] ?? '')), 'text' => trim((string) ($data['trust_one_text'] ?? ''))],
            ['icon' => 'fa-shield-halved', 'title' => trim((string) ($data['trust_two_title'] ?? '')), 'text' => trim((string) ($data['trust_two_text'] ?? ''))],
            ['icon' => 'fa-headset', 'title' => trim((string) ($data['trust_three_title'] ?? '')), 'text' => trim((string) ($data['trust_three_text'] ?? ''))],
        ]);
        SiteSetting::put('service_notice', [
            'shipping_text' => trim((string) ($data['shipping_text'] ?? '')),
            'return_text' => trim((string) ($data['return_text'] ?? '')),
        ]);
        SiteSetting::put('home_seo', [
            'title' => trim((string) ($data['home_seo_title'] ?? '')),
            'description' => trim((string) ($data['home_seo_description'] ?? '')),
            'keywords' => trim((string) ($data['home_seo_keywords'] ?? '')),
        ]);

        return to_route('admin.settings.edit')->with('success', 'Store settings updated.');
    }

    /** @return array<int, array{label:string,url:string}> */
    private function links(string $value): array
    {
        return collect(preg_split('/\R/u', $value) ?: [])
            ->map(fn (string $line): array => array_map('trim', explode('|', $line, 2)))
            ->filter(fn (array $item): bool => count($item) === 2 && $item[0] !== '' && $item[1] !== '')
            ->take(20)
            ->map(fn (array $item): array => ['label' => Str::limit($item[0], 80, ''), 'url' => $this->url($item[1])])
            ->values()->all();
    }

    private function url(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') return '/';
        if (str_starts_with($value, '/') || filter_var($value, FILTER_VALIDATE_URL)) return $value;
        throw ValidationException::withMessages(['url' => 'Menu and button URLs must start with / or be a complete http/https URL.']);
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'header_menu' => [['label' => 'Home', 'url' => '/'], ['label' => 'Shop', 'url' => '/shop']],
            'hero' => ['badge' => '✨ NEW PAPER-CRAFTED COLLECTION 2026', 'title' => 'Experience Shopping in', 'accent' => 'Tangible Style', 'description' => 'Welcome to Live Bajar. Discover handpicked electronics, apparel, home decor, and organic groceries presented with an exquisite skeuomorphic paper aesthetic.', 'image' => '', 'image_alt' => 'Live Bajar shopping collection', 'primary_text' => 'Explore Shop', 'primary_url' => '/shop', 'secondary_text' => 'View Electronics', 'secondary_url' => '/category/electronics'],
            'footer' => ['about' => 'Your ultimate paper-crafted online marketplace. Experience tactile shopping with modern elegance, high quality curated items, and fast delivery.', 'quick_links' => [['label' => 'Home Page', 'url' => '/'], ['label' => 'Product Catalog & Filters', 'url' => '/shop'], ['label' => 'Shopping Cart', 'url' => '/cart'], ['label' => 'Checkout Securely', 'url' => '/checkout']], 'care_links' => [['label' => 'Fast delivery', 'url' => '/fast-delivery'], ['label' => 'Secure checkout', 'url' => '/secure-checkout'], ['label' => '30-day return policy', 'url' => '/30-day-return-policy'], ['label' => '24/7 dedicated support', 'url' => '/dedicated-support']], 'newsletter_title' => 'Newsletter', 'newsletter_text' => 'Subscribe to get special offers, free giveaways, and once-in-a-lifetime deals.'],
            'trust_blocks' => [['icon' => 'fa-truck-fast', 'title' => 'Lightning Fast Delivery', 'text' => 'Free delivery on orders over ৳150.00'], ['icon' => 'fa-shield-halved', 'title' => 'Secure Paper Payments', 'text' => '100% secure checkout systems'], ['icon' => 'fa-headset', 'title' => '24/7 Dedicated Support', 'text' => 'Always ready to assist you']],
            'service_notice' => ['shipping_text' => 'Free shipping on all orders over ৳150.00.', 'return_text' => '30-day hassle-free return and exchange policy.'],
            'home_seo' => [],
        ];
    }
}
