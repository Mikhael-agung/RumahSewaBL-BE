<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use softDeletes;
    protected $table = 'payments';
    protected $fillable = [
        'payment_code',
        'rental_id',
        'payment_month',
        'payment_year',
        'amount',
        'payment_date',
        'payment_method',
        'payment_status',
        'proof_file_name',
        'proof_file_path',
        'proof_file_size',
        'proof_file_mime',
        'uploaded_at',
        'notes',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'created_by',

    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'uploaded_at'  => 'datetime',
        'verified_at'  => 'datetime',
        'amount'       => 'decimal:2',
    ];

        public function rental()
    {
        return $this->belongsTo(Rental::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public $timestamps = true;
}
