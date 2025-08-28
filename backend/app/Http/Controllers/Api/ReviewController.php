<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * List all reviews.
     */
    public function index()
    {
        $reviews = Review::with(['user', 'address', 'images'])->get();

        if ($reviews->count() > 0) {
            return ReviewResource::collection($reviews);
        }

        return response()->json(['message' => 'No records found'], 200);
    }

    /**
     * Store a new review.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id'      => 'required|exists:addresses,id',
            'cafe_shop_name'  => 'required|string|max:255',
            'rating'          => 'required|integer|min:1|max:10',
            'review'          => 'nullable|string',
            'images.*'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // multiple images
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $review = Review::create([
            'user_id'        => $request->user()->id, // authenticated user
            'address_id'     => $request->address_id,
            'cafe_shop_name' => $request->cafe_shop_name,
            'rating'         => $request->rating,
            'review'         => $request->review,
        ]);

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('review_images', 'public');
                $review->images()->create([
                    'image' => $path,
                ]);
            }
        }

        return new ReviewResource($review->load(['user', 'address', 'images']));
    }

    /**
     * Show a single review.
     */
    public function show(Review $review)
    {
        return new ReviewResource($review->load(['user', 'address', 'images']));
    }

    /**
     * Update a review.
     */
    // public function update(Request $request, Review $review)
    // {
    //     $this->authorize('update', $review); // if you add policies

    //     $validator = Validator::make($request->all(), [
    //         'cafe_shop_name'  => 'sometimes|required|string|max:255',
    //         'rating'          => 'sometimes|required|integer|min:1|max:5',
    //         'review'          => 'sometimes|nullable|string',
    //         'images.*'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->messages()], 422);
    //     }

    //     $review->update($request->only(['cafe_shop_name', 'rating', 'review']));

    //     // If new images are uploaded, add them
    //     if ($request->hasFile('images')) {
    //         foreach ($request->file('images') as $image) {
    //             $path = $image->store('review_images', 'public');
    //             $review->images()->create([
    //                 'image' => $path,
    //             ]);
    //         }
    //     }

    //     return new ReviewResource($review->load(['user', 'address', 'images']));
    // }

    // /**
    //  * Delete a review.
    //  */
    // public function destroy(Review $review)
    // {
    //     $this->authorize('delete', $review); // if you add policies

    //     $review->delete();

    //     return response()->json(['message' => 'Review deleted successfully']);
    // }
}
