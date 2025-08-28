<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewImageResource;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewImageController extends Controller
{
    // List all review images
    public function index()
    {
        $images = ReviewImage::all();

        if ($images->isEmpty()) {
            return response()->json(['message' => 'No review images found'], 200);
        }

        return ReviewImageResource::collection($images);
    }

    // Show a single review image
    public function show(ReviewImage $reviewImage)
    {
        return new ReviewImageResource($reviewImage);
    }

    // Store a new review image
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review_id' => 'required|exists:reviews,id',
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $path = $request->file('image')->store('review_images', 'public');

        $reviewImage = ReviewImage::create([
            'review_id' => $request->review_id,
            'image' => $path,
        ]);

        return new ReviewImageResource($reviewImage);
    }

    // Update a review image (replace file if new one is uploaded)
    public function update(Request $request, ReviewImage $reviewImage)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'sometimes|required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('review_images', 'public');
            $reviewImage->image = $path;
        }

        $reviewImage->save();

        return new ReviewImageResource($reviewImage);
    }

    // Delete a review image
    public function destroy(ReviewImage $reviewImage)
    {
        $reviewImage->delete();

        return response()->json(['message' => 'Review image deleted successfully']);
    }

}
