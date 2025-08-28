<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'country',
        'state_province_region',
        'city',
        'description',
    ];

    /**
     * An address belongs to exactly one review.
     */
    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
