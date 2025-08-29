<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Address;
use App\Models\Review;
use App\Models\ReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            // Either provide an existing address_id OR the nested address fields
            'address_id'                         => 'nullable|exists:addresses,id',
            'address.country'                    => 'required_without:address_id|string|max:255',
            'address.state_province_region'      => 'required_without:address_id|string|max:255',
            'address.city'                       => 'required_without:address_id|string|max:255',
            'address.description'                => 'nullable|string',

            'cafe_shop_name'                     => 'required|string|max:255',
            'rating'                             => 'required|integer|min:1|max:10',
            'review'                             => 'required|string',
            'images.*'                           => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'address.country.required_without' => 'Provide address.country, address.state_province_region and address.city or an address_id.',
            'address.city.required_without'    => 'Provide address.country, address.state_province_region and address.city or an address_id.',
            'address.state_province_region.required_without' => 'Provide address.country, address.state_province_region and address.city or an address_id.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $user = $request->user();

        $review = DB::transaction(function () use ($request, $user) {
            // Decide where the address comes from
            if ($request->filled('address_id')) {
                $addressId = (int) $request->address_id; // link to existing
            } 
            else {
                // create a new address from nested payload
                $address = Address::create([
                    'country'                 => $request->input('address.country'),
                    'state_province_region'   => $request->input('address.state_province_region'),
                    'city'                    => $request->input('address.city'),
                    'description'             => $request->input('address.description'),
                ]);
                $addressId = $address->id;
            }

            // create the review
            $review = Review::create([
                'user_id'        => $user->id,
                'address_id'     => $addressId,
                'cafe_shop_name' => $request->cafe_shop_name,
                'rating'         => $request->rating,
                'review'         => $request->review,
            ]);

            // attach images (if any)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('review_images', 'public');
                    $review->images()->create(['image' => $path]);
                }
            }

            return $review;
        });

        $review->load(['user', 'address', 'images']);

        return response()->json([
            'message' => 'Review created successfully',
            'data'    => new ReviewResource($review),
        ], 201);
    }


    // UPDATE a review (with optional new images)
    public function update(Request $request, Review $review)
    {
        $validator = Validator::make($request->all(), [
            // address: update only (no swapping/creating new address)
            'address.country'               => 'sometimes|required|string|max:255',
            'address.state_province_region' => 'sometimes|required|string|max:255',
            'address.city'                  => 'sometimes|required|string|max:255',
            'address.description'           => 'nullable|string',

            'cafe_shop_name'                => 'sometimes|required|string|max:255',
            'rating'                        => 'sometimes|required|integer|min:1|max:10',
            'review'                        => 'sometimes|required|string',

            // replace-mode for images
            'keep_image_ids'                => 'sometimes|array',
            'keep_image_ids.*'              => 'integer|exists:review_images,id',
            'images.*'                      => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->messages()], 422);
        }

        $review = DB::transaction(function () use ($request, $review) {
            // ✅ Update address if any nested fields present
            if ($request->hasAny([
                'address.country',
                'address.state_province_region',
                'address.city',
                'address.description'
            ]) && $review->address) {
                $review->address->update([
                    'country'               => $request->input('address.country', $review->address->country),
                    'state_province_region' => $request->input('address.state_province_region', $review->address->state_province_region),
                    'city'                  => $request->input('address.city', $review->address->city),
                    'description'           => $request->input('address.description', $review->address->description),
                ]);
            }

            // ✅ Update review fields
            $review->update([
                'cafe_shop_name' => $request->input('cafe_shop_name', $review->cafe_shop_name),
                'rating'         => $request->input('rating', $review->rating),
                'review'         => $request->input('review', $review->review),
            ]);

            // ✅ Replace-mode for images
            if ($request->exists('keep_image_ids')) {
                $raw = $request->input('keep_image_ids');
                $keepIds = is_array($raw) ? $raw : (isset($raw) ? [$raw] : []);
                $keepIds = collect($keepIds)
                    ->filter(fn($id) => $id !== null && $id !== '')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                $query = $review->images();
                if (count($keepIds) > 0) {
                    $query->whereNotIn('id', $keepIds);
                }
                $query->get()->each(function ($image) {
                    Storage::disk('public')->delete($image->image);
                    $image->delete();
                });
            }

            // ✅ Add new uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('review_images', 'public');
                    $review->images()->create(['image' => $path]);
                }
            }

            return $review;
        });

        $review->load(['user', 'address', 'images']);

        return response()->json([
            'message' => 'Review updated successfully',
            'data'    => new ReviewResource($review),
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
