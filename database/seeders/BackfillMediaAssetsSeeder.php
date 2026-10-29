<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BackfillMediaAssetsSeeder extends Seeder
{
    public function run(): void
    {
        Product::query()->get()->each(function (Product $product): void {
            $path = $product->getRawOriginal('image_url');
            if (! is_string($path) || ! str_starts_with($path, 'products/') || ! Storage::disk('public')->exists($path)) {
                return;
            }

            $asset = $this->assetFor($path, $product->name);
            $product->updateQuietly(['image_asset_id' => $asset->id]);
        });

        ProductImage::query()->get()->each(function (ProductImage $image): void {
            if (! Storage::disk('public')->exists($image->path)) {
                return;
            }

            $asset = $this->assetFor($image->path, 'Product image');
            $image->updateQuietly(['media_asset_id' => $asset->id]);
        });
    }

    private function assetFor(string $path, string $altText): MediaAsset
    {
        return MediaAsset::query()->firstOrCreate(
            ['path' => $path],
            [
                'original_name' => basename($path),
                'alt_text' => $altText,
                'mime_type' => Storage::disk('public')->mimeType($path) ?: 'application/octet-stream',
                'size_bytes' => Storage::disk('public')->size($path),
            ]
        );
    }
}
