<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use SoftDeletes;

    protected $table = 'rooms';

    protected $fillable = [
        'building_id',
        'room_code',
        'monthly_price',
        'room_status',
        'notes',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
    ];

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function rentals()
    {
        return $this->hasMany(Rental::class);
    }
}