<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'code',
        'value',
        'status',
        'used_by',
        'used_at',
        'expires_at',
    ];

    protected $casts = [
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(VoucherBatch::class, 'batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}

