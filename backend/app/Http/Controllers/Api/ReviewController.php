<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    // GET all reviews
    public function index()
    {
        $reviews = Review::with(['user', 'address', 'images'])->latest()->get();

        if ($reviews->count() > 0) {
            return ReviewResource::collection($reviews);
        }

        return response()->json(['message' => 'No records found'], 200);
    }

    // GET a single review
    public function show(Review $review)
    {
        $review->load(['user', 'address', 'images']);
        return new ReviewResource($review);
    }

    // POST create a review (single endpoint with images)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address_id'      => 'required|exists:addresses,id',
            'cafe_shop_name'  => 'required|string|max:255',
            'rating'          => 'required|integer|min:1|max:5',
            'review'          => 'required|string',
            'images.*'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $user = $request->user();

        // create review
        $review = Review::create([
            'user_id'        => $user->id,
            'address_id'     => $request->address_id,
            'cafe_shop_name' => $request->cafe_shop_name,
            'rating'         => $request->rating,
            'review'         => $request->review,
        ]);

        // save multiple images if provided
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('review_images', 'public');
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image'     => $path,
                ]);
            }
        }

        $review->load(['user', 'address', 'images']);

        return response()->json([
            'message' => 'Review created successfully',
            'data'    => new ReviewResource($review)
        ], 201);
    }

    // UPDATE a review (with optional new images)
    public function update(Request $request, Review $review)
    {
        $validator = Validator::make($request->all(), [
            'cafe_shop_name'  => 'sometimes|required|string|max:255',
            'rating'          => 'sometimes|required|integer|min:1|max:5',
            'review'          => 'sometimes|required|string',
            'images.*'        => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $review->update($request->only(['cafe_shop_name', 'rating', 'review']));

        // add new images if provided
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('review_images', 'public');
                ReviewImage::create([
                    'review_id' => $review->id,
                    'image'     => $path,
                ]);
            }
        }

        $review->load(['user', 'address', 'images']);

        return response()->json([
            'message' => 'Review updated successfully',
            'data'    => new ReviewResource($review)
        ], 200);
    }

    // DELETE a review
    public function destroy(Review $review)
    {
        $review->images()->delete(); // remove related images
        $review->delete();

        return response()->json(['message' => 'Review deleted successfully'], 200);
    }
}
