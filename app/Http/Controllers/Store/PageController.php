<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);
        $seo = ['title' => $page->meta_title ?: $page->title.' | Live Bajar', 'description' => $page->meta_description ?: ($page->excerpt ?: $page->title), 'keywords' => $page->meta_keywords, 'canonical' => route('pages.show', $page)];
        return view('store.pages.show', compact('page', 'seo'));
    }
}
