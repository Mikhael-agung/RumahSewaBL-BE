<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDeadline extends Model
{
    protected $fillable = [
        'payment_month',
        'payment_year',
        'deadline_date',
        'created_by',
    ];

    public $timestamps = true;

    /**
     * Get the user who created the payment deadline.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo Relation linking this payment deadline to the `User` model via the `created_by` foreign key.
     */
    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
