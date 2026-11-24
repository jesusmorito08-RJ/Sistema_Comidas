<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    private const MENU_SECTION_CATEGORIES = [
        'entradas' => [
            'title' => 'Entradas',
            'categories' => ['Entradas criollas', 'Antojitos'],
        ],
        'sopas' => [
            'title' => 'Sopas',
            'categories' => ['Sopas criollas'],
        ],
        'segundos' => [
            'title' => 'Segundos',
            'categories' => ['Platos criollos', 'Platos a la carta', 'Plato principal'],
            'include_unmapped' => true,
        ],
        'pastas-hamburguesas' => [
            'title' => 'Pastas y hamburguesas',
            'categories' => ['Pastas', 'Hamburguesas', 'Saludables'],
        ],
        'bebidas' => [
            'title' => 'Bebidas',
            'categories' => ['Bebidas peruanas'],
        ],
        'postres' => [
            'title' => 'Postres',
            'categories' => ['Postres'],
        ],
    ];

    public function index(Request $request): View
    {
        $availableFoods = Food::query()
            ->where('is_available', true)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();
        $categories = $availableFoods->pluck('category')->unique()->sort()->values();
        $selectedCategory = $request->query('category');
        if (! is_string($selectedCategory) || ! $categories->contains($selectedCategory)) {
            $selectedCategory = null;
        }

        $dailyFoods = Food::query()
            ->where('is_available', true)
            ->where('is_featured', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($dailyFoods->isEmpty()) {
            $dailyFoods = Food::query()
                ->where('is_available', true)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        }

        $categorySections = [];
        foreach (self::MENU_SECTION_CATEGORIES as $sectionId => $section) {
            foreach ($section['categories'] as $category) {
                $categorySections[$category] = $sectionId;
            }
        }

        $categorySectionId = $selectedCategory
            ? ($categorySections[$selectedCategory] ?? 'segundos')
            : null;
        $dailyFoodIds = $dailyFoods->modelKeys();
        $menuPages = collect();
        $activeTabId = null;

        if ($dailyFoods->isNotEmpty()) {
            $menuPages->push([
                'id' => 'platillos-del-dia',
                'title' => 'Platillos del día',
                'eyebrow' => 'HOY EN SAZÓN',
                'description' => 'Una selección de la casa, preparada con ingredientes frescos y mucho sabor.',
                'foods' => $dailyFoods,
                'compact' => false,
            ]);
            $activeTabId = 'platillos-del-dia';
        }

        foreach (self::MENU_SECTION_CATEGORIES as $sectionId => $section) {
            $sectionFoods = $availableFoods->filter(function (Food $food) use ($section, $sectionId, $categorySections): bool {
                return in_array($food->category, $section['categories'], true)
                    || ($sectionId === 'segundos'
                        && ($section['include_unmapped'] ?? false)
                        && ! isset($categorySections[$food->category]));
            });

            if ($selectedCategory && $categorySectionId === $sectionId) {
                $sectionFoods = $sectionFoods->where('category', $selectedCategory);
            } else {
                $sectionFoods = $sectionFoods->reject(
                    fn (Food $food) => in_array($food->getKey(), $dailyFoodIds, true),
                );
            }

            $sectionFoods = $sectionFoods->values();
            if ($sectionFoods->isEmpty()) {
                continue;
            }

            $menuPages->push([
                'id' => $sectionId,
                'title' => $section['title'],
                'eyebrow' => 'DE NUESTRA CARTA',
                'description' => 'Sabores preparados al momento con ingredientes seleccionados.',
                'foods' => $sectionFoods,
                'compact' => $sectionId === 'bebidas',
            ]);

            if ($selectedCategory && $categorySectionId === $sectionId) {
                $activeTabId = $sectionId;
            }
        }

        return view('store.home', compact('dailyFoods', 'menuPages', 'activeTabId'));
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
