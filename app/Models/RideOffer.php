<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RideOffer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'zip_code',
        'city',
        'street',
        'last_name',
        'first_name',
        'email',
        'class',
        'phone',
        'valid_from',
        'valid_until',
        'cost_info',
        'additional_info',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'edit_code' => 'string',
    ];

    /**
     * Generate a random edit code for the ride offer.
     *
     * @return string
     */
    public static function generateEditCode(): string
    {
        $code = Str::random(10);

        // Make sure the code is unique
        while (self::where('edit_code', $code)->exists()) {
            $code = Str::random(10);
        }

        return $code;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set default values for valid_from and valid_until if not provided
        static::creating(function ($rideOffer) {
            if (!$rideOffer->valid_from) {
                $rideOffer->valid_from = now();
            }

            if (!$rideOffer->valid_until) {
                $rideOffer->valid_until = now()->addMonths(6);
            }

            // Generate a random edit code
            $rideOffer->edit_code = self::generateEditCode();
        });
    }
}
