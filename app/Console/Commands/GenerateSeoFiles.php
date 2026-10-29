<?php

namespace App\Console\Commands;

use App\Services\SeoExportService;
use Illuminate\Console\Command;

class GenerateSeoFiles extends Command
{
    protected $signature = 'seo:generate';

    protected $description = 'Generate the public Facebook Commerce feed and sitemap.xml files';

    public function handle(SeoExportService $seo): int
    {
        $facebook = $seo->generateFacebookCommerceFeed();
        $sitemap = $seo->generateSitemap();

        $this->info('Updated '.$facebook['filename'].' with '.$facebook['count'].' active product(s).');
        $this->info('Updated '.$sitemap['filename'].' with '.$sitemap['count'].' URL(s).');

        return self::SUCCESS;
    }
}
