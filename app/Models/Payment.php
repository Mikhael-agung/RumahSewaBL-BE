<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $payment_status Status: menunggu_verifikasi, terverifikasi, ditolak
 * @property-read string|null $proof_file_url Full public URL bukti pembayaran (accessor).
 */
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

    // proof_file_path di DB cuma path relatif (mis. "payment_proofs/xxx.jpeg"),
    // jadi selalu ikutkan proof_file_url ini di setiap response biar frontend
    // gak perlu nyusun URL sendiri (dan gak lupa prefix /storage/).
    protected $appends = ['proof_file_url'];

    public function getProofFileUrlAttribute(): ?string
    {
        if (!$this->proof_file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->proof_file_path);
    }

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