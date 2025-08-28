<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index() {
        $addresses = Address::get();
        if($addresses->count() > 0) {
            return AddressResource::collection($addresses);      
        }
        else {
            return response()->json(['message' => 'No records found'], 200);
        }
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'country' => 'required|string|max:255',
            'state_province_region' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 422);
        }

        $address = Address::create([
            'country' => $request->country,
            'state_province_region' => $request->state_province_region,
            'city' => $request->city,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Address created successfully',
            'data' => $address
        ], 201);
    }

    public function show(Address $address) {
        return new AddressResource($address);
    }

    public function update(Request $request, Address $address){
        $validator = Validator::make($request->all(), [
            'country' => 'sometimes|required|string|max:255',
            'state_province_region' => 'sometimes|nullable|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages()], 422);
        }

        $address->update([
            'country' => $request->country ?? $address->country,
            'state_province_region' => $request->state_province_region ?? $address->state_province_region,
            'city' => $request->city ?? $address->city,
            'description' => $request->description ?? $address->description,
        ]);

        return response()->json([
            'message' => 'Address updated successfully',
            'data' => $address
        ], 200);
    }

    public function destroy(Address $address) {
        $address->delete();
        return response()->json(['message' => 'Address deleted successfully'], 200);
    }
}
