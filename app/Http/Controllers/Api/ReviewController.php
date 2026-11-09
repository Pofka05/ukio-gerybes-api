<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user']);

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        return response()->json(
            $query->paginate($request->integer('per_page', 10))
        );
    }

    public function show($id)
    {
        $review = Review::with(['product', 'user'])->find($id);

        if (! $review) {
            return response()->json(['message' => 'Atsiliepimas nerastas'], 404);
        }

        return response()->json([
            'data' => $review,
            '_links' => [
                'self' => url("/api/v1/reviews/{$id}"),
                'product' => url("/api/v1/products/{$review->product_id}"),
            ],
        ]);
    }

    // 11. GET /api/v1/products/{productId}/reviews
    public function indexByProduct($productId)
    {
        $product = Product::find($productId);
        if (! $product) {
            return response()->json(['message' => 'Produktas nerastas'], 404);
        }

        $reviews = Review::where('product_id', $productId)->get();

        return response()->json($reviews, 200);
    }

    // 12. POST /api/v1/products/{productId}/reviews
    public function store(Request $request, $productId)
    {
        $product = Product::find($productId);
        if (! $product) {
            return response()->json(['message' => 'Produktas nerastas'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        $validated['product_id'] = $productId;

        $review = Review::create($validated);

        return response()->json($review, 201);
    }

    // 13. PUT /api/v1/reviews/{id}
    public function update(Request $request, $id)
    {
        $review = Review::find($id);
        if (! $review) {
            return response()->json(['message' => 'Atsiliepimas nerastas'], 404);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string',
        ]);

        $review->update($validated);

        return response()->json($review, 200);
    }

    // 14. DELETE /api/v1/reviews/{id}
    public function destroy($id)
    {
        $review = Review::find($id);
        if (! $review) {
            return response()->json(['message' => 'Atsiliepimas nerastas'], 404);
        }

        $review->delete();

        return response()->json(null, 204);
    }

    public function indexFullHierarchy($categoryId, $productId)
    {
        $product = Product::where('id', $productId)
            ->where('category_id', $categoryId)
            ->first();

        if (! $product) {
            return response()->json(['message' => 'Produktas šioje kategorijoje nerastas'], 404);
        }

        $reviews = Review::where('product_id', $productId)->get();

        return response()->json([
            'category_id' => (int) $categoryId,
            'product_id' => (int) $productId,
            'reviews' => $reviews,
        ], 200);
    }
}
