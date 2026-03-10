<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'count',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'batch_id');
    }
}

