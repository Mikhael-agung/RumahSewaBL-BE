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

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }
}
