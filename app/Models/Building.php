<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use SoftDeletes;

    protected $table = 'buildings';

    protected $fillable = [
        'building_code',
        'building_name',
        'building_address',
        'description',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }
}