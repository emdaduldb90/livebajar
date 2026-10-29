<?php

namespace App\Console\Commands;

use App\Models\MediaAsset;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\SiteSetting;
use App\Services\ImageWebpOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OptimizeImagesToWebp extends Command
{
    protected $signature = 'images:optimize-webp';
    protected $description = 'Convert existing locally stored JPG/PNG product and hero images to optimized WebP files.';

    public function handle(ImageWebpOptimizer $optimizer): int
    {
        if (! $optimizer->supported()) {
            $this->error('PHP GD with WebP support is not enabled. Enable GD in the hosting PHP selector, then run this command again.');
            return self::FAILURE;
        }

        $disk = Storage::disk('public');
        $files = collect($disk->allFiles())->filter(fn (string $path): bool => preg_match('/\.(?:jpe?g|png)$/i', $path) === 1)->values();
        if ($files->isEmpty()) {
            $this->info('No JPG or PNG files found in public storage.');
            return self::SUCCESS;
        }

        $converted = 0;
        foreach ($files as $oldPath) {
            try {
                $newPath = $optimizer->optimiseExisting($oldPath);
                $this->replaceReferences($oldPath, $newPath);
                $converted++;
                $this->line("{$oldPath} → {$newPath}");
            } catch (\Throwable $exception) {
                $this->warn("Skipped {$oldPath}: {$exception->getMessage()}");
            }
        }

        $this->info("Optimized {$converted} image(s) to WebP.");
        return self::SUCCESS;
    }

    private function replaceReferences(string $oldPath, string $newPath): void
    {
        DB::transaction(function () use ($oldPath, $newPath): void {
            MediaAsset::query()->where('path', $oldPath)->update(['path' => $newPath, 'mime_type' => 'image/webp', 'size_bytes' => Storage::disk('public')->size($newPath)]);
            ProductImage::query()->where('path', $oldPath)->update(['path' => $newPath]);
            Product::query()->where('image_url', $oldPath)->update(['image_url' => $newPath]);

            SiteSetting::query()->where('key', 'hero')->get()->each(function (SiteSetting $setting) use ($oldPath, $newPath): void {
                $value = $setting->value;
                if (($value['image'] ?? null) === $oldPath) {
                    $value['image'] = $newPath;
                    SiteSetting::put('hero', $value);
                }
            });
        });
    }
}
