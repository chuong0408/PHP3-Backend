<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use App\Models\Review;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totalProducts  = Product::count();
        $activeProducts = Product::where('status', 'active')->count();
        $outOfStock     = ProductSku::where('quantity', 0)->count();

        $totalCategories = Category::count();

        $totalOrders   = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $doneOrders    = Order::where('status', 'delivered')->count();

        $revenueTotal = Order::where('status', 'delivered')->sum('total');

        $revenueThisMonth = Order::where('status', 'delivered')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at',  Carbon::now()->year)
            ->sum('total');

        $totalUsers    = User::where('role', 'user')->count();
        $newUsersMonth = User::where('role', 'user')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at',  Carbon::now()->year)
            ->count();

        $pendingReviews = Review::where('status', 'pending')->count();

        $revenueChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $revenueChart[] = [
                'label'   => $month->format('m/Y'),
                'revenue' => (float) Order::where('status', 'delivered')
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at',  $month->year)
                    ->sum('total'),
                'orders' => Order::whereMonth('created_at', $month->month)
                    ->whereYear('created_at',  $month->year)
                    ->count(),
            ];
        }

        $topProducts = Product::withCount(['orderDetails as sold_count'])
            ->orderByDesc('sold_count')
            ->limit(5)
            ->get(['id', 'name', 'price', 'status'])
            ->map(fn($p) => [
                'id'         => $p->id,
                'name'       => $p->name,
                'price'      => $p->price,
                'sold_count' => $p->sold_count,
            ]);

        return response()->json([
            'products'      => ['total' => $totalProducts, 'active' => $activeProducts, 'out_stock' => $outOfStock],
            'categories'    => ['total' => $totalCategories],
            'orders'        => ['total' => $totalOrders, 'pending' => $pendingOrders, 'done' => $doneOrders],
            'revenue'       => ['total' => $revenueTotal, 'this_month' => $revenueThisMonth],
            'users'         => ['total' => $totalUsers, 'new_month' => $newUsersMonth],
            'reviews'       => ['pending' => $pendingReviews],
            'revenue_chart' => $revenueChart,
            'top_products'  => $topProducts,
        ]);
    }
}