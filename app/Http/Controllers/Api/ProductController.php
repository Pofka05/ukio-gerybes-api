<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // 1. LIST su Filtravimu, Puslapiavimu (Pagination) ir Hypermedia nuorodomis
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filtravimas pagal kategoriją arba kainą
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Puslapiavimas (po 2 produktus puslapyje demonstracijai)
        $products = $query->paginate(2);

        // Hypermedia nuorodų pridėjimas (HATEOAS)
        $products->getCollection()->transform(function ($product) {
            $product->_links = [
                'self' => url("/api/v1/products/{$product->id}"),
                'category' => url("/api/v1/categories/{$product->category_id}"),
                'reviews' => url("/api/v1/products/{$product->id}/reviews"),
            ];

            return $product;
        });

        return response()->json($products, 200);
    }

    // 2. SUJUNGTAS RESURSAS: Ūkininko panelė (Surenkama iš Vartotojo, Produktų ir Atsiliepimų)
    public function farmerDashboard($farmerId)
    {
        $farmer = User::find($farmerId);
        if (! $farmer) {
            return response()->json(['message' => 'Ūkininkas nerastas'], 404);
        }

        if (! $this->canManageUser($farmerId)) {
            return response()->json(['message' => 'Neturite teisės peržiūrėti šios panelės'], 403);
        }

        $products = Product::where('user_id', $farmerId)->with('reviews')->get();

        return response()->json([
            'farmer' => [
                'id' => $farmer->id,
                'name' => $farmer->name,
                'email' => $farmer->email,
            ],
            'total_products' => $products->count(),
            'products' => $products,
        ], 200);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'reviews'])->find($id);
        if (! $product) {
            return response()->json(['message' => 'Produktas nerastas'], 404);
        }

        $product->_links = [
            'self' => url("/api/v1/products/{$product->id}"),
            'reviews' => url("/api/v1/products/{$product->id}/reviews"),
        ];

        return response()->json($product, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'user_id' => 'sometimes|exists:users,id',
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['user_id'] = $user->isAdmin()
            ? ($validated['user_id'] ?? $user->id)
            : $user->id;

        $product = Product::create($validated);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Produktas nerastas'], 404);
        }

        if (! $this->canManageUser($product->user_id)) {
            return response()->json(['message' => 'Neturite teisės redaguoti šio produkto'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $product->update($validated);

        return response()->json($product, 200);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (! $product) {
            return response()->json(['message' => 'Produktas nerastas'], 404);
        }

        if (! $this->canManageUser($product->user_id)) {
            return response()->json(['message' => 'Neturite teisės ištrinti šio produkto'], 403);
        }

        $product->delete();

        return response()->json(null, 204);
    }

    private function canManageUser(int|string $userId): bool
    {
        $user = request()->user();

        return $user?->isAdmin() || (int) $user?->id === (int) $userId;
    }
}
