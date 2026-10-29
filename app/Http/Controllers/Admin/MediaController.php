<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $assets = MediaAsset::query()
            ->withCount(['mainProducts', 'productImages'])
            ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search) {
                $nested->where('original_name', 'like', '%'.$search.'%')
                    ->orWhere('alt_text', 'like', '%'.$search.'%')
                    ->orWhere('path', 'like', '%'.$search.'%');
            }))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.media.index', compact('assets', 'search'));
    }

    public function update(Request $request, MediaAsset $media): RedirectResponse
    {
        $data = $request->validate(['alt_text' => ['nullable', 'string', 'max:255']]);
        $media->update($data);

        return back()->with('success', 'Image alt text updated.');
    }

    public function destroy(MediaAsset $media): RedirectResponse
    {
        $mainImageUses = $media->mainProducts()->count();
        $galleryUses = $media->productImages()->count();

        if ($mainImageUses + $galleryUses > 0) {
            return back()->withErrors(['media' => 'This image is used by '.$mainImageUses.' main product image(s) and '.$galleryUses.' gallery image(s). Replace or remove those product references before deleting the file.']);
        }

        Storage::disk('public')->delete($media->path);
        $media->delete();

        return back()->with('success', 'Image file deleted.');
    }
}
