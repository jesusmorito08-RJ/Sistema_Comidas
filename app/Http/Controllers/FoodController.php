<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FoodController extends Controller
{
    public function index(Request $request): View
    {
        $foods = Food::query()
            ->when($request->boolean('archived'), fn ($query) => $query->onlyTrashed(), fn ($query) => $query->whereNull('deleted_at'))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.foods', compact('foods'));
    }

    public function create(): View
    {
        return view('admin.foods.create');
    }

    public function edit(Food $food): View
    {
        return view('admin.foods.edit', compact('food'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedFood($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('foods', 'public');
        }
        unset($data['image']);
        Food::create($data);

        return redirect()->route('admin.foods.index')->with('success', 'Comida creada correctamente.');
    }

    public function update(Request $request, Food $food): RedirectResponse
    {
        $data = $this->validatedFood($request);
        $previousImage = $food->image_path;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('foods', 'public');
        }
        unset($data['image']);
        $food->update($data);
        if ($request->hasFile('image') && $previousImage && ! filter_var($previousImage, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($previousImage);
        }

        return redirect()->route('admin.foods.index')->with('success', 'Comida actualizada correctamente.');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $food->delete();

        return redirect()->route('admin.foods.index')->with('success', 'Comida archivada; puedes restaurarla sin perder su historial.');
    }

    public function forceDestroy(int $id): RedirectResponse
    {
        $food = Food::withTrashed()->findOrFail($id);
        $imagePath = $food->image_path;
        $food->forceDelete();

        if ($imagePath && ! filter_var($imagePath, FILTER_VALIDATE_URL)) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()->route('admin.foods.index')->with('success', 'Comida eliminada permanentemente; el historial de pedidos se conserva.');
    }

    public function restore(int $id): RedirectResponse
    {
        $food = Food::onlyTrashed()->findOrFail($id);
        $nameExists = Food::query()->where('name', $food->name)->exists();
        if ($nameExists) {
            return back()->withErrors(['food' => 'Ya existe una comida activa con ese nombre. Edita el nombre activo antes de restaurar.']);
        }
        $food->restore();

        return back()->with('success', 'Comida restaurada.');
    }

    private function validatedFood(Request $request): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('foods', 'name')->whereNull('deleted_at')->ignore($request->route('food')?->id),
            ],
            'category' => ['required', Rule::in(Food::CATEGORIES)],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999'],
            'offer_price' => ['nullable', 'numeric', 'gt:0', 'lt:price'],
            'image' => ['nullable', 'image', 'max:4096'],
            'is_available' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ]) + [
            'is_available' => $request->boolean('is_available'),
            'is_featured' => $request->boolean('is_featured'),
        ];
    }
}
