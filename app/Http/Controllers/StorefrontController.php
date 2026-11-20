<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Food::query()
            ->where('is_available', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $selectedCategory = $request->query('category');
        if (! is_string($selectedCategory) || ! $categories->contains($selectedCategory)) {
            $selectedCategory = null;
        }

        $foods = Food::query()
            ->where('is_available', true)
            ->when($selectedCategory, fn ($query) => $query->where('category', $selectedCategory))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        return view('store.home', compact('foods', 'categories', 'selectedCategory'));
    }

    public function orders(): View
    {
        $orders = Auth::user()->orders()
            ->with([
                'items',
                'paymentProofs' => fn ($query) => $query->orderByDesc('submitted_at')->orderByDesc('id'),
            ])
            ->latest()
            ->get();

        return view('store.orders', compact('orders'));
    }

    public function dashboard(Request $request): View
    {
        $customer = $request->user();
        abort_unless($customer->role === 'customer', 403);

        $recentOrders = $customer->orders()->with('items')->latest()->limit(5)->get();
        $recentReservations = $customer->reservations()->latest('reservation_date')->limit(5)->get();
        $stats = [
            'orders' => $customer->orders()->count(),
            'pending_orders' => $customer->orders()->whereIn('status', ['pending', 'confirmed', 'preparing'])->count(),
            'reservations' => $customer->reservations()->whereIn('status', ['pending', 'confirmed'])->count(),
        ];

        return view('store.dashboard', compact('customer', 'recentOrders', 'recentReservations', 'stats'));
    }
}
