<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SeoExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Throwable;

class SeoController extends Controller
{
    public function facebookCommerce(): View
    {
        return view('admin.seo.facebook-commerce', [
            'file' => $this->fileDetails('facebook-commerce.xml'),
        ]);
    }

    public function sitemap(): View
    {
        return view('admin.seo.sitemap', [
            'file' => $this->fileDetails('sitemap.xml'),
        ]);
    }

    public function updateFacebookCommerce(SeoExportService $seo): RedirectResponse
    {
        try {
            $result = $seo->generateFacebookCommerceFeed();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['seo' => 'Facebook Commerce feed could not be updated. Verify public-directory write permission.']);
        }

        return to_route('admin.seo.facebook-commerce')->with('success', 'Facebook Commerce data file updated with '.$result['count'].' active product(s).');
    }

    public function updateSitemap(SeoExportService $seo): RedirectResponse
    {
        try {
            $result = $seo->generateSitemap();
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['seo' => 'Sitemap could not be updated. Verify public-directory write permission.']);
        }

        return to_route('admin.seo.sitemap')->with('success', 'Sitemap updated with '.$result['count'].' URL(s).');
    }

    private function fileDetails(string $filename): array
    {
        $path = public_path($filename);

        return [
            'name' => $filename,
            'url' => rtrim((string) config('app.url'), '/').'/'.$filename,
            'exists' => File::exists($path),
            'updated_at' => File::exists($path) ? now()->setTimestamp(File::lastModified($path)) : null,
            'size' => File::exists($path) ? File::size($path) : null,
        ];
    }
}
