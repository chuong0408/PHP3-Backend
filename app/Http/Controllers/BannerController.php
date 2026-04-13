<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    // ── Helper: gắn base URL đầy đủ cho ảnh ─────────────────────────────────
    private function appendImageUrl(Banner $banner): void
    {
        $base = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        if ($banner->image_url && !str_starts_with($banner->image_url, 'http')) {
            $banner->image_url = $base . '/storage/' . $banner->image_url;
        }
    }

    // ── GET /api/admin/banners ────────────────────────────────────────────────
    // Lấy tất cả banner (admin quản lý)
    public function index()
    {
        $banners = Banner::orderBy('sort_order')->orderByDesc('created_at')->get();

        $banners->each(fn($b) => $this->appendImageUrl($b));

        return response()->json($banners);
    }

    // ── GET /api/banners (public) ─────────────────────────────────────────────
    // Chỉ lấy banner đang active để hiển thị trang chủ
    public function publicIndex()
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $banners->each(fn($b) => $this->appendImageUrl($b));

        return response()->json($banners);
    }

    // ── POST /api/admin/banners ───────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'image'       => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'link'        => 'nullable|string|max:500',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            'banner_type' => 'nullable|in:main,side',
        ]);

        // Upload ảnh vào storage/app/public/banners/
        $path = $request->file('image')->store('banners', 'public');

        $banner = Banner::create([
            'title'       => $request->title,
            'image_url'   => $path,
            'link'        => $request->link,
            'description' => $request->description,
            'is_active'   => $request->boolean('is_active', true),
            'sort_order'  => $request->input('sort_order', 0),
            'banner_type' => $request->input('banner_type', 'main'),  
        ]);

        $this->appendImageUrl($banner);

        return response()->json($banner, 201);
    }

    // ── GET /api/admin/banners/{banner} ──────────────────────────────────────
    public function show(Banner $banner)
    {
        $this->appendImageUrl($banner);
        return response()->json($banner);
    }

    // ── PUT/PATCH /api/admin/banners/{banner} ─────────────────────────────────
    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'link'        => 'nullable|string|max:500',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0',
            'banner_type' => 'nullable|in:main,side',
        ]);

        $data = $request->only(['title', 'link', 'description', 'sort_order', 'banner_type']);

        // Xử lý boolean is_active
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        // Nếu có ảnh mới → xóa ảnh cũ rồi upload
        if ($request->hasFile('image')) {
            // Xóa ảnh cũ (chỉ xóa nếu không phải URL ngoài)
            if ($banner->image_url && !str_starts_with($banner->image_url, 'http')) {
                Storage::disk('public')->delete($banner->image_url);
            }
            $data['image_url'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);
        $this->appendImageUrl($banner);

        return response()->json($banner);
    }

    // ── DELETE /api/admin/banners/{banner} ────────────────────────────────────
    public function destroy(Banner $banner)
    {
        // Xóa file ảnh khỏi storage
        if ($banner->image_url && !str_starts_with($banner->image_url, 'http')) {
            Storage::disk('public')->delete($banner->image_url);
        }

        $banner->delete();

        return response()->json(['message' => 'Xóa banner thành công!']);
    }

    // ── PATCH /api/admin/banners/{banner}/toggle ──────────────────────────────
    // Bật/tắt nhanh trạng thái hiển thị
    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => !$banner->is_active]);
        $this->appendImageUrl($banner);

        return response()->json([
            'message'   => $banner->is_active ? 'Banner đã được bật!' : 'Banner đã được tắt!',
            'banner'    => $banner,
        ]);
    }
}