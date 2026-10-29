<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Page;
use RuntimeException;
use XMLWriter;

class SeoExportService
{
    public function generateFacebookCommerceFeed(): array
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $xml->startElement('channel');
        $xml->writeElement('title', config('app.name'));
        $xml->writeElement('link', $this->absoluteUrl('/'));
        $xml->writeElement('description', 'Live Bajar product catalog for Facebook Commerce.');

        $count = 0;
        Product::query()
            ->active()
            ->with(['category:id,name', 'brand:id,name'])
            ->orderBy('id')
            ->cursor()
            ->each(function (Product $product) use ($xml, &$count): void {
                $xml->startElement('item');
                $this->writeGoogleElement($xml, 'id', $product->sku);
                $this->writeGoogleElement($xml, 'title', $product->name);
                $this->writeGoogleElement($xml, 'description', $this->description($product));
                $this->writeGoogleElement($xml, 'availability', $product->stock_quantity > 0 ? 'in stock' : 'out of stock');
                $this->writeGoogleElement($xml, 'condition', 'new');
                $this->writeGoogleElement($xml, 'price', number_format((float) $product->price, 2, '.', '').' BDT');
                $this->writeGoogleElement($xml, 'link', $this->routeUrl('products.show', ['product' => $product]));
                $this->writeGoogleElement($xml, 'image_link', $product->image_url);
                $this->writeGoogleElement($xml, 'brand', $product->brand?->name ?: config('app.name'));
                $this->writeGoogleElement($xml, 'product_type', $product->category->name);
                $xml->endElement();
                $count++;
            });

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        $this->writePublicFile('facebook-commerce.xml', $xml->outputMemory());

        return ['count' => $count, 'filename' => 'facebook-commerce.xml'];
    }

    public function generateSitemap(): array
    {
        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $count = 0;
        $this->writeSitemapUrl($xml, $this->absoluteUrl('/'), now()->toDateString(), 'daily', '1.0');
        $count++;
        $this->writeSitemapUrl($xml, $this->routeUrl('products.index'), now()->toDateString(), 'daily', '0.9');
        $count++;

        Category::query()->active()->orderBy('id')->cursor()->each(function (Category $category) use ($xml, &$count): void {
            $this->writeSitemapUrl($xml, $this->routeUrl('products.index', ['category' => $category->slug]), $category->updated_at?->toDateString(), 'weekly', '0.7');
            $count++;
        });

        Product::query()->active()->orderBy('id')->cursor()->each(function (Product $product) use ($xml, &$count): void {
            $this->writeSitemapUrl($xml, $this->routeUrl('products.show', ['product' => $product]), $product->updated_at?->toDateString(), 'weekly', '0.8');
            $count++;
        });

        Page::query()->published()->orderBy('id')->cursor()->each(function (Page $page) use ($xml, &$count): void {
            $this->writeSitemapUrl($xml, $this->routeUrl('pages.show', ['page' => $page]), $page->updated_at?->toDateString(), 'monthly', '0.6');
            $count++;
        });

        $xml->endElement();
        $xml->endDocument();

        $this->writePublicFile('sitemap.xml', $xml->outputMemory());

        return ['count' => $count, 'filename' => 'sitemap.xml'];
    }

    private function description(Product $product): string
    {
        $description = $product->description ?: $product->short_description;
        $description = preg_replace('/\s+/u', ' ', trim(strip_tags($description))) ?: $product->name;

        return mb_substr($description, 0, 5000);
    }

    private function writeGoogleElement(XMLWriter $xml, string $name, string $value): void
    {
        $xml->startElementNs('g', $name, 'http://base.google.com/ns/1.0');
        $xml->text($value);
        $xml->endElement();
    }

    private function writeSitemapUrl(XMLWriter $xml, string $location, ?string $lastModified, string $frequency, string $priority): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $location);
        if ($lastModified !== null) {
            $xml->writeElement('lastmod', $lastModified);
        }
        $xml->writeElement('changefreq', $frequency);
        $xml->writeElement('priority', $priority);
        $xml->endElement();
    }

    private function routeUrl(string $route, array $parameters = []): string
    {
        return $this->absoluteUrl(route($route, $parameters, false));
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
    }

    private function writePublicFile(string $filename, string $contents): void
    {
        $path = public_path($filename);
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to update '.$filename.'. Check write permission for the public directory.');
        }
    }
}
