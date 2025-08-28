<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_id',
        'cafe_shop_name',
        'rating',
        'review',
    ];

    /**
     * A review belongs to a user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A review belongs to an address.
     */
    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * A review can have many images.
     */
    public function images()
    {
        return $this->hasMany(ReviewImage::class);
    }
}
