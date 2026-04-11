<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════════
    //  USER ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * POST /api/user/reviews
     * Gửi đánh giá cho 1 sản phẩm (sku) đã mua.
     * Yêu cầu auth:sanctum.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_sku_code' => 'required|string|exists:product_skus,sku_code',
            'rating'           => 'required|integer|between:1,5',
            'comment'          => 'required|string|min:5|max:1000',
        ], [
            'product_sku_code.exists' => 'Sản phẩm không tồn tại.',
            'rating.between'          => 'Điểm đánh giá từ 1 đến 5.',
            'comment.min'             => 'Nội dung đánh giá ít nhất 5 ký tự.',
        ]);

        $user = $request->user();

        // Kiểm tra user đã mua sản phẩm này chưa (đơn hàng delivered)
        $hasPurchased = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->whereHas('details', fn($q) =>
                $q->where('product_sku_code', $request->product_sku_code)
            )->exists();

        if (! $hasPurchased) {
            return response()->json([
                'message' => 'Bạn chỉ có thể đánh giá sản phẩm đã mua và đã giao thành công.',
            ], 403);
        }

        // Chỉ cho phép 1 đánh giá / sku / user
        $exists = Review::where('user_id', $user->id)
            ->where('product_sku_code', $request->product_sku_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Bạn đã đánh giá sản phẩm này rồi.',
            ], 422);
        }

        $review = Review::create([
            'user_id'          => $user->id,
            'product_sku_code' => $request->product_sku_code,
            'rating'           => $request->rating,
            'comment'          => $request->comment,
            'status'           => 'pending',
        ]);

        return response()->json([
            'message' => 'Đánh giá đã được gửi và đang chờ duyệt.',
            'review'  => $this->formatReview($review->load('user', 'sku.product')),
        ], 201);
    }

    /**
     * GET /api/user/reviews
     * Lấy tất cả đánh giá của user đang đăng nhập.
     */
    public function myReviews(Request $request)
    {
        $user    = $request->user();
        $reviews = Review::with(['sku.product'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => $this->formatReview($r));

        return response()->json(['data' => $reviews]);
    }

    /**
     * GET /api/reviews/product/{productId}
     * Lấy đánh giá đã được duyệt của 1 sản phẩm (public).
     */
    public function byProduct($productId)
    {
        $reviews = Review::with(['user', 'sku'])
            ->where('status', 'approved')
            ->whereHas('sku', fn($q) => $q->where('product_id', $productId))
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($r) => $this->formatReview($r));

        return response()->json(['data' => $reviews]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    //  ADMIN ENDPOINTS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * GET /api/admin/reviews
     * Danh sách tất cả đánh giá, có lọc theo status.
     */
    public function adminIndex(Request $request)
    {
        $query = Review::with(['user', 'sku.product'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reviews = $query->paginate(15);
        $reviews->getCollection()->transform(fn($r) => $this->formatReview($r));

        return response()->json($reviews);
    }

    /**
     * PATCH /api/admin/reviews/{id}/approve
     * Duyệt đánh giá.
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Đã duyệt đánh giá.',
            'review'  => $this->formatReview($review->load('user', 'sku.product')),
        ]);
    }

    /**
     * PATCH /api/admin/reviews/{id}/reject
     * Từ chối đánh giá.
     */
    public function reject($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Đã từ chối đánh giá.',
            'review'  => $this->formatReview($review->load('user', 'sku.product')),
        ]);
    }

    /**
     * DELETE /api/admin/reviews/{id}
     * Xoá đánh giá.
     */
    public function destroy($id)
    {
        Review::findOrFail($id)->delete();

        return response()->json(['message' => 'Đã xoá đánh giá.']);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    private function formatReview(Review $review): array
    {
        $base    = rtrim(env('APP_URL', 'http://localhost:8000'), '/');
        $product = $review->sku?->product;

        $imageUrl = null;
        if ($product?->image_url) {
            $imageUrl = str_starts_with($product->image_url, 'http')
                ? $product->image_url
                : $base . '/storage/' . $product->image_url;
        }

        $avatarUrl = null;
        if ($review->user?->avatar) {
            $avatarUrl = str_starts_with($review->user->avatar, 'http')
                ? $review->user->avatar
                : $base . '/storage/' . $review->user->avatar;
        }

        return [
            'id'               => $review->id,
            'user_id'          => $review->user_id,
            'user_name'        => $review->user?->fullname ?? 'Khách hàng',
            'user_avatar'      => $avatarUrl,
            'product_sku_code' => $review->product_sku_code,
            'product_name'     => $product?->name,
            'product_image'    => $imageUrl,
            'rating'           => $review->rating,
            'comment'          => $review->comment,
            'status'           => $review->status,
            'created_at'       => $review->created_at?->toDateTimeString(),
        ];
    }
}