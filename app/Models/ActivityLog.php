<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_description',
        'ip_address',
        'user_agent',
    ];

    public $timestamps = false;

    protected $dates = ['created_at'];
    /**
     * Get the user associated with this activity log.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo The associated User relation.
     */
    public function user() {
        return $this->belongsTo(User::class);
    }
}
