<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%'.$request->title.'%');
        }

        $categories = $query
            ->withCount('products')
            ->paginate($request->integer('per_page', 10));

        return response()->json($categories);
    }

    public function show($id)
    {
        $category = Category::withCount('products')->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Kategorija nerasta',
            ], 404);
        }

        return response()->json([
            'data' => $category,
            '_links' => [
                'self' => url("/api/v1/categories/{$id}"),
                'products' => url("/api/v1/products?category_id={$id}"),
                'catalog' => url("/api/v1/categories/{$id}/catalog"),
            ],
        ]);
    }

    public function catalog($id)
    {
        $category = Category::with([
            'products' => fn ($query) => $query->with('reviews'),
        ])->find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Kategorija nerasta',
            ], 404);
        }

        return response()->json([
            'data' => $category,
            '_links' => [
                'self' => url("/api/v1/categories/{$id}/catalog"),
                'category' => url("/api/v1/categories/{$id}"),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $category = Category::create($request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]));

        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Kategorija nerasta',
            ], 404);
        }

        $category->update($request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]));

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Kategorija nerasta',
            ], 404);
        }

        $category->delete();

        return response()->noContent();
    }
}
