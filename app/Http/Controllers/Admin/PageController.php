<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View { return view('admin.pages.index', ['pages' => Page::query()->latest()->paginate(20)]); }
    public function create(): View { return view('admin.pages.create', ['page' => new Page(['is_published' => true])]); }
    public function store(Request $request): RedirectResponse { Page::query()->create($this->validated($request)); return to_route('admin.pages.index')->with('success', 'Page created.'); }
    public function edit(Page $page): View { return view('admin.pages.edit', compact('page')); }
    public function update(Request $request, Page $page): RedirectResponse { $page->update($this->validated($request, $page)); return to_route('admin.pages.index')->with('success', 'Page updated.'); }
    public function destroy(Page $page): RedirectResponse { $page->delete(); return to_route('admin.pages.index')->with('success', 'Page deleted.'); }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate(['title' => ['required','string','max:180'], 'slug' => ['required','string','max:180','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages','slug')->ignore($page)], 'excerpt' => ['nullable','string','max:500'], 'content' => ['nullable','string','max:50000'], 'meta_title' => ['nullable','string','max:70'], 'meta_description' => ['nullable','string','max:160'], 'meta_keywords' => ['nullable','string','max:255']]);
        $data['is_published'] = $request->boolean('is_published');
        return $data;
    }
}
